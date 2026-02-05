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
    
    $file_path = ABSPATH . 'wp-content/uploads/scoreboard_settings.json';
    
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
 * Update admin reload interval
 */
function scoreboard_update_admin_interval() {
    // Verify nonce if provided
    if (isset($_POST['nonce']) && !wp_verify_nonce($_POST['nonce'], 'scoreboard_nonce')) {
        wp_send_json_error('Invalid nonce');
    }
    
    $interval = isset($_POST['interval']) ? intval($_POST['interval']) : 5;
    
    if ($interval < 1) {
        wp_send_json_error('Interval must be at least 1 second');
    }
    
    $file_path = ABSPATH . 'wp-content/uploads/scoreboard_settings.json';
    
    // Read existing data
    $data = ['admin_interval' => 5, 'scoreboard_interval' => 10, 'notices' => []];
    if (file_exists($file_path)) {
        $content = file_get_contents($file_path);
        $decoded = json_decode($content, true);
        if (is_array($decoded)) {
            $data = $decoded;
            // Ensure both intervals exist
            if (!isset($data['admin_interval'])) {
                $data['admin_interval'] = isset($data['interval']) ? $data['interval'] : 5;
            }
            if (!isset($data['scoreboard_interval'])) {
                $data['scoreboard_interval'] = 10;
            }
        }
    }
    
    // Update admin interval
    $data['admin_interval'] = $interval;
    
    // Save to file
    file_put_contents($file_path, json_encode($data, JSON_PRETTY_PRINT));
    
    wp_send_json_success($data);
}
add_action('wp_ajax_scoreboard_update_admin_interval', 'scoreboard_update_admin_interval');
add_action('wp_ajax_nopriv_scoreboard_update_admin_interval', 'scoreboard_update_admin_interval');

/**
 * Update scoreboard reload interval
 */
function scoreboard_update_scoreboard_interval() {
    // Verify nonce if provided
    if (isset($_POST['nonce']) && !wp_verify_nonce($_POST['nonce'], 'scoreboard_nonce')) {
        wp_send_json_error('Invalid nonce');
    }
    
    $interval = isset($_POST['interval']) ? intval($_POST['interval']) : 10;
    
    if ($interval < 1) {
        wp_send_json_error('Interval must be at least 1 second');
    }
    
    $file_path = ABSPATH . 'wp-content/uploads/scoreboard_settings.json';
    
    // Read existing data
    $data = ['admin_interval' => 5, 'scoreboard_interval' => 10, 'notices' => []];
    if (file_exists($file_path)) {
        $content = file_get_contents($file_path);
        $decoded = json_decode($content, true);
        if (is_array($decoded)) {
            $data = $decoded;
            // Ensure both intervals exist
            if (!isset($data['admin_interval'])) {
                $data['admin_interval'] = isset($data['interval']) ? $data['interval'] : 5;
            }
            if (!isset($data['scoreboard_interval'])) {
                $data['scoreboard_interval'] = 10;
            }
        }
    }
    
    // Update scoreboard interval
    $data['scoreboard_interval'] = $interval;
    
    // Save to file
    file_put_contents($file_path, json_encode($data, JSON_PRETTY_PRINT));
    
    wp_send_json_success($data);
}
add_action('wp_ajax_scoreboard_update_scoreboard_interval', 'scoreboard_update_scoreboard_interval');
add_action('wp_ajax_nopriv_scoreboard_update_scoreboard_interval', 'scoreboard_update_scoreboard_interval');

/**
 * Update event status
 */
function scoreboard_update_event_status() {
    // Verify nonce if provided
    if (isset($_POST['nonce']) && !wp_verify_nonce($_POST['nonce'], 'scoreboard_nonce')) {
        wp_send_json_error('Invalid nonce');
    }
    
    $status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : 'welcome';
    
    // Validate status value
    $valid_statuses = ['welcome', 'in-progress', 'finished'];
    if (!in_array($status, $valid_statuses)) {
        wp_send_json_error('Invalid status value');
    }
    
    $file_path = ABSPATH . 'wp-content/uploads/scoreboard_settings.json';
    
    // Read existing data
    $data = ['admin_interval' => 5, 'scoreboard_interval' => 10, 'notices' => [], 'event_status' => 'welcome'];
    if (file_exists($file_path)) {
        $content = file_get_contents($file_path);
        $decoded = json_decode($content, true);
        if (is_array($decoded)) {
            $data = $decoded;
        }
    }
    
    // Update event status
    $data['event_status'] = $status;
    
    // Save to file
    file_put_contents($file_path, json_encode($data, JSON_PRETTY_PRINT));
    
    wp_send_json_success($data);
}
add_action('wp_ajax_scoreboard_update_event_status', 'scoreboard_update_event_status');
add_action('wp_ajax_nopriv_scoreboard_update_event_status', 'scoreboard_update_event_status');

