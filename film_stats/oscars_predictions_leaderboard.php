<?php

/**
 * Shortcode to display a leaderboard of users by correct predictions or prediction rate.
 * Usage: [oscars_predictions_leaderboard stat="correct-predictions"|"prediction-rate" top="10"]
 */
function oscars_predictions_leaderboard_shortcode($atts = []) {
    $atts = shortcode_atts([
        'top' => null,
        'stat' => 'correct-predictions',
    ], $atts);
    $top = is_numeric($atts['top']) && intval($atts['top']) > 0 ? intval($atts['top']) : null;
    $stat = $atts['stat'] === 'prediction-rate' ? 'correct-prediction-rate' : 'correct-predictions';
    return oscars_predictions_leaderboard_inner($top, $stat);
}
add_shortcode('oscars_predictions_leaderboard', 'oscars_predictions_leaderboard_shortcode');

function oscars_predictions_leaderboard_inner($top = null, $stat = 'correct-predictions') {
    $output_path = ABSPATH . 'wp-content/uploads/all_user_stats.json';
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
        $score = isset($user[$stat]) && $user[$stat] !== '' ? $user[$stat] : 0;
        if ($stat === 'correct-prediction-rate' && is_numeric($score)) {
            $sum += $score;
            $count++;
        } elseif ($stat === 'correct-predictions') {
            $sum += intval($score);
            $count++;
        }
    }
    // Sort users by stat descending
    usort($users, function($a, $b) use ($stat) {
        $a_val = isset($a[$stat]) && $a[$stat] !== '' ? $a[$stat] : 0;
        $b_val = isset($b[$stat]) && $b[$stat] !== '' ? $b[$stat] : 0;
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
        $score = isset($user[$stat]) && $user[$stat] !== '' ? $user[$stat] : 0;
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
    // Collect user and friends (if not in top X)
    $user_and_friends_ids = [];
    if ($current_user_id && isset($user_ranks[$current_user_id])) {
        $user_and_friends_ids[] = $current_user_id;
    }
    foreach ($friends as $fid) {
        if (isset($user_ranks[$fid])) {
            $user_and_friends_ids[] = $fid;
        }
    }
    // Remove any already in top_users
    $added_ids = array_map(function($entry) { return $entry['user']['user_id']; }, $top_users);
    $user_and_friends_ids = array_diff($user_and_friends_ids, $added_ids);
    // Get user/friend entries in leaderboard order
    $user_and_friends_entries = [];
    foreach ($ranked_users as $entry) {
        if (in_array($entry['user']['user_id'], $user_and_friends_ids)) {
            $user_and_friends_entries[] = $entry;
        }
    }
    // Output
    $heading = $stat === 'correct-prediction-rate' ? 'Prediction Rate' : 'Correct Predictions';
    $output = '<h2>' . esc_html($heading) . '</h2>';
    $output .= '<h3>Top ' . $top . ' & Friends</h3>';
    $output .= '<ul class="leaderboard">';
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
        $username = (!empty($u['public']) && !empty($u['username'])) ? esc_html($u['username']) : 'Anonymous';
        $highlight = ($current_user_id && $u['user_id'] == $current_user_id) ? ' class="current-user"' : '';
        $display_score = $stat === 'correct-prediction-rate' ? (is_numeric($u[$stat]) ? (round(100 * $u[$stat], 1) . '%') : 'N/A') : intval($u[$stat]);
        $output .= '<li' . $highlight . '><span class="rank">' . $entry['rank'] . '<sup>' . $suffixes($entry['rank']) . '</sup> </span><span class="username">' . $username . '</span><span class="stat-value">' . $display_score . '</span></li>';
    }
    $output .= '<li class="spacer"></li>';
    // Show current user and friends (in order) after top X
    foreach ($user_and_friends_entries as $entry) {
        $u = $entry['user'];
        $shown_ids[] = $u['user_id'];
        $username = esc_html($u['username']);
        // $username = (!empty($u['public']) && !empty($u['username'])) ? esc_html($u['username']) : 'Anonymous';
        $highlight = ($current_user_id && $u['user_id'] == $current_user_id) ? ' class="current-user"' : ' class="current-users-friend"';
        $display_score = $stat === 'correct-prediction-rate' ? (is_numeric($u[$stat]) ? (round(100 * $u[$stat], 1) . '%') : 'N/A') : intval($u[$stat]);
        $output .= '<li' . $highlight . '><span class="rank">' . $entry['rank'] . '<sup>' . $suffixes($entry['rank']) . '</sup> </span><span class="username">' . $username . '</span><span class="stat-value">' . $display_score . '</span></li>';
    }
    $output .= '</ul>';
    if ($count > 0) {
        if ($stat === 'correct-prediction-rate') {
            $avg = round(100 * $sum / $count, 1);
            $output .= '<div class="oscars-leaderboard-avg">Average: ' . $avg . '%</div>';
        } else {
            $avg = round($sum / $count, 1);
            $output .= '<div class="oscars-leaderboard-avg">Average: ' . $avg . '</div>';
        }
    }
    return $output;
}