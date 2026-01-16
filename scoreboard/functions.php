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
    // Check if we're on the scoreboard page - multiple conditions for reliability
    $is_scoreboard_page = is_page_template('page-scoreboard.php') || 
                          is_page('scoreboard') || 
                          (is_page() && get_post_field('post_name') === 'scoreboard');
    
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