/**
 * Get all notices from JSON file
 */
function scoreboard_get_notices() {
    // Verify nonce if provided
    if (isset($_POST['nonce']) && !wp_verify_nonce($_POST['nonce'], 'scoreboard_nonce')) {
        wp_send_json_error('Invalid nonce');
    }
    
    $file_path = ABSPATH . 'wp-content/uploads/scoreboard_settings.json';
    
    $data = ['admin_interval' => 5, 'scoreboard_interval' => 10, 'notices' => []];
    if (file_exists($file_path)) {
        $content = file_get_contents($file_path);
        $decoded = json_decode($content, true);
        if (is_array($decoded)) {
            $data = $decoded;
            // Ensure both intervals exist for backwards compatibility
            if (!isset($data['admin_interval'])) {
                $data['admin_interval'] = isset($data['interval']) ? $data['interval'] : 5;
            }
            if (!isset($data['scoreboard_interval'])) {
                $data['scoreboard_interval'] = 10;
            }
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
    
    $file_path = ABSPATH . 'wp-content/uploads/scoreboard_settings.json';
    
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
    
    $file_path = ABSPATH . 'wp-content/uploads/scoreboard_settings.json';
    
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

/**
 * Get friend predictions from pred_fav files
 */
function scoreboard_get_friend_predictions() {
    // Verify nonce if provided
    if (isset($_POST['nonce']) && !wp_verify_nonce($_POST['nonce'], 'scoreboard_nonce')) {
        wp_send_json_error('Invalid nonce');
    }
    
    if (!is_user_logged_in()) {
        wp_send_json_error('Not logged in');
    }
    
    $current_user_id = get_current_user_id();
    $friends = friends_get_friend_user_ids($current_user_id);
    
    $predictions_data = array();
    
    // Base path to user_meta folder in uploads directory
    $upload_dir = wp_upload_dir();
    $base_path = $upload_dir['basedir'] . '/user_meta/';
    
    // Get predictions for current user
    $current_user_file = $base_path . 'user_' . $current_user_id . '_pred_fav.json';
    if (file_exists($current_user_file)) {
        $content = file_get_contents($current_user_file);
        $data = json_decode($content, true);
        if (is_array($data)) {
            $predictions_data[$current_user_id] = array(
                'predictions' => isset($data['predictions']) ? $data['predictions'] : array(),
                'favourites' => isset($data['favourites']) ? $data['favourites'] : array()
            );
        }
    }
    
    // Get predictions for friends
    if ($friends) {
        foreach ($friends as $friend_id) {
            $friend_file = $base_path . 'user_' . $friend_id . '_pred_fav.json';
            if (file_exists($friend_file)) {
                $content = file_get_contents($friend_file);
                $data = json_decode($content, true);
                if (is_array($data)) {
                    $predictions_data[$friend_id] = array(
                        'predictions' => isset($data['predictions']) ? $data['predictions'] : array(),
                        'favourites' => isset($data['favourites']) ? $data['favourites'] : array()
                    );
                }
            }
        }
    }
    
    wp_send_json_success($predictions_data);
}
add_action('wp_ajax_scoreboard_get_friend_predictions', 'scoreboard_get_friend_predictions');
add_action('wp_ajax_nopriv_scoreboard_get_friend_predictions', 'scoreboard_get_friend_predictions');

/**
 * Set active category for 2026 results
 */
function scoreboard_set_active_category() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'scoreboard_nonce')) {
        wp_send_json_error('Invalid nonce');
    }
    
    $category_slug = isset($_POST['category_slug']) ? sanitize_text_field($_POST['category_slug']) : '';
    
    if (empty($category_slug)) {
        wp_send_json_error('Category slug is required');
    }
    
    // Path to 2026-results.json in user-meta folder - using ABSPATH pattern like rest of theme
    $file_path = ABSPATH . 'wp-content/uploads/2026-results.json';
    
    // Debug: Check if file exists and is writable
    if (!file_exists($file_path)) {
        wp_send_json_error('File does not exist: ' . $file_path);
    }
    
    if (!is_writable($file_path)) {
        wp_send_json_error('File is not writable: ' . $file_path . ' (permissions: ' . substr(sprintf('%o', fileperms($file_path)), -4) . ')');
    }
    
    // Read existing data or create new structure
    $data = [
        'last_updated' => '',
        'active_category' => '',
        'past_categories' => []
    ];
    
    if (file_exists($file_path)) {
        $content = file_get_contents($file_path);
        error_log('2026-results.json BEFORE write: ' . $content);
        $decoded = json_decode($content, true);
        if (is_array($decoded)) {
            $data = $decoded;
        }
    }
    
    // Update active category and timestamp
    $data['active_category'] = $category_slug;
    $data['last_updated'] = current_time('mysql');
    
    error_log('Data to be written: ' . print_r($data, true));
    
    // Save to file
    $json_string = json_encode($data, JSON_PRETTY_PRINT);
    error_log('JSON string to write: ' . $json_string);
    $bytes_written = file_put_contents($file_path, $json_string);
    error_log('Bytes written: ' . $bytes_written);
    
    // Verify the write
    if (file_exists($file_path)) {
        $verify_content = file_get_contents($file_path);
        error_log('2026-results.json AFTER write: ' . $verify_content);
    }
    
    if ($bytes_written === false) {
        wp_send_json_error('Failed to write to file: ' . $file_path . ' (bytes: ' . $bytes_written . ')');
    }
    
    wp_send_json_success([
        'message' => 'Category activated successfully',
        'data' => $data,
        'bytes_written' => $bytes_written,
        'file_path' => $file_path
    ]);
}
add_action('wp_ajax_scoreboard_set_active_category', 'scoreboard_set_active_category');

