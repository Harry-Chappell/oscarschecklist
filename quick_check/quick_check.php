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
    <div class="quick-check-container">
        <div class="cards-wrapper">
            <div class="card" data-card="5">
                <div class="card-content">
                    <h2>Card 5 / 5</h2>
                    <p>Swipe left or right</p>
                </div>
            </div>
            <div class="card" data-card="4">
                <div class="card-content">
                    <h2>Card 4 / 5</h2>
                    <p>Swipe left or right</p>
                </div>
            </div>
            <div class="card" data-card="3">
                <div class="card-content">
                    <h2>Card 3 / 5</h2>
                    <p>Swipe left or right</p>
                </div>
            </div>
            <div class="card" data-card="2">
                <div class="card-content">
                    <h2>Card 2 / 5</h2>
                    <p>Swipe left or right</p>
                </div>
            </div>
            <div class="card active" data-card="1">
                <div class="card-content">
                    <h2>Card 1 / 5</h2>
                    <p>Swipe left or right</p>
                </div>
            </div>
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
