<?php
/**
 * Scoreboard Functions
 * 
 * All functions specific to the Scoreboard page
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Enqueue scoreboard styles and scripts
 */
function scoreboard_enqueue_assets() {
    // Check if we're on the scoreboard page or admin page - multiple conditions for reliability
    $is_scoreboard_page = is_page_template('page-scoreboard.php') || 
                          is_page_template('page-scoreboard-admin.php') ||
                          is_page('scoreboard') || 
                          is_page('scoreboard-admin') ||
                          (is_page() && get_post_field('post_name') === 'scoreboard') ||
                          (is_page() && get_post_field('post_name') === 'scoreboard-admin');
    
    if ($is_scoreboard_page) {
        // Enqueue stylesheet
        wp_enqueue_style(
            'scoreboard-styles',
            get_stylesheet_directory_uri() . '/scoreboard/style.css',
            array(),
            filemtime(get_stylesheet_directory() . '/scoreboard/style.css')
        );
        
        // Enqueue script (vanilla JavaScript - no jQuery dependency)
        wp_enqueue_script(
            'scoreboard-scripts',
            get_stylesheet_directory_uri() . '/scoreboard/scripts.js',
            array(),
            filemtime(get_stylesheet_directory() . '/scoreboard/scripts.js'),
            true
        );
        
        // Pass data to JavaScript
        wp_localize_script('scoreboard-scripts', 'scoreboardData', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('scoreboard_nonce')
        ));
    }
}
add_action('wp_enqueue_scripts', 'scoreboard_enqueue_assets');


// Add your custom scoreboard functions below this line

/**
 * Save notice to JSON file
 */
function scoreboard_save_notice() {
    // Verify nonce if provided
    if (isset($_POST['nonce']) && !wp_verify_nonce($_POST['nonce'], 'scoreboard_nonce')) {
        wp_send_json_error('Invalid nonce');
    }
    
    $message = isset($_POST['message']) ? sanitize_text_field($_POST['message']) : '';
    
    if (empty($message)) {
        wp_send_json_error('Message is required');
    }
    
    $file_path = get_stylesheet_directory() . '/scoreboard/testing.json';
    
    // Read existing data
    $data = ['interval' => 5, 'notices' => []];
    if (file_exists($file_path)) {
        $content = file_get_contents($file_path);
        $decoded = json_decode($content, true);
        if (is_array($decoded)) {
            $data = $decoded;
        }
    }
    
    // Add new notice
    $data['notices'][] = [
        'message' => $message,
        'timestamp' => time(),
        'formatted_time' => current_time('Y-m-d H:i:s')
    ];
    
    // Save to file
    file_put_contents($file_path, json_encode($data, JSON_PRETTY_PRINT));
    
    wp_send_json_success($data);
}
add_action('wp_ajax_scoreboard_save_notice', 'scoreboard_save_notice');
add_action('wp_ajax_nopriv_scoreboard_save_notice', 'scoreboard_save_notice');

/**
 * Update reload interval
 */
function scoreboard_update_interval() {
    // Verify nonce if provided
    if (isset($_POST['nonce']) && !wp_verify_nonce($_POST['nonce'], 'scoreboard_nonce')) {
        wp_send_json_error('Invalid nonce');
    }
    
    $interval = isset($_POST['interval']) ? intval($_POST['interval']) : 5;
    
    if ($interval < 1) {
        wp_send_json_error('Interval must be at least 1 second');
    }
    
    $file_path = get_stylesheet_directory() . '/scoreboard/testing.json';
    
    // Read existing data
    $data = ['interval' => 5, 'notices' => []];
    if (file_exists($file_path)) {
        $content = file_get_contents($file_path);
        $decoded = json_decode($content, true);
        if (is_array($decoded)) {
            $data = $decoded;
        }
    }
    
    // Update interval
    $data['interval'] = $interval;
    
    // Save to file
    file_put_contents($file_path, json_encode($data, JSON_PRETTY_PRINT));
    
    wp_send_json_success($data);
}
add_action('wp_ajax_scoreboard_update_interval', 'scoreboard_update_interval');
add_action('wp_ajax_nopriv_scoreboard_update_interval', 'scoreboard_update_interval');

/**
 * Get all notices from JSON file
 */