/**
 * Get current category display HTML
 */
function scoreboard_get_current_category() {
    // Verify nonce if provided
    if (isset($_POST['nonce']) && !wp_verify_nonce($_POST['nonce'], 'scoreboard_nonce')) {
        wp_send_json_error('Invalid nonce');
    }
    
    // Start output buffering
    ob_start();
    
    // Read the JSON file to get the current category
    $json_file = ABSPATH . 'wp-content/uploads/2026-results.json';
    $current_category_slug = '';
    $current_category_name = '';
    
    if (file_exists($json_file)) {
        $json_data = json_decode(file_get_contents($json_file), true);
        $current_category_slug = isset($json_data['active_category']) ? $json_data['active_category'] : '';
        
        if ($current_category_slug) {
            // Get the category term
            $category_term = get_term_by('slug', $current_category_slug, 'award-categories');
            if ($category_term && !is_wp_error($category_term)) {
                $current_category_name = $category_term->name;
                
                // Query nominations for this category in 2026
                $args = array(
                    'post_type' => 'nominations',
                    'order' => 'ASC',
                    'orderby' => 'name',
                    'posts_per_page' => -1,
                    'date_query' => array(
                        array(
                            'year' => 2026,
                        ),
                    ),
                    'tax_query' => array(
                        array(
                            'taxonomy' => 'award-categories',
                            'field' => 'slug',
                            'terms' => $current_category_slug,
                        ),
                    ),
                );
                
                $nominations_query = new WP_Query($args);
                
                // Determine nominee visibility based on category
                $nominee_visibility = "hidden";
                if (in_array($current_category_name, ["Actor in a Leading Role", "Actor in a Supporting Role", "Actress in a Leading Role", "Actress in a Supporting Role"])) {
                    $nominee_visibility = "prominent";
                } elseif (in_array($current_category_name, ["Directing", "Music (Original Song)", "Music (Original Score)"])) {
                    $nominee_visibility = "shown";
                }
                
                $is_song = ($current_category_name == "Music (Original Song)");
                
                echo '<div class="current-category-display">';
                echo '<h3 class="category-title">' . esc_html($current_category_name) . '</h3>';
                
                if ($nominations_query->have_posts()) {
                    echo '<ul class="nominations-list nominee_visibility-' . $nominee_visibility . '">';
                    
                    while ($nominations_query->have_posts()) {
                        $nominations_query->the_post();
                        
                        $films = get_the_terms(get_the_ID(), 'films');
                        if (is_array($films) || is_object($films)) {
                            foreach ($films as $film) {
                                $nominees = get_the_terms(get_the_ID(), 'nominees');
                                $categories = get_the_terms(get_the_ID(), 'award-categories');
                                
                                $winner = '';
                                if ($categories && !is_wp_error($categories)) {
                                    foreach ($categories as $category) {
                                        if ($category->slug == "winner") {
                                            $winner = "winner";
                                        }
                                    }
                                }
                                
                                $nomination_id = get_the_ID();
                                
                                echo '<li id="nomination-' . esc_attr($nomination_id) . '" class="' . $winner . ' film-id-' . $film->term_id;
                                if ($is_song) {
                                    echo ' song';
                                }
                                echo '" data-film-id="' . $film->term_id . '">';
                                
                                echo '<div class="left-section">';
                                echo '<span class="film-poster">';
                                $poster = get_field('poster', "films_" . $film->term_id);
                                
                                if ($poster) {
                                    echo '<img src="' . esc_url($poster) . '" alt="' . esc_attr($film->name) . '">';
                                } else {
                                    echo '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path d="M0 96C0 60.7 28.7 32 64 32l384 0c35.3 0 64 28.7 64 64l0 320c0 35.3-28.7 64-64 64L64 480c-35.3 0-64-28.7-64-64L0 96zM48 368l0 32c0 8.8 7.2 16 16 16l32 0c8.8 0 16-7.2 16-16l0-32c0-8.8-7.2-16-16-16l-32 0c-8.8 0-16 7.2-16 16zm368-16c-8.8 0-16 7.2-16 16l0 32c0 8.8 7.2 16 16 16l32 0c8.8 0 16-7.2 16-16l0-32c0-8.8-7.2-16-16-16l-32 0zM48 240l0 32c0 8.8 7.2 16 16 16l32 0c8.8 0 16-7.2 16-16l0-32c0-8.8-7.2-16-16-16l-32 0c-8.8 0-16 7.2-16 16zm368-16c-8.8 0-16 7.2-16 16l0 32c0 8.8 7.2 16 16 16l32 0c8.8 0 16-7.2 16-16l0-32c0-8.8-7.2-16-16-16l-32 0zM48 112l0 32c0 8.8 7.2 16 16 16l32 0c8.8 0 16-7.2 16-16l0-32c0-8.8-7.2-16-16-16L64 96c-8.8 0-16 7.2-16 16zM416 96c-8.8 0-16 7.2-16 16l0 32c0 8.8 7.2 16 16 16l32 0c8.8 0 16-7.2 16-16l0-32c0-8.8-7.2-16-16-16l-32 0zM160 128l0 64c0 17.7 14.3 32 32 32l128 0c17.7 0 32-14.3 32-32l0-64c0-17.7-14.3-32-32-32L192 96c-17.7 0-32 14.3-32 32zm32 160c-17.7 0-32 14.3-32 32l0 64c0 17.7 14.3 32 32 32l128 0c17.7 0 32-14.3 32-32l0-64c0-17.7-14.3-32-32-32l-128 0z"/></svg>';
                                }
                                echo '</span>';
                                
                                if ($nominee_visibility == "prominent") {
                                    if (is_array($nominees)) {
                                        foreach ($nominees as $nominee) {
                                            echo '<span class="nominee-photo">';
                                            $photo = get_field('photo', "nominees_" . $nominee->term_id);
                                            
                                            if (is_array($photo) && isset($photo['id'])) {
                                                echo wp_get_attachment_image($photo['id'], 'medium');
                                            } else {
                                                echo '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path d="M224 256A128 128 0 1 0 224 0a128 128 0 1 0 0 256zm-45.7 48C79.8 304 0 383.8 0 482.3C0 498.7 13.3 512 29.7 512l388.6 0c16.4 0 29.7-13.3 29.7-29.7C448 383.8 368.2 304 269.7 304l-91.4 0z"/></svg>';
                                            }
                                            echo '</span>';
                                        }
                                    }
                                }
                                echo '</div>'; // .left-section
                                
                                echo '<div class="right-section">';
                                if ($is_song) {
                                    echo '<h3 class="song-name">' . esc_html(get_the_title()) . '</h3>';
                                }
                                
                                if ($nominee_visibility == "prominent") {
                                    echo '<span class="film-name"><p>' . esc_html($film->name) . '</p></span>';
                                } elseif ($is_song) {
                                    echo '<span class="film-name"><h4>' . esc_html($film->name) . '</h4></span>';
                                } else {
                                    echo '<span class="film-name"><h3>' . esc_html($film->name) . '</h3></span>';
                                }
                                
                                if (!$is_song) {
                                    echo '<ul class="nominees-name">';
                                    if (is_array($nominees)) {
                                        foreach ($nominees as $nominee) {
                                            if ($nominee_visibility == "prominent") {
                                                echo '<li><span class="nominee-name"><h3>' . esc_html($nominee->name) . '</h3></span></li>';
                                            } elseif ($nominee_visibility == "shown") {
                                                echo '<li><span class="nominee-name">' . esc_html($nominee->name) . '</span></li>';
                                            }
                                        }
                                    }
                                    echo '</ul>';
                                }
                                
                                echo '</div>'; // .right-section
                                
                                echo '</li>';
                            }
                        }
                    }
                    
                    echo '</ul>';
                } else {
                    echo '<p>No nominations found for this category.</p>';
                }
                
                echo '</div>'; // .current-category-display
                
                wp_reset_postdata();
            } else {
                echo '<p>Category not found.</p>';
            }
        } else {
            echo '<p>No active category set.</p>';
        }
    } else {
        echo '<p>Results file not found.</p>';
    }
    
    $html = ob_get_clean();
    
    wp_send_json_success(['html' => $html]);
}
add_action('wp_ajax_scoreboard_get_current_category', 'scoreboard_get_current_category');
add_action('wp_ajax_nopriv_scoreboard_get_current_category', 'scoreboard_get_current_category');

