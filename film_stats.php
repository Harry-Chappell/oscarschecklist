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
        foreach (glob($user_meta_dir . 'user_*.json') as $file) {
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
        foreach (glob($user_meta_dir . 'user_*.json') as $file) {
            if (preg_match('/user_(\d+)\.json$/', $file, $matches)) {
                $user_id = (int)$matches[1];
                $json = file_get_contents($file);
                $data = json_decode($json, true);
                $output[] = [
                    'user_id' => $user_id,
                    'last-updated' => $data['last-updated'] ?? '',
                    'total-watched' => $data['total-watched'] ?? 0,
                    'username' => $data['username'] ?? '',
                    'public' => $data['public'] ?? false,
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
    $file_path = wp_upload_dir()['basedir'] . "/user_meta/user_{$user_id}.json";
    $public = false;
    if (file_exists($file_path)) {
        $data = json_decode(file_get_contents($file_path), true);
        $public = !empty($data['public']);
    }
    // Handle POST only for this user and this form
    if (
        isset($_POST['oscars_publicise_data_checkbox']) &&
        isset($_POST['oscars_publicise_data_form_user']) &&
        intval($_POST['oscars_publicise_data_form_user']) === $user_id
    ) {
        $public = !empty($_POST['oscars_publicise_data']);
        if (file_exists($file_path)) {
            $data = json_decode(file_get_contents($file_path), true);
            $data['public'] = $public;
            file_put_contents($file_path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
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
 */
function oscars_leaderboard_shortcode() {
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
    }
    $output .= '</ul>';
    return $output;
}
add_shortcode('oscars_leaderboard', 'oscars_leaderboard_shortcode');

/**
 * For every user, count correct/incorrect predictions and save to their JSON. Add a shortcode button for admins.
 */
function oscars_update_all_user_prediction_stats() {
    $user_meta_dir = ABSPATH . 'wp-content/uploads/user_meta/';
    if (!is_dir($user_meta_dir)) return 'User meta directory not found.';
    $count = 0;
    foreach (glob($user_meta_dir . 'user_*.json') as $file) {
        $json = file_get_contents($file);
        $data = json_decode($json, true);
        if (!is_array($data) || empty($data['predictions']) || !is_array($data['predictions'])) continue;
        $correct = 0;
        $incorrect = 0;
        foreach ($data['predictions'] as $nom_id) {
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
        $data['correct-predictions'] = $correct;
        $data['incorrect-predictions'] = $incorrect;
        $total = $correct + $incorrect;
        $data['correct-prediction-rate'] = $total > 0 ? round($correct / $total, 3) : null;
        file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
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