function scoreboard_get_notices() {
    // Verify nonce if provided
    if (isset($_POST['nonce']) && !wp_verify_nonce($_POST['nonce'], 'scoreboard_nonce')) {
        wp_send_json_error('Invalid nonce');
    }
    
    $file_path = get_stylesheet_directory() . '/scoreboard/testing.json';
    
    $data = ['interval' => 5, 'notices' => []];
    if (file_exists($file_path)) {
        $content = file_get_contents($file_path);
        $decoded = json_decode($content, true);
        if (is_array($decoded)) {
            $data = $decoded;
        }
    }
    
    wp_send_json_success($data);
}
add_action('wp_ajax_scoreboard_get_notices', 'scoreboard_get_notices');
add_action('wp_ajax_nopriv_scoreboard_get_notices', 'scoreboard_get_notices');

/**
 * Delete a specific notice by timestamp
 */
function scoreboard_delete_notice() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'scoreboard_nonce')) {
        wp_send_json_error('Invalid nonce');
    }
    
    $timestamp = isset($_POST['timestamp']) ? intval($_POST['timestamp']) : 0;
    
    if (!$timestamp) {
        wp_send_json_error('Timestamp is required');
    }
    
    $file_path = get_stylesheet_directory() . '/scoreboard/testing.json';
    
    $data = ['interval' => 5, 'notices' => []];
    if (file_exists($file_path)) {
        $content = file_get_contents($file_path);
        $decoded = json_decode($content, true);
        if (is_array($decoded)) {
            $data = $decoded;
        }
    }
    
    // Filter out the notice with matching timestamp
    $data['notices'] = array_values(array_filter($data['notices'], function($notice) use ($timestamp) {
        return $notice['timestamp'] !== $timestamp;
    }));
    
    // Save to file
    file_put_contents($file_path, json_encode($data, JSON_PRETTY_PRINT));
    
    wp_send_json_success($data);
}
add_action('wp_ajax_scoreboard_delete_notice', 'scoreboard_delete_notice');

/**
 * Clear all notices
 */
function scoreboard_clear_all_notices() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'scoreboard_nonce')) {
        wp_send_json_error('Invalid nonce');
    }
    
    $file_path = get_stylesheet_directory() . '/scoreboard/testing.json';
    
    $data = ['interval' => 5, 'notices' => []];
    if (file_exists($file_path)) {
        $content = file_get_contents($file_path);
        $decoded = json_decode($content, true);
        if (is_array($decoded)) {
            $data = $decoded;
            $data['notices'] = []; // Clear all notices
        }
    }
    
    // Save to file
    file_put_contents($file_path, json_encode($data, JSON_PRETTY_PRINT));
    
    wp_send_json_success($data);
}
add_action('wp_ajax_scoreboard_clear_all_notices', 'scoreboard_clear_all_notices');

/**
 * Get friend file sizes for pred_fav files
 */
function scoreboard_get_friend_file_sizes() {
    // Verify nonce if provided
    if (isset($_POST['nonce']) && !wp_verify_nonce($_POST['nonce'], 'scoreboard_nonce')) {
        wp_send_json_error('Invalid nonce');
    }
    
    if (!is_user_logged_in()) {
        wp_send_json_error('Not logged in');
    }
    
    $current_user_id = get_current_user_id();
    $friends = friends_get_friend_user_ids($current_user_id);
    
    $file_sizes = array();
    
    // Base path to user_meta folder in uploads directory
    $upload_dir = wp_upload_dir();
    $base_path = $upload_dir['basedir'] . '/user_meta/';
    
    // Get file size for current user
    $current_user_file = $base_path . 'user_' . $current_user_id . '_pred_fav.json';
    if (file_exists($current_user_file)) {
        $file_sizes[$current_user_id] = filesize($current_user_file);
    } else {
        $file_sizes[$current_user_id] = 0;
    }
    
    // Get file sizes for friends
    if ($friends) {
        foreach ($friends as $friend_id) {
            $friend_file = $base_path . 'user_' . $friend_id . '_pred_fav.json';
            if (file_exists($friend_file)) {
                $file_sizes[$friend_id] = filesize($friend_file);
            } else {
                $file_sizes[$friend_id] = 0;
            }
        }
    }
    
    wp_send_json_success($file_sizes);
}
add_action('wp_ajax_scoreboard_get_friend_file_sizes', 'scoreboard_get_friend_file_sizes');
add_action('wp_ajax_nopriv_scoreboard_get_friend_file_sizes', 'scoreboard_get_friend_file_sizes');