/**
 * Complete current category - move it to past categories
 */
function scoreboard_complete_category() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'scoreboard_nonce')) {
        wp_send_json_error('Invalid nonce');
    }
    
    // Path to 2026-results.json in user-meta folder
    $file_path = ABSPATH . 'wp-content/uploads/2026-results.json';
    
    // Check if file exists
    if (!file_exists($file_path)) {
        wp_send_json_error('Results file does not exist: ' . $file_path);
    }
    
    if (!is_writable($file_path)) {
        wp_send_json_error('File is not writable: ' . $file_path);
    }
    
    // Read existing data
    $content = file_get_contents($file_path);
    $data = json_decode($content, true);
    
    if (!is_array($data)) {
        wp_send_json_error('Invalid JSON data in results file');
    }
    
    // Get the current active category
    $active_category = isset($data['active_category']) ? $data['active_category'] : '';
    
    if (empty($active_category)) {
        wp_send_json_error('No active category to complete');
    }
    
    // Initialize past_categories if it doesn't exist
    if (!isset($data['past_categories']) || !is_array($data['past_categories'])) {
        $data['past_categories'] = [];
    }
    
    // Calculate the order number: -(100 + count)
    // If this is the 1st category completed, order = -101
    // If this is the 2nd category completed, order = -100
    // If this is the 3rd category completed, order = -99
    $category_count = count($data['past_categories']);
    $order = -1 * (101 - $category_count);
    
    // Add the current category to past_categories with the calculated order
    $data['past_categories'][] = [
        'slug' => $active_category,
        'order' => $order,
        'completed_at' => current_time('mysql')
    ];
    
    // Clear the active category
    $data['active_category'] = '';
    
    // Update timestamp
    $data['last_updated'] = current_time('mysql');
    
    // Save to file
    $json_string = json_encode($data, JSON_PRETTY_PRINT);
    $bytes_written = file_put_contents($file_path, $json_string);
    
    if ($bytes_written === false) {
        wp_send_json_error('Failed to write to file: ' . $file_path);
    }
    
    wp_send_json_success([
        'message' => 'Category marked as complete successfully',
        'data' => $data,
        'completed_category' => $active_category,
        'order' => $order
    ]);
}
add_action('wp_ajax_scoreboard_complete_category', 'scoreboard_complete_category');

