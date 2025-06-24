<?php

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
