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
                    'correct-predictions' => $data['correct-predictions'] ?? '',
                    'incorrect-predictions' => $data['incorrect-predictions'] ?? '',
                    'correct-prediction-rate' => $data['correct-prediction-rate'] ?? '',
                ];
            }
        }
    }
    $output_path = ABSPATH . 'wp-content/uploads/all_user_stats.json';
    file_put_contents($output_path, json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    return 'All user stats JSON generated!';
}
// 1. Add a custom schedule for every minute
add_filter('cron_schedules', function($schedules) {
    $schedules['every_minute'] = [
        'interval' => 60,
        'display'  => __('Every Minute')
    ];
    return $schedules;
});

// 2. Schedule the event if not already scheduled
add_action('wp', function() {
    if (!wp_next_scheduled('oscars_compile_all_user_stats_cron')) {
        wp_schedule_event(time(), 'every_minute', 'oscars_compile_all_user_stats_cron');
    }
});

// 3. Hook your function to the event
add_action('oscars_compile_all_user_stats_cron', 'oscars_compile_all_user_stats');



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











require_once get_stylesheet_directory() . '/film_stats/publicise_data_checkbox.php';
require_once get_stylesheet_directory() . '/film_stats/oscars_watched_leaderboard.php';
require_once get_stylesheet_directory() . '/film_stats/update_all_user_prediction_stats_button.php';
require_once get_stylesheet_directory() . '/film_stats/oscars_predictions_leaderboard.php';
require_once get_stylesheet_directory() . '/film_stats/oscars_get_user_count.php';
require_once get_stylesheet_directory() . '/film_stats/oscars_user_count_above_watched.php';
require_once get_stylesheet_directory() . '/film_stats/oscars_user_count_above_correct.php';
require_once get_stylesheet_directory() . '/film_stats/oscars_user_count_above_rate.php';
require_once get_stylesheet_directory() . '/film_stats/oscars_user_count_active_days.php';
require_once get_stylesheet_directory() . '/film_stats/oscars_active_users_barchart.php';
require_once get_stylesheet_directory() . '/film_stats/user_watched_by_week_barchart.php';
require_once get_stylesheet_directory() . '/film_stats/oscars_log_user_activity.php';
require_once get_stylesheet_directory() . '/film_stats/oscars_user_stats.php';
require_once get_stylesheet_directory() . '/film_stats/oscars_user_watched_chart.php';
require_once get_stylesheet_directory() . '/film_stats/oscars_user_watched_by_decade.php';