/**
 * Get upcoming categories HTML for dynamic refresh
 */
function scoreboard_get_upcoming_categories() {
    // Verify nonce if provided
    if (isset($_POST['nonce']) && !wp_verify_nonce($_POST['nonce'], 'scoreboard_nonce')) {
        wp_send_json_error('Invalid nonce');
    }
    
    // Start output buffering
    ob_start();
    
    // Read 2026-results.json to get past categories
    $json_file = ABSPATH . 'wp-content/uploads/2026-results.json';
    $past_category_slugs = [];
    if (file_exists($json_file)) {
        $json_data = json_decode(file_get_contents($json_file), true);
        if (isset($json_data['past_categories']) && is_array($json_data['past_categories'])) {
            foreach ($json_data['past_categories'] as $past_cat) {
                if (isset($past_cat['slug'])) {
                    $past_category_slugs[] = $past_cat['slug'];
                }
            }
        }
    }
    
    // Query nominations for 2026
    $nominations_2026 = get_posts([
        'post_type'      => 'nominations',
        'posts_per_page' => -1,
        'date_query'     => [
            [
                'year' => 2026,
            ],
        ],
    ]);

    // Extract unique category IDs from the nominations
    $category_ids = [];
    foreach ($nominations_2026 as $nomination) {
        $post_categories = wp_get_post_terms($nomination->ID, 'award-categories', ['fields' => 'ids']);
        $category_ids = array_merge($category_ids, $post_categories);
    }
    $category_ids = array_unique($category_ids);

    // Fetch the categories with those IDs
    $categories = get_terms([
        'taxonomy'   => 'award-categories',
        'hide_empty' => false,
        'include'    => $category_ids,
        'orderby'    => 'name',
        'order'      => 'ASC',
    ]);
    
    // Filter out past categories and 'winner' category
    $upcoming_categories = [];
    $completed_categories = [];
    if (!empty($categories) && !is_wp_error($categories)) {
        foreach ($categories as $category) {
            if ($category->slug === 'winner') continue;
            
            if (in_array($category->slug, $past_category_slugs)) {
                $completed_categories[] = $category;
            } else {
                $upcoming_categories[] = $category;
            }
        }
    }

    if (!empty($upcoming_categories)) :
    ?>
        <div id="category-control">
            <!-- <label for="category-select">Select New Current Category:</label> -->
             <form>
                <select id="category-select">
                    <option value="">-- Select a category --</option>
                    <?php foreach ($upcoming_categories as $category) : ?>
                        <option value="<?php echo esc_attr($category->term_id); ?>" data-slug="<?php echo esc_attr($category->slug); ?>">
                            <?php echo esc_html($category->name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="button" id="set-category-btn">Activate</button>
            </form>
        </div>
    <?php else : ?>
        <p>No upcoming categories found.</p>
    <?php endif; ?>
    
    <?php if (!empty($completed_categories)) : ?>
        <div id="completed-categories-display" style="margin-top: 20px;">
            <h3>Completed Categories</h3>
            <div class="completed-chips">
                <?php foreach ($completed_categories as $category) : ?>
                    <span class="category-chip" data-slug="<?php echo esc_attr($category->slug); ?>">
                        <?php echo esc_html($category->name); ?>
                    </span>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
    
    <?php
    $html = ob_get_clean();
    
    wp_send_json_success(['html' => $html]);
}
add_action('wp_ajax_scoreboard_get_upcoming_categories', 'scoreboard_get_upcoming_categories');
add_action('wp_ajax_nopriv_scoreboard_get_upcoming_categories', 'scoreboard_get_upcoming_categories');

/**
 * Mark a nomination as winner in 2026-results.json
 */
function scoreboard_mark_winner() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'scoreboard_nonce')) {
        wp_send_json_error('Invalid nonce');
    }
    
    $nomination_id = isset($_POST['nomination_id']) ? intval($_POST['nomination_id']) : 0;
    
    if (!$nomination_id) {
        wp_send_json_error('Nomination ID is required');
    }
    
    // Path to 2026-results.json
    $file_path = ABSPATH . 'wp-content/uploads/2026-results.json';
    
    // Check if file exists
    if (!file_exists($file_path)) {
        wp_send_json_error('Results file does not exist: ' . $file_path);
    }
    
    if (!is_writable($file_path)) {
        wp_send_json_error('File is not writable: ' . $file_path);
    }
    
    // Read existing data
    $content = file_get_contents($file_path);
    $data = json_decode($content, true);
    
    if (!is_array($data)) {
        wp_send_json_error('Invalid JSON data in results file');
    }
    
    // Initialize winners array if it doesn't exist
    if (!isset($data['winners']) || !is_array($data['winners'])) {
        $data['winners'] = [];
    }
    
    // Check if this nomination is already marked as winner
    if (in_array($nomination_id, $data['winners'])) {
        wp_send_json_error('This nomination is already marked as winner');
    }
    
    // Add the nomination ID to winners
    $data['winners'][] = $nomination_id;
    
    // Update timestamp
    $data['last_updated'] = current_time('mysql');
    
    // Save to file
    $json_string = json_encode($data, JSON_PRETTY_PRINT);
    $bytes_written = file_put_contents($file_path, $json_string);
    
    if ($bytes_written === false) {
        wp_send_json_error('Failed to write to file: ' . $file_path);
    }
    
    wp_send_json_success([
        'message' => 'Winner marked successfully',
        'data' => $data,
        'nomination_id' => $nomination_id
    ]);
}
add_action('wp_ajax_scoreboard_mark_winner', 'scoreboard_mark_winner');
/**
 * Toggle testing mode
 */
