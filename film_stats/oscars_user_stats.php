<?php
/**
 * Shortcode: oscars_user_stats
 * Outputs user stats (total watched, favourites, predictions, last updated).
 */
function oscars_user_stats_shortcode() {
    if (!is_user_logged_in()) return '<p>Please log in to view your stats.</p>';
    $user_id = get_current_user_id();
    $file_path = wp_upload_dir()['basedir'] . "/user_meta/user_{$user_id}.json";
    if (!file_exists($file_path)) return '<p>No user data found.</p>';
    $data = json_decode(file_get_contents($file_path), true);
    if (!$data) return '<p>User data is invalid.</p>';
    $output = '<div class="oscars-user-stats">';
    $output .= '<strong>Total Watched:</strong> ' . intval($data['total-watched'] ?? 0) . '<br>';
    $output .= '<strong>Favourites:</strong> ' . count($data['favourites'] ?? []) . '<br>';
    $output .= '<strong>Predictions:</strong> ' . count($data['predictions'] ?? []) . '<br>';
    $output .= '<strong>Last Updated:</strong> ' . esc_html($data['last-updated'] ?? '') . '<br>';
    $output .= '</div>';
    return $output;
}
add_shortcode('oscars_user_stats', 'oscars_user_stats_shortcode');
