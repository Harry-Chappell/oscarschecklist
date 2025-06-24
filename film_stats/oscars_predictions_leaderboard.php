<?php

/**
 * Shortcode to display a leaderboard of users by correct predictions or correct prediction rate, with AJAX switch.
 */
function oscars_predictions_leaderboard_shortcode($atts = []) {
    $atts = shortcode_atts(['top' => null], $atts);
    $top = is_numeric($atts['top']) && intval($atts['top']) > 0 ? intval($atts['top']) : null;
    ob_start();
    ?>
    <form id="oscars-predictions-leaderboard-form" style="margin-bottom:1em">
        <label><input type="radio" name="oscars_leaderboard_sort" value="correct" checked> Correct Predictions</label>
        <label><input type="radio" name="oscars_leaderboard_sort" value="rate"> Prediction Rate</label>
    </form>
    <div id="oscars-predictions-leaderboard-results"></div>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        function fetchLeaderboard(sort) {
            var xhr = new XMLHttpRequest();
            xhr.open('POST', window.location.href, true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onload = function() {
                if (xhr.status === 200) {
                    var parser = new DOMParser();
                    var doc = parser.parseFromString(xhr.responseText, 'text/html');
                    var leaderboard = doc.querySelector('#oscars-predictions-leaderboard-ajax');
                    if (leaderboard) {
                        document.getElementById('oscars-predictions-leaderboard-results').innerHTML = leaderboard.innerHTML;
                    }
                }
            };
            xhr.send('oscars_leaderboard_sort=' + encodeURIComponent(sort) + '&oscars_leaderboard_ajax=1&oscars_leaderboard_top=<?php echo $top !== null ? intval($top) : ''; ?>');
        }
        var radios = document.querySelectorAll('#oscars-predictions-leaderboard-form input[type=radio]');
        radios.forEach(function(radio) {
            radio.addEventListener('change', function() {
                fetchLeaderboard(this.value);
            });
        });
        // Initial load
        fetchLeaderboard(document.querySelector('#oscars-predictions-leaderboard-form input[type=radio]:checked').value);
    });
    </script>
    <div id="oscars-predictions-leaderboard-ajax" style="display:none">
        <?php echo oscars_predictions_leaderboard_inner($top); ?>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('oscars_predictions_leaderboard', 'oscars_predictions_leaderboard_shortcode');

