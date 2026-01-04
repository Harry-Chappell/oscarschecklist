<?php
/**
 * Quick Check - Swipeable Cards Feature
 * Hooked after footer for admin users only
 */

// Enqueue scripts and styles for admin users
function quick_check_enqueue_scripts() {
    if (!current_user_can('administrator')) {
        return;
    }
    
    wp_enqueue_style('quick-check-style', get_stylesheet_directory_uri() . '/quick_check/quick_check_beta.css', array(), time());
    wp_enqueue_script('quick-check-script', get_stylesheet_directory_uri() . '/quick_check/quick_check_beta.js', array(), time(), true);
}
add_action('wp_enqueue_scripts', 'quick_check_enqueue_scripts');

// Add Quick Check markup after footer for admin users only
function quick_check_after_footer() {
    if (!current_user_can('administrator')) {
        return;
    }
    
    ?>
    <!-- Full-screen modal -->
    <div class="quick-check-modal" style="display: none;">
        <div class="quick-check-modal-overlay"></div>
        <div class="quick-check-modal-content">
            <button class="quick-check-close" aria-label="Close Quick Check">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                    <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
                </svg>
            </button>
            
            <div class="quick-check-container" data-source="films">
                
                <!-- Progress rings -->
                <div class="quick-check-progress">
                    <div class="progress-ring-container">
                        <svg class="progress-ring" width="100" height="100">
                            <circle class="progress-ring-bg" cx="50" cy="50" r="40"/>
                            <circle class="progress-ring-fill" cx="50" cy="50" r="40" data-progress="watched"/>
                        </svg>
                        <div class="progress-ring-text">
                            <span class="progress-value" id="watched-count">0</span>
                            <span class="progress-label">Watched</span>
                        </div>
                    </div>
                    <div class="progress-ring-container">
                        <svg class="progress-ring" width="100" height="100">
                            <circle class="progress-ring-bg" cx="50" cy="50" r="40"/>
                            <circle class="progress-ring-fill" cx="50" cy="50" r="40" data-progress="categories"/>
                        </svg>
                        <div class="progress-ring-text">
                            <span class="progress-value" id="category-count">0</span>
                            <span class="progress-label">Categories</span>
                        </div>
                    </div>
                </div>
                
                <div class="quick-check-controls">
                    <button class="undo-button" style="opacity: 0.2;">
                        Undo
                    </button>
                    <div class="quick-check-counter-display"></div>
                </div>

                <div class="cards-wrapper">
                    <!-- Cards will be dynamically generated from film data or fallback to default cards -->
                </div>
                
                <div class="quick-check-instructions">
                    <p>Swipe, use arrow keys, or these buttons:</p>
                </div>
                
                <div class="quick-check-action-buttons">
                    <button class="action-button cross-button" aria-label="Not watched">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20">
                            <path d="M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z" fill="currentColor"/>
                        </svg>
                        <span class="button-text">Not Watched</span>
                    </button>
                    <button class="action-button tick-button" aria-label="Watched">
                        <span class="button-text">Watched</span>
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="20" height="20">
                            <path d="M12 4l-1.41 1.41L16.17 11H4v2h12.17l-5.58 5.59L12 20l8-8z" fill="currentColor"/>
                        </svg>
                    </button>
                </div>
                
                <div class="results hidden">
                    <h2>Results</h2>
                    <div class="results-list"></div>
                    <button class="reset-button">Start Over</button>
                </div>
            </div>
        </div>
    </div>
    <?php
}
add_action('wp_footer', 'quick_check_after_footer');
