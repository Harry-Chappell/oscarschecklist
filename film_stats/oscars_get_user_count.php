<?php

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