function scoreboard_toggle_testing() {
    // Verify nonce
    if (isset($_POST['nonce']) && !wp_verify_nonce($_POST['nonce'], 'scoreboard_nonce')) {
        wp_send_json_error('Invalid nonce');
    }
    
    $testing = isset($_POST['testing']) && $_POST['testing'] === '1';
    
    $file_path = ABSPATH . 'wp-content/uploads/scoreboard_settings.json';
    
    // Read existing data
    $data = ['notices' => [], 'admin_interval' => 10, 'scoreboard_interval' => 30, 'event_status' => 'welcome'];
    if (file_exists($file_path)) {
        $content = file_get_contents($file_path);
        $decoded = json_decode($content, true);
        if (is_array($decoded)) {
            $data = $decoded;
        }
    }
    
    // Update testing mode
    $data['testing'] = $testing;
    
    // Save to file
    file_put_contents($file_path, json_encode($data, JSON_PRETTY_PRINT));
    
    wp_send_json_success(['testing' => $testing]);
}
add_action('wp_ajax_scoreboard_toggle_testing', 'scoreboard_toggle_testing');

/**
 * Force refresh all clients
 */
function scoreboard_force_refresh() {
    // Verify nonce
    if (isset($_POST['nonce']) && !wp_verify_nonce($_POST['nonce'], 'scoreboard_nonce')) {
        wp_send_json_error('Invalid nonce');
    }
    
    $file_path = ABSPATH . 'wp-content/uploads/scoreboard_settings.json';
    
    // Read existing data
    $data = ['notices' => [], 'admin_interval' => 10, 'scoreboard_interval' => 30, 'event_status' => 'welcome'];
    if (file_exists($file_path)) {
        $content = file_get_contents($file_path);
        $decoded = json_decode($content, true);
        if (is_array($decoded)) {
            $data = $decoded;
        }
    }
    
    // Set force refresh timestamp to 10 seconds in the future
    $data['force_refresh'] = time() + 10;
    
    // Save to file
    file_put_contents($file_path, json_encode($data, JSON_PRETTY_PRINT));
    
    wp_send_json_success(['force_refresh' => $data['force_refresh']]);
}
add_action('wp_ajax_scoreboard_force_refresh', 'scoreboard_force_refresh');