function oscars_predictions_leaderboard_inner($top = null) {
    $output_path = ABSPATH . 'wp-content/uploads/all_user_stats.json';
    $sort_by = isset($_POST['oscars_leaderboard_sort']) && $_POST['oscars_leaderboard_sort'] === 'rate' ? 'correct-prediction-rate' : 'correct-predictions';
    if (isset($_POST['oscars_leaderboard_top']) && is_numeric($_POST['oscars_leaderboard_top']) && intval($_POST['oscars_leaderboard_top']) > 0) {
        $top = intval($_POST['oscars_leaderboard_top']);
    }
    if (!file_exists($output_path)) {
        return '<p>No leaderboard data found. Please generate it first.</p>';
    }
    $json = file_get_contents($output_path);
    $users = json_decode($json, true);
    if (!$users || !is_array($users)) {
        return '<p>Leaderboard data is invalid.</p>';
    }
    // Calculate average from all users
    $sum = 0;
    $count = 0;
    foreach ($users as $user) {
        $score = isset($user[$sort_by]) && $user[$sort_by] !== '' ? $user[$sort_by] : 0;
        if ($sort_by === 'correct-prediction-rate' && is_numeric($score)) {
            $sum += $score;
            $count++;
        } elseif ($sort_by === 'correct-predictions') {
            $sum += intval($score);
            $count++;
        }
    }
    usort($users, function($a, $b) use ($sort_by) {
        $a_val = isset($a[$sort_by]) && $a[$sort_by] !== '' ? $a[$sort_by] : 0;
        $b_val = isset($b[$sort_by]) && $b[$sort_by] !== '' ? $b[$sort_by] : 0;
        return $b_val <=> $a_val;
    });
    // Prepare user/friends logic
    $current_user_id = is_user_logged_in() ? get_current_user_id() : null;
    $current_username = null;
    $friends = [];
    if ($current_user_id) {
        $current_user_data = get_userdata($current_user_id);
        $current_username = $current_user_data ? $current_user_data->user_login : null;
        if (function_exists('friends_get_friend_user_ids')) {
            $friends = friends_get_friend_user_ids($current_user_id);
        } else {
            $friends = [];
        }
    }
    // Build rank map and find user/friends
    $ranked_users = [];
    $user_ranks = [];
    $last_score = null;
    $rank = 0;
    $display_rank = 0;
    foreach ($users as $i => $user) {
        $rank++;
        $score = isset($user[$sort_by]) && $user[$sort_by] !== '' ? $user[$sort_by] : 0;
        if ($last_score !== $score) {
            $display_rank = $rank;
            $last_score = $score;
        }
        $user_ranks[$user['user_id']] = [
            'rank' => $display_rank,
            'user' => $user
        ];
        $ranked_users[] = [
            'rank' => $display_rank,
            'user' => $user
        ];
    }
    // Top X
    $top_users = $top !== null ? array_slice($ranked_users, 0, $top) : $ranked_users;
    // Collect user/friends (if not in top X)
    $extra_users = [];
    $added_ids = array_column($top_users, 'user_id');
    if ($current_user_id && isset($user_ranks[$current_user_id]) && !in_array($current_user_id, $added_ids)) {
        $extra_users[$current_user_id] = $user_ranks[$current_user_id];
    }
    foreach ($ranked_users as $entry) {
        $u = $entry['user'];
        if ($current_user_id && in_array($u['user_id'], $friends) && !in_array($u['user_id'], $added_ids) && $u['user_id'] !== $current_user_id) {
            $extra_users[$u['user_id']] = $entry;
        }
    }
    // Output
    $output = '<ul class="oscars-leaderboard">';
    $suffixes = function($n) {
        if ($n % 10 == 1 && $n % 100 != 11) return 'st';
        if ($n % 10 == 2 && $n % 100 != 12) return 'nd';
        if ($n % 10 == 3 && $n % 100 != 13) return 'rd';
        return 'th';
    };
    $shown_ids = [];
    foreach ($top_users as $entry) {
        $u = $entry['user'];
        $shown_ids[] = $u['user_id'];
        $username = esc_html($u['username']);
        $username = (!empty($u['public']) && !empty($user['username'])) ? esc_html($user['username']) : 'anonymous';
        $highlight = ($current_user_id && $u['user_id'] == $current_user_id) ? ' class="current-user"' : '';
        $display_score = $sort_by === 'correct-prediction-rate' ? (is_numeric($u[$sort_by]) ? (100 * $u[$sort_by]) . '%' : 'N/A') : intval($u[$sort_by]);
        $output .= '<li' . $highlight . '>' . $entry['rank'] . $suffixes($entry['rank']) . ': ' . $username . ' - ' . $display_score . '</li>';
    }
    // Show current user and friends if not already shown
    foreach ($extra_users as $uid => $entry) {
        if (in_array($uid, $shown_ids)) continue;
        $u = $entry['user'];
        $username = esc_html($u['username']);
        $highlight = ($current_user_id && $u['user_id'] == $current_user_id) ? ' class="current-user"' : ' class="current-users-friend"';
        $display_score = $sort_by === 'correct-prediction-rate' ? (is_numeric($u[$sort_by]) ? (100 * $u[$sort_by]) . '%' : 'N/A') : intval($u[$sort_by]);
        $output .= '<li' . $highlight . '>' . $entry['rank'] . $suffixes($entry['rank']) . ': ' . $username . ' - ' . $display_score . '</li>';
    }
    $output .= '</ul>';
    if ($count > 0) {
        if ($sort_by === 'correct-prediction-rate') {
            $avg = round(100 * $sum / $count, 1);
            $output .= '<div class="oscars-leaderboard-avg">Average: ' . $avg . '%</div>';
        } else {
            $avg = round($sum / $count, 1);
            $output .= '<div class="oscars-leaderboard-avg">Average: ' . $avg . '</div>';
        }
    }
    return $output;
}