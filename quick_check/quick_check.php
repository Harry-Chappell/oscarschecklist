<?php
/**
 * Quick Check - Swipeable Cards Feature
 * Shortcode: [quick_check]
 */

// Register the shortcode
function quick_check_shortcode() {
    // Enqueue scripts and styles
    wp_enqueue_style('quick-check-style', get_stylesheet_directory_uri() . '/quick_check/quick_check.css', array(), time());
    wp_enqueue_script('quick-check-script', get_stylesheet_directory_uri() . '/quick_check/quick_check.js', array(), time(), true);
    
    ob_start();
    ?>
    <div class="quick-check-container" data-source="films">
        <div class="cards-wrapper">
            <!-- Cards will be dynamically generated from film data or fallback to default cards -->
        </div>
        
        <div class="results hidden">
            <h2>Results</h2>
            <div class="results-list"></div>
            <button class="reset-button">Start Over</button>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

add_shortcode('quick_check', 'quick_check_shortcode');
