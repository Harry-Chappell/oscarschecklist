<?php
/**
 * Quick Check - Swipeable Cards Feature
 * Hooked after footer for admin users only
 */

// Enqueue scripts and styles for all users
function quick_check_enqueue_scripts() {
    wp_enqueue_style('quick-check-style', get_stylesheet_directory_uri() . '/quick_check/quick_check_beta.css', array(), time());
    wp_enqueue_script('quick-check-script', get_stylesheet_directory_uri() . '/quick_check/quick_check_beta.js', array(), time(), true);
}
add_action('wp_enqueue_scripts', 'quick_check_enqueue_scripts');

// Add Quick Check markup after footer for all users
function quick_check_after_footer() {
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
            <button class="quick-check-filter-button" aria-label="Filter categories">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" fill="currentColor">
                    <path d="M3.9 54.9C10.5 40.9 24.5 32 40 32l432 0c15.5 0 29.5 8.9 36.1 22.9s4.6 30.5-5.2 42.5L320 320.9 320 448c0 12.1-6.8 23.2-17.7 28.6s-23.8 4.3-33.5-3l-64-48c-8.1-6-12.8-15.5-12.8-25.6l0-79.1L9.1 97.3C-.7 85.4-2.8 68.8 3.9 54.9z"/>
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
                
                <!-- Filter Modal -->
                <div class="quick-check-filter-modal" style="display: none;">
                    <div class="quick-check-filter-modal-content">
                        <div class="quick-check-filter-modal-header">
                            <h3>Filter by Categories</h3>
                            <button class="quick-check-filter-close" aria-label="Close filter">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
                                </svg>
                            </button>
                        </div>
                        <div class="quick-check-filter-modal-body">
                            <div class="quick-check-filter-actions">
                                <button class="select-all-categories">Select All</button>
                                <button class="deselect-all-categories">Deselect All</button>
                            </div>
                            <div class="quick-check-filter-categories">
                                <!-- Categories will be populated by JavaScript -->
                            </div>
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
