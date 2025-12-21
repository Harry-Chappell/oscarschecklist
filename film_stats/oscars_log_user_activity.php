<?php

/**
 * Shortcode to display user activity log (admin only).
 */
function oscars_user_activity_log_shortcode() {
    if (!current_user_can('manage_options')) {
        return 'You do not have permission.';
    }
    $output_path = ABSPATH . 'wp-content/uploads/user_activity_log.json';
    if (!file_exists($output_path)) {
        return '<p>No activity log found.</p>';
    }
    $json = file_get_contents($output_path);
    $logs = json_decode($json, true);
    if (!$logs || !is_array($logs)) {
        return '<p>Activity log is invalid.</p>';
    }
    // Sort by timestamp descending
    usort($logs, function($a, $b) {
        return $b['timestamp'] <=> $a['timestamp'];
    });
    $output = '<table class="oscars-activity-log">';
    $output .= '<tr><th>User</th><th>Action</th><th>Timestamp</th></tr>';
    foreach ($logs as $log) {
        $user = esc_html($log['user'] ?? 'N/A');
        $action = esc_html($log['action'] ?? 'N/A');
        $timestamp = esc_html($log['timestamp'] ?? 'N/A');
        $output .= "<tr><td>$user</td><td>$action</td><td>$timestamp</td></tr>";
    }
    $output .= '</table>';
    return $output;
}
add_shortcode('oscars_user_activity_log', 'oscars_user_activity_log_shortcode');

/**
 * Log user activity (admin only).
 */
function oscars_log_user_activity($action) {
    if (!current_user_can('manage_options')) {
        return;
    }
    $log = [
        'user' => wp_get_current_user()->user_login,
        'action' => $action,
        'timestamp' => current_time('mysql'),
    ];
    $output_path = ABSPATH . 'wp-content/uploads/user_activity_log.json';
    $logs = [];
    if (file_exists($output_path)) {
        $json = file_get_contents($output_path);
        $logs = json_decode($json, true);
        if (!$logs || !is_array($logs)) {
            $logs = [];
        }
    }
    $logs[] = $log;
    file_put_contents($output_path, json_encode($logs));
}

// Example: log when a user updates their profile
add_action('personal_options_update', function($user_id) {
    oscars_log_user_activity('Updated profile: ' . $user_id);
});
add_action('edit_user_profile_update', function($user_id) {
    oscars_log_user_activity('Updated profile: ' . $user_id);
});