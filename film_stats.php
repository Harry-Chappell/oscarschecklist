<?php

/**
 * Compile all films and watched counts, save as JSON, and provide a shortcode button to trigger.
 */
function oscars_compile_films_stats() {
    if (!current_user_can('manage_options')) {
        return 'You do not have permission.';
    }
    $films = get_terms([
        'taxonomy' => 'films',
        'hide_empty' => false,
    ]);
    if (empty($films) || is_wp_error($films)) {
        return 'No films found.';
    }
    $film_stats = [];
    // Build initial film array
    foreach ($films as $film) {
        // Get year from first nomination post (if any)
        $film_year = null;
        $nominations = new WP_Query([
            'post_type' => 'nominations',
            'posts_per_page' => 1,
            'tax_query' => [[
                'taxonomy' => 'films',
                'field' => 'term_id',
                'terms' => $film->term_id,
            ]],
            'orderby' => 'date',
            'order' => 'ASC',
        ]);
        if ($nominations->have_posts()) {
            $nom = $nominations->posts[0];
            $film_year = (int) date('Y', strtotime($nom->post_date));
        }
        wp_reset_postdata();
        $film_stats[$film->term_id] = [
            'film-id' => $film->term_id,
            'film-name' => $film->name,
            'film-year' => $film_year,
            'film-url' => get_term_link($film),
            'watched-count' => 0,
        ];
    }
    // Count watched per user
    $user_meta_dir = ABSPATH . 'wp-content/uploads/user_meta/';
    if (is_dir($user_meta_dir)) {
        foreach (glob($user_meta_dir . 'user_*_watched.json') as $file) {
            $json = file_get_contents($file);
            $data = json_decode($json, true);
            if (!empty($data['watched']) && is_array($data['watched'])) {
                foreach ($data['watched'] as $watched) {
                    $fid = $watched['film-id'] ?? null;
                    if ($fid && isset($film_stats[$fid])) {
                        $film_stats[$fid]['watched-count']++;
                    }
                }
            }
        }
    }
    // Save JSON
    $output_path = ABSPATH . 'wp-content/uploads/films_stats.json';
    file_put_contents($output_path, json_encode(array_values($film_stats), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    return 'Film stats JSON generated!';
}

function oscars_films_stats_button_shortcode() {
    $output = '';
    if (isset($_POST['oscars_films_stats_generate'])) {
        $output .= '<div>' . oscars_compile_films_stats() . '</div>';
    }
    $output .= '<form method="post"><button type="submit" name="oscars_films_stats_generate">Generate Films Stats JSON</button></form>';
    return $output;
}
add_shortcode('films_stats_button', 'oscars_films_stats_button_shortcode');

/**
 * Shortcode to output top 10 watched films, ordered by watched count, with links.
 */
function oscars_top10_watched_films_list_shortcode() {
    $output_path = ABSPATH . 'wp-content/uploads/films_stats.json';
    if (!file_exists($output_path)) {
        return '<p>No stats file found. Please generate it first.</p>';
    }
    $json = file_get_contents($output_path);
    $films = json_decode($json, true);
    if (!$films || !is_array($films)) {
        return '<p>Stats file is invalid.</p>';
    }
    // Filter to only watched films
    $watched_films = array_filter($films, function($film) {
        return isset($film['watched-count']) && $film['watched-count'] > 0;
    });
    // Sort by watched-count descending
    usort($watched_films, function($a, $b) {
        return $b['watched-count'] <=> $a['watched-count'];
    });
    $watched_films = array_slice($watched_films, 0, 10);
    if (empty($watched_films)) {
        return '<p>No films have been watched yet.</p>';
    }
    $output = '<ul class="oscars-watched-films-list">';
    foreach ($watched_films as $film) {
        $output .= '<li>';
        $output .= '<a href="' . esc_url($film['film-url']) . '" target="_blank">' . esc_html($film['film-name']) . '</a>';
        $output .= ' <span>(' . intval($film['watched-count']) . ')</span>';
        $output .= '</li>';
    }
    $output .= '</ul>';
    return $output;
}
add_shortcode('top10_watched_films', 'oscars_top10_watched_films_list_shortcode');

/**
 * Compile user stats (last-updated, total-watched, user id) for all users into a single JSON file.
 */
function oscars_compile_all_user_stats() {
    $user_meta_dir = ABSPATH . 'wp-content/uploads/user_meta/';
    $output = [];
    if (is_dir($user_meta_dir)) {
        foreach (glob($user_meta_dir . 'user_*_watched.json') as $watched_file) {
            if (preg_match('/user_(\d+)_watched\.json$/', $watched_file, $matches)) {
                $user_id = (int)$matches[1];
                $prefs_file = $user_meta_dir . "user_{$user_id}_prefs.json";
                $watched_data = json_decode(file_get_contents($watched_file), true);
                $prefs_data = file_exists($prefs_file) ? json_decode(file_get_contents($prefs_file), true) : [];
                $output[] = [
                    'user_id' => $user_id,
                    'last-updated' => $watched_data['last-updated'] ?? '',
                    'total-watched' => $watched_data['total-watched'] ?? 0,
                    'username' => $watched_data['username'] ?? ($prefs_data['username'] ?? ''),
                    'public' => $prefs_data['public'] ?? false,
                    'correct-predictions' => $prefs_data['correct-predictions'] ?? '',
                    'incorrect-predictions' => $prefs_data['incorrect-predictions'] ?? '',
                    'correct-prediction-rate' => $prefs_data['correct-prediction-rate'] ?? '',
                ];
            }
        }
    }
    $output_path = ABSPATH . 'wp-content/uploads/all_user_stats.json';
    file_put_contents($output_path, json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    return 'All user stats JSON generated!';
}

/**
 * Admin button/shortcode to trigger compiling all user stats JSON.
 */
function oscars_all_user_stats_button_shortcode() {
    if (!current_user_can('manage_options')) {
        return 'You do not have permission.';
    }
    $output = '';
    if (isset($_POST['oscars_all_user_stats_generate'])) {
        $output .= '<div>' . oscars_compile_all_user_stats() . '</div>';
    }
    $output .= '<form method="post"><button type="submit" name="oscars_all_user_stats_generate">Generate All User Stats JSON</button></form>';
    return $output;
}
add_shortcode('all_user_stats_button', 'oscars_all_user_stats_button_shortcode');

/**
 * Shortcode for user to toggle 'public' field in their user meta JSON.
 */
function oscars_publicise_data_checkbox_shortcode() {
    if (!is_user_logged_in()) {
        return '<p>Please log in to change your data publicity setting.</p>';
    }
    $user_id = get_current_user_id();
    $prefs_path = wp_upload_dir()['basedir'] . "/user_meta/user_{$user_id}_prefs.json";
    $public = false;
    if (file_exists($prefs_path)) {
        $data = json_decode(file_get_contents($prefs_path), true);
        $public = !empty($data['public']);
    }
    // Handle POST only for this user and this form
    if (
        isset($_POST['oscars_publicise_data_checkbox']) &&
        isset($_POST['oscars_publicise_data_form_user']) &&
        intval($_POST['oscars_publicise_data_form_user']) === $user_id
    ) {
        $public = !empty($_POST['oscars_publicise_data']);
        if (file_exists($prefs_path)) {
            $data = json_decode(file_get_contents($prefs_path), true);
            $data['public'] = $public;
            file_put_contents($prefs_path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        }
    }
    $checked = $public ? 'checked' : '';
    $output = '<form method="post" id="oscars-publicise-form">';
    $output .= '<input type="hidden" name="oscars_publicise_data_checkbox" value="1">';
    $output .= '<input type="hidden" name="oscars_publicise_data_form_user" value="' . esc_attr($user_id) . '">';
    $output .= '<label><input type="checkbox" name="oscars_publicise_data" value="1" ' . $checked . ' onchange="this.form.submit()"> Publicise my data</label>';
    $output .= '</form>';
    return $output;
}
add_shortcode('publicise_data_checkbox', 'oscars_publicise_data_checkbox_shortcode');

/**
 * Shortcode to display a leaderboard of users by total-watched, using all_user_stats.json.
 * Renamed to oscars_watched_leaderboard. Now includes average at the bottom.
 */
function oscars_watched_leaderboard_shortcode() {
    $output_path = ABSPATH . 'wp-content/uploads/all_user_stats.json';
    if (!file_exists($output_path)) {
        return '<p>No leaderboard data found. Please generate it first.</p>';
    }
    $json = file_get_contents($output_path);
    $users = json_decode($json, true);
    if (!$users || !is_array($users)) {
        return '<p>Leaderboard data is invalid.</p>';
    }
    // Sort by total-watched descending
    usort($users, function($a, $b) {
        return $b['total-watched'] <=> $a['total-watched'];
    });
    $output = '<ul class="oscars-leaderboard">';
    $last_score = null;
    $rank = 0;
    $display_rank = 0;
    $sum = 0;
    $count = 0;
    foreach ($users as $i => $user) {
        $rank++;
        if ($last_score !== $user['total-watched']) {
            $display_rank = $rank;
            $last_score = $user['total-watched'];
        }
        $suffix = 'th';
        if ($display_rank % 10 == 1 && $display_rank % 100 != 11) $suffix = 'st';
        elseif ($display_rank % 10 == 2 && $display_rank % 100 != 12) $suffix = 'nd';
        elseif ($display_rank % 10 == 3 && $display_rank % 100 != 13) $suffix = 'rd';
        $username = (!empty($user['public']) && !empty($user['username'])) ? esc_html($user['username']) : 'anonymous';
        $output .= '<li>' . $display_rank . $suffix . ': ' . $username . ' - ' . intval($user['total-watched']) . '</li>';
        $sum += intval($user['total-watched']);
        $count++;
    }
    $output .= '</ul>';
    if ($count > 0) {
        $avg = round($sum / $count, 1);
        $output .= '<div class="oscars-leaderboard-avg">Average: ' . $avg . '</div>';
    }
    return $output;
}
add_shortcode('oscars_watched_leaderboard', 'oscars_watched_leaderboard_shortcode');

/**
 * For every user, count correct/incorrect predictions and save to their JSON. Add a shortcode button for admins.
 */
function oscars_update_all_user_prediction_stats() {
    $user_meta_dir = ABSPATH . 'wp-content/uploads/user_meta/';
    if (!is_dir($user_meta_dir)) return 'User meta directory not found.';
    $count = 0;
    foreach (glob($user_meta_dir . 'user_*_prefs.json') as $prefs_file) {
        $prefs_data = json_decode(file_get_contents($prefs_file), true);
        if (!is_array($prefs_data) || empty($prefs_data['predictions']) || !is_array($prefs_data['predictions'])) continue;
        $correct = 0;
        $incorrect = 0;
        foreach ($prefs_data['predictions'] as $nom_id) {
            $cat_terms = wp_get_post_terms($nom_id, 'award-categories');
            if (is_wp_error($cat_terms) || empty($cat_terms)) {
                $incorrect++;
                continue;
            }
            $is_winner = false;
            foreach ($cat_terms as $term) {
                if ($term->slug === 'winner') {
                    $is_winner = true;
                    break;
                }
            }
            if ($is_winner) {
                $correct++;
            } else {
                $incorrect++;
            }
        }
        $prefs_data['correct-predictions'] = $correct;
        $prefs_data['incorrect-predictions'] = $incorrect;
        $total = $correct + $incorrect;
        $prefs_data['correct-prediction-rate'] = $total > 0 ? round($correct / $total, 3) : null;
        file_put_contents($prefs_file, json_encode($prefs_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        $count++;
    }
    return "Prediction stats updated for $count users.";
}

function oscars_update_all_user_prediction_stats_button_shortcode() {
    if (!current_user_can('manage_options')) {
        return 'You do not have permission.';
    }
    $output = '';
    if (isset($_POST['oscars_update_all_user_prediction_stats'])) {
        $output .= '<div>' . oscars_update_all_user_prediction_stats() . '</div>';
    }
    $output .= '<form method="post"><button type="submit" name="oscars_update_all_user_prediction_stats">Update All User Prediction Stats</button></form>';
    return $output;
}
add_shortcode('update_all_user_prediction_stats_button', 'oscars_update_all_user_prediction_stats_button_shortcode');

/**
 * Shortcode to display a leaderboard of users by correct predictions or correct prediction rate, with AJAX switch.
 */
function oscars_predictions_leaderboard_shortcode() {
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
            xhr.send('oscars_leaderboard_sort=' + encodeURIComponent(sort) + '&oscars_leaderboard_ajax=1');
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
        <?php echo oscars_predictions_leaderboard_inner(); ?>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('oscars_predictions_leaderboard', 'oscars_predictions_leaderboard_shortcode');

function oscars_predictions_leaderboard_inner() {
    $output_path = ABSPATH . 'wp-content/uploads/all_user_stats.json';
    $sort_by = isset($_POST['oscars_leaderboard_sort']) && $_POST['oscars_leaderboard_sort'] === 'rate' ? 'correct-prediction-rate' : 'correct-predictions';
    if (!file_exists($output_path)) {
        return '<p>No leaderboard data found. Please generate it first.</p>';
    }
    $json = file_get_contents($output_path);
    $users = json_decode($json, true);
    if (!$users || !is_array($users)) {
        return '<p>Leaderboard data is invalid.</p>';
    }
    usort($users, function($a, $b) use ($sort_by) {
        $a_val = isset($a[$sort_by]) && $a[$sort_by] !== '' ? $a[$sort_by] : 0;
        $b_val = isset($b[$sort_by]) && $b[$sort_by] !== '' ? $b[$sort_by] : 0;
        return $b_val <=> $a_val;
    });
    $last_score = null;
    $rank = 0;
    $display_rank = 0;
    $output = '<ul class="oscars-leaderboard">';
    $sum = 0;
    $count = 0;
    foreach ($users as $i => $user) {
        $rank++;
        $score = isset($user[$sort_by]) && $user[$sort_by] !== '' ? $user[$sort_by] : 0;
        if ($last_score !== $score) {
            $display_rank = $rank;
            $last_score = $score;
        }
        $suffix = 'th';
        if ($display_rank % 10 == 1 && $display_rank % 100 != 11) $suffix = 'st';
        elseif ($display_rank % 10 == 2 && $display_rank % 100 != 12) $suffix = 'nd';
        elseif ($display_rank % 10 == 3 && $display_rank % 100 != 13) $suffix = 'rd';
        $username = (!empty($user['public']) && !empty($user['username'])) ? esc_html($user['username']) : 'anonymous';
        $display_score = $sort_by === 'correct-prediction-rate' ? (is_numeric($score) ? (100 * $score) . '%' : 'N/A') : intval($score);
        $output .= '<li>' . $display_rank . $suffix . ': ' . $username . ' - ' . $display_score . '</li>';
        if ($sort_by === 'correct-prediction-rate' && is_numeric($score)) {
            $sum += $score;
            $count++;
        } elseif ($sort_by === 'correct-predictions') {
            $sum += intval($score);
            $count++;
        }
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

/**
 * Returns the number of users in all_user_stats.json.
 * @return int|false Number of users, or false if file missing/invalid.
 */
function oscars_get_user_count_shortcode() {
    $output_path = ABSPATH . 'wp-content/uploads/all_user_stats.json';
    if (!file_exists($output_path)) {
        return false;
    }
    $json = file_get_contents($output_path);
    $users = json_decode($json, true);
    if (!$users || !is_array($users)) {
        return false;
    }
    return count($users);
}
add_shortcode('oscars_get_user_count', 'oscars_get_user_count_shortcode');

/**
 * Shortcode: oscars_user_count_above_watched
 * Shows a number input and displays the count of users who have watched more than X films (AJAX, live update).
 */
function oscars_user_count_above_watched_shortcode() {
    ob_start();
    ?>
    <div id="oscars-user-count-above-watched-wrap">
        <label>Films watched more than: <input type="number" id="oscars-user-count-above-watched-input" value="0" min="0" style="width:60px"></label>
        <span id="oscars-user-count-above-watched-result"></span>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var input = document.getElementById('oscars-user-count-above-watched-input');
        var result = document.getElementById('oscars-user-count-above-watched-result');
        function updateCount() {
            var xhr = new XMLHttpRequest();
            xhr.open('POST', window.location.href, true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onload = function() {
                if (xhr.status === 200) {
                    var parser = new DOMParser();
                    var doc = parser.parseFromString(xhr.responseText, 'text/html');
                    var el = doc.querySelector('#oscars-user-count-above-watched-ajax');
                    if (el) result.textContent = el.textContent;
                }
            };
            xhr.send('oscars_user_count_above_watched=' + encodeURIComponent(input.value) + '&oscars_user_count_above_watched_ajax=1');
        }
        input.addEventListener('input', updateCount);
        updateCount();
    });
    </script>
    <span id="oscars-user-count-above-watched-ajax" style="display:none"><?php echo oscars_user_count_above_watched_inner(); ?></span>
    <?php
    return ob_get_clean();
}
add_shortcode('oscars_user_count_above_watched', 'oscars_user_count_above_watched_shortcode');

function oscars_user_count_above_watched_inner() {
    $output_path = ABSPATH . 'wp-content/uploads/all_user_stats.json';
    $threshold = isset($_POST['oscars_user_count_above_watched']) ? intval($_POST['oscars_user_count_above_watched']) : 0;
    if (!file_exists($output_path)) return '0';
    $json = file_get_contents($output_path);
    $users = json_decode($json, true);
    if (!$users || !is_array($users)) return '0';
    $count = 0;
    foreach ($users as $user) {
        if (isset($user['total-watched']) && intval($user['total-watched']) > $threshold) {
            $count++;
        }
    }
    return $count;
}

/**
 * Shortcode: oscars_user_count_above_correct
 * Shows a number input and displays the count of users with more than X correct predictions (AJAX, live update).
 */
function oscars_user_count_above_correct_shortcode() {
    ob_start();
    ?>
    <div id="oscars-user-count-above-correct-wrap">
        <label>Correct predictions more than: <input type="number" id="oscars-user-count-above-correct-input" value="0" min="0" style="width:60px"></label>
        <span id="oscars-user-count-above-correct-result"></span>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var input = document.getElementById('oscars-user-count-above-correct-input');
        var result = document.getElementById('oscars-user-count-above-correct-result');
        function updateCount() {
            var xhr = new XMLHttpRequest();
            xhr.open('POST', window.location.href, true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onload = function() {
                if (xhr.status === 200) {
                    var parser = new DOMParser();
                    var doc = parser.parseFromString(xhr.responseText, 'text/html');
                    var el = doc.querySelector('#oscars-user-count-above-correct-ajax');
                    if (el) result.textContent = el.textContent;
                }
            };
            xhr.send('oscars_user_count_above_correct=' + encodeURIComponent(input.value) + '&oscars_user_count_above_correct_ajax=1');
        }
        input.addEventListener('input', updateCount);
        updateCount();
    });
    </script>
    <span id="oscars-user-count-above-correct-ajax" style="display:none"><?php echo oscars_user_count_above_correct_inner(); ?></span>
    <?php
    return ob_get_clean();
}
add_shortcode('oscars_user_count_above_correct', 'oscars_user_count_above_correct_shortcode');

function oscars_user_count_above_correct_inner() {
    $output_path = ABSPATH . 'wp-content/uploads/all_user_stats.json';
    $threshold = isset($_POST['oscars_user_count_above_correct']) ? intval($_POST['oscars_user_count_above_correct']) : 0;
    if (!file_exists($output_path)) return '0';
    $json = file_get_contents($output_path);
    $users = json_decode($json, true);
    if (!$users || !is_array($users)) return '0';
    $count = 0;
    foreach ($users as $user) {
        if (isset($user['correct-predictions']) && intval($user['correct-predictions']) > $threshold) {
            $count++;
        }
    }
    return $count;
}

/**
 * Shortcode: oscars_user_count_above_rate
 * Shows a number input and displays the count of users with prediction rate above X% (AJAX, live update).
 */
function oscars_user_count_above_rate_shortcode() {
    ob_start();
    ?>
    <div id="oscars-user-count-above-rate-wrap">
        <label>Prediction rate above (%): <input type="number" id="oscars-user-count-above-rate-input" value="0" min="0" max="100" style="width:60px"></label>
        <span id="oscars-user-count-above-rate-result"></span>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var input = document.getElementById('oscars-user-count-above-rate-input');
        var result = document.getElementById('oscars-user-count-above-rate-result');
        function updateCount() {
            var xhr = new XMLHttpRequest();
            xhr.open('POST', window.location.href, true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onload = function() {
                if (xhr.status === 200) {
                    var parser = new DOMParser();
                    var doc = parser.parseFromString(xhr.responseText, 'text/html');
                    var el = doc.querySelector('#oscars-user-count-above-rate-ajax');
                    if (el) result.textContent = el.textContent;
                }
            };
            xhr.send('oscars_user_count_above_rate=' + encodeURIComponent(input.value) + '&oscars_user_count_above_rate_ajax=1');
        }
        input.addEventListener('input', updateCount);
        updateCount();
    });
    </script>
    <span id="oscars-user-count-above-rate-ajax" style="display:none"><?php echo oscars_user_count_above_rate_inner(); ?></span>
    <?php
    return ob_get_clean();
}
add_shortcode('oscars_user_count_above_rate', 'oscars_user_count_above_rate_shortcode');

function oscars_user_count_above_rate_inner() {
    $output_path = ABSPATH . 'wp-content/uploads/all_user_stats.json';
    $threshold = isset($_POST['oscars_user_count_above_rate']) ? floatval($_POST['oscars_user_count_above_rate']) : 0;
    if (!file_exists($output_path)) return '0';
    $json = file_get_contents($output_path);
    $users = json_decode($json, true);
    if (!$users || !is_array($users)) return '0';
    $count = 0;
    foreach ($users as $user) {
        if (isset($user['correct-prediction-rate']) && is_numeric($user['correct-prediction-rate']) && (100 * floatval($user['correct-prediction-rate'])) > $threshold) {
            $count++;
        }
    }
    return $count;
}

/**
 * Shortcode: oscars_user_count_active_days
 * Shows a number input and displays the count of users active in the last X days (AJAX, live update).
 */
function oscars_user_count_active_days_shortcode() {
    ob_start();
    ?>
    <div id="oscars-user-count-active-days-wrap">
        <label>Active in last <input type="number" id="oscars-user-count-active-days-input" value="7" min="1" style="width:60px"> days:</label>
        <span id="oscars-user-count-active-days-result"></span>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var input = document.getElementById('oscars-user-count-active-days-input');
        var result = document.getElementById('oscars-user-count-active-days-result');
        function updateCount() {
            var xhr = new XMLHttpRequest();
            xhr.open('POST', window.location.href, true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onload = function() {
                if (xhr.status === 200) {
                    var parser = new DOMParser();
                    var doc = parser.parseFromString(xhr.responseText, 'text/html');
                    var el = doc.querySelector('#oscars-user-count-active-days-ajax');
                    if (el) result.textContent = el.textContent;
                }
            };
            xhr.send('oscars_user_count_active_days=' + encodeURIComponent(input.value) + '&oscars_user_count_active_days_ajax=1');
        }
        input.addEventListener('input', updateCount);
        updateCount();
    });
    </script>
    <span id="oscars-user-count-active-days-ajax" style="display:none"><?php echo oscars_user_count_active_days_inner(); ?></span>
    <?php
    return ob_get_clean();
}
add_shortcode('oscars_user_count_active_days', 'oscars_user_count_active_days_shortcode');

function oscars_user_count_active_days_inner() {
    $output_path = ABSPATH . 'wp-content/uploads/all_user_stats.json';
    $days = isset($_POST['oscars_user_count_active_days']) ? intval($_POST['oscars_user_count_active_days']) : 7;
    if (!file_exists($output_path)) return '0';
    $json = file_get_contents($output_path);
    $users = json_decode($json, true);
    if (!$users || !is_array($users)) return '0';
    $count = 0;
    $now = time();
    foreach ($users as $user) {
        if (!empty($user['last-updated'])) {
            $last = strtotime($user['last-updated']);
            if ($last && ($now - $last) <= ($days * 86400)) {
                $count++;
            }
        }
    }
    return $count;
}

/**
 * Shortcode: oscars_active_users_barchart
 * Displays a bar chart of number of users last active in each of the last 52 weeks.
 * Uses Chart.js (loads from CDN if not present).
 */
function oscars_active_users_barchart_shortcode() {
    $output_path = ABSPATH . 'wp-content/uploads/all_user_stats.json';
    if (!file_exists($output_path)) {
        return '<p>No user stats data found.</p>';
    }
    $json = file_get_contents($output_path);
    $users = json_decode($json, true);
    if (!$users || !is_array($users)) {
        return '<p>User stats data is invalid.</p>';
    }
    // Prepare 52 weeks of bins (0 = this week, 51 = 51 weeks ago)
    $now = strtotime('today');
    $bins = array_fill(0, 52, 0);
    foreach ($users as $user) {
        if (!empty($user['last-updated'])) {
            $last = strtotime($user['last-updated']);
            if ($last) {
                $weeks_ago = floor(($now - $last) / (7 * 86400));
                if ($weeks_ago >= 0 && $weeks_ago < 52) {
                    $bins[$weeks_ago]++;
                }
            }
        }
    }
    // Prepare labels (week start dates)
    $labels = [];
    for ($i = 51; $i >= 0; $i--) {
        $week_start = strtotime("-{$i} week", $now);
        $labels[] = date('j M', $week_start);
    }
    // Reverse bins for left-to-right (oldest to newest)
    $bins = array_reverse($bins);
    $labels = array_reverse($labels);
    // Output chart container and Chart.js loader
    ob_start();
    ?>
    <canvas id="oscars-active-users-barchart" width="1000" height="300"></canvas>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var ctx = document.getElementById('oscars-active-users-barchart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($labels); ?>,
                datasets: [{
                    label: 'Users last active',
                    data: <?php echo json_encode($bins); ?>,
                    backgroundColor: 'rgba(54, 162, 235, 0.7)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: false },
                    title: { display: true, text: 'User Last Active by Week (past 52 weeks)' }
                },
                scales: {
                    x: { title: { display: true, text: 'Week (starting)' }, ticks: { maxTicksLimit: 13 } },
                    y: { beginAtZero: true, title: { display: true, text: 'Number of Users' } }
                }
            }
        });
    });
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('oscars_active_users_barchart', 'oscars_active_users_barchart_shortcode');

/**
 * Shortcode: oscars_user_watched_by_week_barchart
 * Shows a bar chart of how many films the current user watched in each of the last 52 weeks.
 * Uses Chart.js (loads from CDN if not present).
 */
function oscars_user_watched_by_week_barchart_shortcode() {
    if (!is_user_logged_in()) {
        return '<p>Please log in to see your watched films by week.</p>';
    }
    $user_id = get_current_user_id();
    $watched_path = wp_upload_dir()['basedir'] . "/user_meta/user_{$user_id}_watched.json";
    if (!file_exists($watched_path)) {
        return '<p>No data found for current user.</p>';
    }
    $json = file_get_contents($watched_path);
    $data = json_decode($json, true);
    if (!$data || empty($data['watched']) || !is_array($data['watched'])) {
        return '<p>No watched films data found.</p>';
    }
    // Prepare 52 weeks of bins (0 = this week, 51 = 51 weeks ago)
    $now = strtotime('today');
    $bins = array_fill(0, 52, 0);
    foreach ($data['watched'] as $film) {
        if (!empty($film['watched-date'])) {
            $watched = strtotime($film['watched-date']);
            if ($watched) {
                $weeks_ago = floor(($now - $watched) / (7 * 86400));
                if ($weeks_ago >= 0 && $weeks_ago < 52) {
                    $bins[$weeks_ago]++;
                }
            }
        }
    }
    // Prepare labels (week start dates)
    $labels = [];
    for ($i = 51; $i >= 0; $i--) {
        $week_start = strtotime("-{$i} week", $now);
        $labels[] = date('j M', $week_start);
    }
    // Reverse bins for left-to-right (oldest to newest)
    $bins = array_reverse($bins);
    $labels = array_reverse($labels);
    ob_start();
    ?>
    <canvas id="oscars-user-watched-by-week-barchart" width="1000" height="300"></canvas>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    function renderUserWatchedByWeekChart() {
        var canvas = document.getElementById('oscars-user-watched-by-week-barchart');
        if (!canvas) return;
        var ctx = canvas.getContext('2d');
        // Wait until canvas is visible and has width
        if (canvas.offsetWidth === 0 || canvas.offsetHeight === 0) {
            setTimeout(renderUserWatchedByWeekChart, 100);
            return;
        }
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($labels); ?>,
                datasets: [{
                    label: 'Films watched',
                    data: <?php echo json_encode($bins); ?>,
                    backgroundColor: 'rgba(255, 99, 132, 0.7)',
                    borderColor: 'rgba(255, 99, 132, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: false },
                    title: { display: true, text: 'Your Films Watched by Week (past 52 weeks)' }
                },
                scales: {
                    x: { title: { display: true, text: 'Week (starting)' }, ticks: { maxTicksLimit: 13 } },
                    y: { beginAtZero: true, title: { display: true, text: 'Films Watched' } }
                }
            }
        });
    }
    document.addEventListener('DOMContentLoaded', renderUserWatchedByWeekChart);
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('user_watched_by_week_barchart', 'oscars_user_watched_by_week_barchart_shortcode');

/**
 * Shortcode: oscars_user_stats
 * Outputs user stats (total watched, favourites, predictions, last updated).
 */
function oscars_user_stats_shortcode() {
    if (!is_user_logged_in()) return '<p>Please log in to view your stats.</p>';
    $user_id = get_current_user_id();
    $watched_path = wp_upload_dir()['basedir'] . "/user_meta/user_{$user_id}_watched.json";
    $prefs_path = wp_upload_dir()['basedir'] . "/user_meta/user_{$user_id}_prefs.json";
    if (!file_exists($watched_path) && !file_exists($prefs_path)) return '<p>No user data found.</p>';
    $watched = file_exists($watched_path) ? json_decode(file_get_contents($watched_path), true) : [];
    $prefs = file_exists($prefs_path) ? json_decode(file_get_contents($prefs_path), true) : [];
    $output = '<div class="oscars-user-stats">';
    $output .= '<strong>Total Watched:</strong> ' . intval($watched['total-watched'] ?? 0) . '<br>';
    $output .= '<strong>Favourites:</strong> ' . count($prefs['favourites'] ?? []) . '<br>';
    $output .= '<strong>Predictions:</strong> ' . count($prefs['predictions'] ?? []) . '<br>';
    $output .= '<strong>Last Updated:</strong> ' . esc_html($watched['last-updated'] ?? '') . '<br>';
    $output .= '</div>';
    return $output;
}
add_shortcode('oscars_user_stats', 'oscars_user_stats_shortcode');

/**
 * Shortcode: oscars_user_watched_chart
 * Outputs a bar chart of watched films by year for the current user.
 */
function oscars_user_watched_chart_shortcode() {
    if (!is_user_logged_in()) return '<p>Please log in to view your chart.</p>';
    $user_id = get_current_user_id();
    $watched_path = wp_upload_dir()['basedir'] . "/user_meta/user_{$user_id}_watched.json";
    if (!file_exists($watched_path)) return '<p>No user data found.</p>';
    $data = json_decode(file_get_contents($watched_path), true);
    if (!$data || empty($data['watched'])) return '<p>No watched films data found.</p>';
    $watched_by_year = [];
    // Find min/max year
    $min_year = 1929;
    $max_year = intval(date('Y'));
    // Count watched per year
    foreach ($data['watched'] as $film) {
        if (!empty($film['film-year'])) {
            $y = intval($film['film-year']);
            if (!isset($watched_by_year[$y])) $watched_by_year[$y] = 0;
            $watched_by_year[$y]++;
        }
    }
    // Fill in all years from 1929 to current year with 0 if missing
    for ($y = $min_year; $y <= $max_year; $y++) {
        if (!isset($watched_by_year[$y])) $watched_by_year[$y] = 0;
    }
    ksort($watched_by_year);
    $labels = array_keys($watched_by_year);
    $counts = array_values($watched_by_year);
    $chart_id = 'watchedchart';
    ob_start();
    ?>
    <canvas id="<?php echo esc_attr($chart_id); ?>" width="1000" height="300"></canvas>
    <script>
    (function(){
        function renderChart_<?php echo $chart_id; ?>() {
            var canvas = document.getElementById('<?php echo esc_js($chart_id); ?>');
            if (!canvas || canvas.offsetWidth === 0 || canvas.offsetHeight === 0) {
                setTimeout(renderChart_<?php echo $chart_id; ?>, 100);
                return;
            }
            if (typeof Chart === 'undefined') {
                var script = document.createElement('script');
                script.src = 'https://cdn.jsdelivr.net/npm/chart.js';
                script.onload = renderChart_<?php echo $chart_id; ?>;
                document.head.appendChild(script);
                return;
            }
            new Chart(canvas.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: <?php echo json_encode($labels); ?>,
                    datasets: [{
                        label: 'Films watched',
                        data: <?php echo json_encode($counts); ?>,
                        backgroundColor: '#c7a34e',
                        borderColor: '#c7a34e',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { display: false },
                        title: { display: true, text: 'Films Watched by Year' }
                    },
                    scales: {
                        x: { title: { display: true, text: 'Year' } },
                        y: { beginAtZero: true, title: { display: true, text: 'Films Watched' } }
                    }
                }
            });
        }
        document.addEventListener('DOMContentLoaded', renderChart_<?php echo $chart_id; ?>);
    })();
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('oscars_user_watched_chart', 'oscars_user_watched_chart_shortcode');

/**
 * Shortcode: oscars_user_watched_by_decade
 * Outputs a list of watched films by decade for the current user.
 */
function oscars_user_watched_by_decade_shortcode() {
    if (!is_user_logged_in()) return '<p>Please log in to view your watched films by decade.</p>';
    $user_id = get_current_user_id();
    $watched_path = wp_upload_dir()['basedir'] . "/user_meta/user_{$user_id}_watched.json";
    if (!file_exists($watched_path)) return '<p>No user data found.</p>';
    $data = json_decode(file_get_contents($watched_path), true);
    if (!$data || empty($data['watched'])) return '<p>No watched films data found.</p>';
    $by_decade = [];
    foreach ($data['watched'] as $film) {
        if (!empty($film['film-year'])) {
            $decade = floor($film['film-year'] / 10) * 10;
            $year = intval($film['film-year']);
            if (!isset($by_decade[$decade])) $by_decade[$decade] = [];
            if (!isset($by_decade[$decade][$year])) $by_decade[$decade][$year] = [];
            $by_decade[$decade][$year][] = [
                'name' => $film['film-name'],
                'url' => $film['film-url'] ?? ''
            ];
        }
    }
    krsort($by_decade); // Descending order (most recent decade first)
    $output = '<div class="oscars-watched-by-decade">';
    foreach ($by_decade as $decade => $years) {
        // Count total films in decade
        $decade_count = 0;
        foreach ($years as $films) { $decade_count += count($films); }
        $output .= '<details><summary><strong>' . $decade . 's</strong> (' . $decade_count . ')</summary><ul>';
        krsort($years); // Most recent year first
        foreach ($years as $year => $films) {
            $year_count = count($films);
            $output .= '<li><strong>' . $year . '</strong> (' . $year_count . '): <ul>';
            // Sort films alphabetically by name
            usort($films, function($a, $b) {
                return strcasecmp($a['name'], $b['name']);
            });
            foreach ($films as $film) {
                $url = esc_url('https://stage.oscarschecklist.com/films/' . ltrim($film['url'], '/'));
                $output .= '<li><a href="' . $url . '">' . esc_html($film['name']) . '</a></li>';
            }
            $output .= '</ul></li>';
        }
        $output .= '</ul></details>';
    }
    $output .= '</div>';
    return $output;
}
add_shortcode('oscars_user_watched_by_decade', 'oscars_user_watched_by_decade_shortcode');