/**
 * Reset 2026 results
 */
function scoreboard_reset_results() {
    // Verify nonce
    if (isset($_POST['nonce']) && !wp_verify_nonce($_POST['nonce'], 'scoreboard_nonce')) {
        wp_send_json_error('Invalid nonce');
    }
    
    $file_path = ABSPATH . 'wp-content/uploads/2026-results.json';
    
    // Reset to default structure
    $data = [
        'last_updated' => '',
        'active_category' => '',
        'past_categories' => [],
        'winners' => []
    ];
    
    // Save to file
    $result = file_put_contents($file_path, json_encode($data, JSON_PRETTY_PRINT));
    
    if ($result === false) {
        wp_send_json_error('Failed to write to file');
    }
    
    wp_send_json_success(['message' => '2026 results have been reset']);
}
add_action('wp_ajax_scoreboard_reset_results', 'scoreboard_reset_results');

/**
 * Reset scoreboard settings
 */
function scoreboard_reset_settings() {
    // Verify nonce
    if (isset($_POST['nonce']) && !wp_verify_nonce($_POST['nonce'], 'scoreboard_nonce')) {
        wp_send_json_error('Invalid nonce');
    }
    
    $file_path = ABSPATH . 'wp-content/uploads/scoreboard_settings.json';
    
    // Reset to default structure
    $data = [
        'notices' => [],
        'admin_interval' => 10,
        'scoreboard_interval' => 30,
        'event_status' => 'welcome'
    ];
    
    // Save to file
    $result = file_put_contents($file_path, json_encode($data, JSON_PRETTY_PRINT));
    
    if ($result === false) {
        wp_send_json_error('Failed to write to file');
    }
    
    wp_send_json_success(['message' => 'Scoreboard settings have been reset']);
}
add_action('wp_ajax_scoreboard_reset_settings', 'scoreboard_reset_settings');

/**
 * Save user prediction
 */
function scoreboard_save_prediction() {
    check_ajax_referer('scoreboard_nonce', 'nonce');
    
    if (!is_user_logged_in()) {
        wp_send_json_error('User not logged in');
        return;
    }
    
    $nomination_id = intval($_POST['nomination_id']);
    $category_slug = sanitize_text_field($_POST['category_slug']);
    $user_id = get_current_user_id();
    
    // Load user's JSON file
    $upload_dir = wp_upload_dir();
    $file_path = $upload_dir['basedir'] . '/user_meta/user_' . $user_id . '_pred_fav.json';
    
    if (file_exists($file_path)) {
        $json_data = json_decode(file_get_contents($file_path), true);
    } else {
        $user = wp_get_current_user();
        $json_data = [
            'username' => $user->display_name,
            'correct-predictions' => 0,
            'incorrect-predictions' => 0,
            'correct-prediction-rate' => 0,
            'predictions' => [],
            'favourites' => []
        ];
    }
    
    // Get all nominations for this category to find which to remove
    $args = array(
        'post_type' => 'nominations',
        'posts_per_page' => -1,
        'tax_query' => array(
            array(
                'taxonomy' => 'award-categories',
                'field' => 'slug',
                'terms' => $category_slug,
            ),
        ),
    );
    $category_nominations = get_posts($args);
    $category_nomination_ids = wp_list_pluck($category_nominations, 'ID');
    
    // Remove any existing predictions from this category
    $json_data['predictions'] = array_values(array_diff($json_data['predictions'], $category_nomination_ids));
    
    // Add the new prediction
    if (!in_array($nomination_id, $json_data['predictions'])) {
        $json_data['predictions'][] = $nomination_id;
    }
    
    // Ensure directory exists
    $dir = dirname($file_path);
    if (!file_exists($dir)) {
        mkdir($dir, 0755, true);
    }
    
    // Save back to file
    $result = file_put_contents($file_path, json_encode($json_data, JSON_PRETTY_PRINT));
    
    if ($result === false) {
        wp_send_json_error('Failed to save prediction');
        return;
    }
    
    wp_send_json_success(['message' => 'Prediction saved', 'data' => $json_data]);
}
add_action('wp_ajax_scoreboard_save_prediction', 'scoreboard_save_prediction');

/**
 * Save user favourite
 */
function scoreboard_save_favourite() {
    check_ajax_referer('scoreboard_nonce', 'nonce');
    
    if (!is_user_logged_in()) {
        wp_send_json_error('User not logged in');
        return;
    }
    
    $nomination_id = intval($_POST['nomination_id']);
    $category_slug = sanitize_text_field($_POST['category_slug']);
    $user_id = get_current_user_id();
    
    // Load user's JSON file
    $upload_dir = wp_upload_dir();
    $file_path = $upload_dir['basedir'] . '/user_meta/user_' . $user_id . '_pred_fav.json';
    
    if (file_exists($file_path)) {
        $json_data = json_decode(file_get_contents($file_path), true);
    } else {
        $user = wp_get_current_user();
        $json_data = [
            'username' => $user->display_name,
            'correct-predictions' => 0,
            'incorrect-predictions' => 0,
            'correct-prediction-rate' => 0,
            'predictions' => [],
            'favourites' => []
        ];
    }
    
    // Get all nominations for this category to find which to remove
    $args = array(
        'post_type' => 'nominations',
        'posts_per_page' => -1,
        'tax_query' => array(
            array(
                'taxonomy' => 'award-categories',
                'field' => 'slug',
                'terms' => $category_slug,
            ),
        ),
    );
    $category_nominations = get_posts($args);
    $category_nomination_ids = wp_list_pluck($category_nominations, 'ID');
    
    // Remove any existing favourites from this category
    $json_data['favourites'] = array_values(array_diff($json_data['favourites'], $category_nomination_ids));
    
    // Add the new favourite
    if (!in_array($nomination_id, $json_data['favourites'])) {
        $json_data['favourites'][] = $nomination_id;
    }
    
    // Ensure directory exists
    $dir = dirname($file_path);
    if (!file_exists($dir)) {
        mkdir($dir, 0755, true);
    }
    
    // Save back to file
    $result = file_put_contents($file_path, json_encode($json_data, JSON_PRETTY_PRINT));
    
    if ($result === false) {
        wp_send_json_error('Failed to save favourite');
        return;
    }
    
    wp_send_json_success(['message' => 'Favourite saved', 'data' => $json_data]);
}
add_action('wp_ajax_scoreboard_save_favourite', 'scoreboard_save_favourite');

// /**
//  * Compile all pred_fav usernames and IDs into one JSON file (runs every minute).
//  */
// function scoreboard_compile_pred_fav_user_index() {
//     $upload_dir = wp_upload_dir();
//     $base_dir = trailingslashit($upload_dir['basedir']) . 'user_meta/';

//     if (!is_dir($base_dir)) {
//         return;
//     }

//     $files = glob($base_dir . '*_pred_fav.json');
//     if ($files === false) {
//         return;
//     }

//     $users = array();

//     foreach ($files as $file_path) {
//         $filename = basename($file_path);

//         if (!preg_match('/user_(\d+)_pred_fav\.json$/', $filename, $matches)) {
//             continue;
//         }

//         $user_id = intval($matches[1]);
//         $content = file_get_contents($file_path);
//         if ($content === false) {
//             continue;
//         }

//         $json = json_decode($content, true);
//         if (!is_array($json) || empty($json['username'])) {
//             continue;
//         }

//         $users[$user_id] = array(
//             'user_id' => $user_id,
//             'username' => $json['username']
//         );
//     }

//     if (!empty($users)) {
//         ksort($users);
//     }

//     $output = array(
//         'generated_at' => current_time('Y-m-d H:i:s'),
//         'users' => array_values($users)
//     );

//     $output_file = trailingslashit($upload_dir['basedir']) . 'pred_fav_users.json';
//     file_put_contents($output_file, json_encode($output, JSON_PRETTY_PRINT));
// }

// // Add custom 1-minute cron schedule
// add_filter('cron_schedules', function($schedules) {
//     if (!isset($schedules['every_minute'])) {
//         $schedules['every_minute'] = array(
//             'interval' => 60,
//             'display'  => __('Every Minute')
//         );
//     }
//     return $schedules;
// });

// // Schedule 1-minute cron job for compiling pred_fav usernames
// add_action('wp', function() {
//     if (!wp_next_scheduled('scoreboard_compile_pred_fav_user_index')) {
//         wp_schedule_event(time(), 'every_minute', 'scoreboard_compile_pred_fav_user_index');
//     }
// });

// // Hook the compile function to the cron event
// add_action('scoreboard_compile_pred_fav_user_index', 'scoreboard_compile_pred_fav_user_index');