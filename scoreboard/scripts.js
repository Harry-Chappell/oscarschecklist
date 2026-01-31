/**
 * Scoreboard Scripts
 * 
 * JavaScript functionality for the Scoreboard page
 * Vanilla JavaScript - No jQuery
 */

(function() {
    'use strict';
    
    let lastNoticeCount = 0;
    let pollingInterval = null;
    let countdownInterval = null;
    let adminInterval = 5; // Default to 5 seconds for admin
    let scoreboardInterval = 10; // Default to 10 seconds for scoreboard
    let currentInterval = 5; // Will be set based on page type
    let countdown = 5;
    let isAdminPage = false;
    
    // LocalStorage key for dismissed notices
    const DISMISSED_KEY = 'scoreboard_dismissed_notices';
    
    /**
     * Get list of dismissed notice timestamps from localStorage
     */
    function getDismissedNotices() {
        const dismissed = localStorage.getItem(DISMISSED_KEY);
        return dismissed ? JSON.parse(dismissed) : [];
    }
    
    /**
     * Add a notice timestamp to dismissed list
     */
    function dismissNotice(timestamp) {
        const dismissed = getDismissedNotices();
        if (!dismissed.includes(timestamp)) {
            dismissed.push(timestamp);
            localStorage.setItem(DISMISSED_KEY, JSON.stringify(dismissed));
        }
    }
    
    /**
     * Format timestamp to user's local timezone
     */
    function formatTimestamp(timestamp) {
        const date = new Date(timestamp * 1000); // Convert Unix timestamp to milliseconds
        return date.toLocaleString(undefined, {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            hour12: true
        });
    }
    
    /**
     * Initialize scoreboard functionality
     */
    function initScoreboard() {
        const form = document.getElementById('notice-form');
        const input = document.getElementById('notice-input');
        const adminIntervalInput = document.getElementById('admin-interval-input');
        const scoreboardIntervalInput = document.getElementById('scoreboard-interval-input');
        const clearAllBtn = document.getElementById('clear-all-notices');
        const eventStatusForm = document.getElementById('event-status-form');
        
        // Detect if we're on admin page
        isAdminPage = !!adminIntervalInput;
        
        // Set current interval based on page type
        currentInterval = isAdminPage ? adminInterval : scoreboardInterval;
        countdown = currentInterval;
        
        if (form && input) {
            form.addEventListener('submit', handleNoticeSubmit);
        }
        
        if (adminIntervalInput) {
            adminIntervalInput.addEventListener('change', handleAdminIntervalChange);
        }
        
        if (scoreboardIntervalInput) {
            scoreboardIntervalInput.addEventListener('change', handleScoreboardIntervalChange);
        }
        
        if (clearAllBtn) {
            clearAllBtn.addEventListener('click', handleClearAllNotices);
        }
        
        if (eventStatusForm) {
            eventStatusForm.addEventListener('submit', handleEventStatusSubmit);
        }
        
        // Danger zone controls
        const testingModeCheckbox = document.getElementById('testing-mode-checkbox');
        const forceRefreshBtn = document.getElementById('force-refresh-btn');
        const resetResultsBtn = document.getElementById('reset-results-btn');
        const resetSettingsBtn = document.getElementById('reset-settings-btn');
        
        if (testingModeCheckbox) {
            testingModeCheckbox.addEventListener('change', handleTestingModeChange);
        }
        
        if (forceRefreshBtn) {
            forceRefreshBtn.addEventListener('click', handleForceRefresh);
        }
        
        if (resetResultsBtn) {
            resetResultsBtn.addEventListener('click', handleResetResults);
        }
        
        if (resetSettingsBtn) {
            resetSettingsBtn.addEventListener('click', handleResetSettings);
        }
        
        // Load initial data
        loadData();
        
        // Load current category on initial load
        loadCurrentCategory();
        
        // Start countdown
        startCountdown();
    }
    
    /**
     * Start countdown timer
     */
    function startCountdown() {
        if (countdownInterval) clearInterval(countdownInterval);
        
        countdown = currentInterval;
        updateCountdown();
        restartProgressBar();
        
        countdownInterval = setInterval(() => {
            countdown--;
            updateCountdown();
            
            if (countdown <= 0) {
                loadData();
                countdown = currentInterval;
                restartProgressBar();
            }
        }, 1000);
    }
    
    /**
     * Restart progress bar animation with current interval
     */
    function restartProgressBar() {
        const progressBar = document.getElementById('progress-bar');
        if (!progressBar) return;
        
        // Remove and re-add the element to restart animation
        progressBar.style.animation = 'none';
        progressBar.offsetHeight; // Trigger reflow
        progressBar.style.animation = `progress-animation ${currentInterval}s linear`;
    }
    
    /**
     * Update countdown display
     */
    function updateCountdown() {
        const countdownEl = isAdminPage 
            ? document.getElementById('admin-countdown') 
            : document.getElementById('countdown');
        
        if (countdownEl) {
            countdownEl.textContent = countdown;
        }
    }
    
    /**
     * Handle admin interval change - save to server and update countdown
     */
    function handleAdminIntervalChange(e) {
        const newInterval = parseInt(e.target.value);
        if (newInterval > 0 && newInterval !== adminInterval) {
            const formData = new FormData();
            formData.append('action', 'scoreboard_update_admin_interval');
            formData.append('interval', newInterval);
            formData.append('nonce', scoreboardData.nonce);
            
            fetch(scoreboardData.ajaxurl, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    adminInterval = newInterval;
                    if (isAdminPage) {
                        currentInterval = adminInterval;
                        startCountdown();
                    }
                    console.log('Admin interval updated');
                }
            })
            .catch(error => {
                console.error('Error updating admin interval:', error);
            });
        }
    }
    
    /**
     * Handle scoreboard interval change - save to server
     */
    function handleScoreboardIntervalChange(e) {
        const newInterval = parseInt(e.target.value);
        if (newInterval > 0 && newInterval !== scoreboardInterval) {
            const formData = new FormData();
            formData.append('action', 'scoreboard_update_scoreboard_interval');
            formData.append('interval', newInterval);
            formData.append('nonce', scoreboardData.nonce);
            
            fetch(scoreboardData.ajaxurl, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    scoreboardInterval = newInterval;
                    if (!isAdminPage) {
                        currentInterval = scoreboardInterval;
                        startCountdown();
                    }
                    console.log('Scoreboard interval updated');
                }
            })
            .catch(error => {
                console.error('Error updating scoreboard interval:', error);
            });
        }
    }
    
    /**
     * Handle event status form submission
     */
    function handleEventStatusSubmit(e) {
        e.preventDefault();
        
        const select = document.getElementById('event-status-select');
        const status = select.value;
        
        if (!status) return;
        
        // Disable form while submitting
        select.disabled = true;
        const submitBtn = e.target.querySelector('button[type="submit"]');
        if (submitBtn) submitBtn.disabled = true;
        
        const formData = new FormData();
        formData.append('action', 'scoreboard_update_event_status');
        formData.append('status', status);
        formData.append('nonce', scoreboardData.nonce);
        
        fetch(scoreboardData.ajaxurl, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log('Event status updated to:', status);
                // Update the display immediately
                updateEventStatusDisplay(status);
            } else {
                console.error('Error saving event status:', data);
            }
        })
        .catch(error => {
            console.error('Error:', error);
        })
        .finally(() => {
            select.disabled = false;
            if (submitBtn) submitBtn.disabled = false;
        });
    }
    
    /**
     * Update event status display
     */
    function updateEventStatusDisplay(status) {
        const statusText = document.getElementById('current-event-status-text');
        if (statusText) {
            // Convert "in-progress" to "In Progress", etc.
            const displayText = status.split('-').map(word => 
                word.charAt(0).toUpperCase() + word.slice(1)
            ).join(' ');
            statusText.textContent = displayText;
        }
    }
    
    /**
     * Handle notice submission
     */
    function handleNoticeSubmit(e) {
        e.preventDefault();
        
        const input = document.getElementById('notice-input');
        const message = input.value.trim();
        
        if (!message) return;
        
        // Disable form while submitting
        input.disabled = true;
        
        const formData = new FormData();
        formData.append('action', 'scoreboard_save_notice');
        formData.append('message', message);
        formData.append('nonce', scoreboardData.nonce);
        
        fetch(scoreboardData.ajaxurl, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                input.value = '';
                // Don't manually reload - will update on next poll
            } else {
                console.error('Error saving notice:', data);
            }
        })
        .catch(error => {
            console.error('Error:', error);
        })
        .finally(() => {
            input.disabled = false;
            input.focus();
        });
    }
    
    /**
     * Handle deleting a specific notice
     */
    function handleDeleteNotice(timestamp) {
        const formData = new FormData();
        formData.append('action', 'scoreboard_delete_notice');
        formData.append('timestamp', timestamp);
        formData.append('nonce', scoreboardData.nonce);
        
        fetch(scoreboardData.ajaxurl, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Force reload to update both admin list and user-facing notices
                loadData();
            } else {
                console.error('Error deleting notice:', data);
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
    }
    
    /**
     * Handle clearing all notices
     */
    function handleClearAllNotices() {
        if (!confirm('Are you sure you want to delete ALL notices? This cannot be undone.')) {
            return;
        }
        
        const formData = new FormData();
        formData.append('action', 'scoreboard_clear_all_notices');
        formData.append('nonce', scoreboardData.nonce);
        
        fetch(scoreboardData.ajaxurl, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Force reload to update both admin list and user-facing notices
                loadData();
            } else {
                console.error('Error clearing notices:', data);
            }
        })
        .catch(error => {
            console.error('Error:', error);
        });
    }
    
    /**
     * Handle testing mode toggle
     */
    function handleTestingModeChange(e) {
        const isEnabled = e.target.checked;
        
        e.target.disabled = true;
        
        const formData = new FormData();
        formData.append('action', 'scoreboard_toggle_testing');
        formData.append('testing', isEnabled ? '1' : '0');
        formData.append('nonce', scoreboardData.nonce);
        
        fetch(scoreboardData.ajaxurl, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log('Testing mode:', isEnabled ? 'enabled' : 'disabled');
            } else {
                console.error('Error toggling testing mode:', data);
                // Revert checkbox on error
                e.target.checked = !isEnabled;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            // Revert checkbox on error
            e.target.checked = !isEnabled;
        })
        .finally(() => {
            e.target.disabled = false;
        });
    }
    
    /**
     * Handle force refresh button
     */
    function handleForceRefresh() {
        if (!confirm('This will force all scoreboard clients to refresh in 10 seconds. Continue?')) {
            return;
        }
        
        const btn = document.getElementById('force-refresh-btn');
        btn.disabled = true;
        btn.textContent = 'Sending refresh signal...';
        
        const formData = new FormData();
        formData.append('action', 'scoreboard_force_refresh');
        formData.append('nonce', scoreboardData.nonce);
        
        fetch(scoreboardData.ajaxurl, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Force refresh signal sent! All clients will refresh in 10 seconds.');
            } else {
                alert('Error sending refresh signal: ' + (data.data || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error sending refresh signal');
        })
        .finally(() => {
            btn.disabled = false;
            btn.textContent = 'Force Refresh All Clients';
        });
    }
    
    /**
     * Handle reset results button
     */
    function handleResetResults() {
        if (!confirm('⚠️ WARNING: This will RESET ALL 2026 results including winners and category progress.\n\nThis action CANNOT be undone!\n\nAre you absolutely sure?')) {
            return;
        }
        
        // Double confirmation for destructive action
        if (!confirm('Last chance! Click OK to permanently delete all 2026 results.')) {
            return;
        }
        
        const btn = document.getElementById('reset-results-btn');
        btn.disabled = true;
        btn.textContent = 'Resetting...';
        
        const formData = new FormData();
        formData.append('action', 'scoreboard_reset_results');
        formData.append('nonce', scoreboardData.nonce);
        
        fetch(scoreboardData.ajaxurl, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('2026 results have been reset successfully.');
                // Reload the page to reflect changes
                window.location.reload();
            } else {
                alert('Error resetting results: ' + (data.data || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error resetting results');
        })
        .finally(() => {
            btn.disabled = false;
            btn.textContent = 'Reset 2026 Results';
        });
    }
    
    /**
     * Handle reset settings button
     */
    function handleResetSettings() {
        if (!confirm('⚠️ WARNING: This will RESET ALL scoreboard settings including:\n- All notices\n- Reload intervals\n- Event status\n\nThis action CANNOT be undone!\n\nAre you absolutely sure?')) {
            return;
        }
        
        // Double confirmation for destructive action
        if (!confirm('Last chance! Click OK to permanently reset all settings.')) {
            return;
        }
        
        const btn = document.getElementById('reset-settings-btn');
        btn.disabled = true;
        btn.textContent = 'Resetting...';
        
        const formData = new FormData();
        formData.append('action', 'scoreboard_reset_settings');
        formData.append('nonce', scoreboardData.nonce);
        
        fetch(scoreboardData.ajaxurl, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Scoreboard settings have been reset successfully.');
                // Reload the page to reflect changes
                window.location.reload();
            } else {
                alert('Error resetting settings: ' + (data.data || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error resetting settings');
        })
        .finally(() => {
            btn.disabled = false;
            btn.textContent = 'Reset Scoreboard Settings';
        });
    }
    
    /**
     * Load data from server (notices and interval)
     */
    function loadData() {
        const formData = new FormData();
        formData.append('action', 'scoreboard_get_notices');
        formData.append('nonce', scoreboardData.nonce);
        
        fetch(scoreboardData.ajaxurl, {
            method: 'POST',
            body: formData
        })
        .then(response => {
            if (!response.ok) {
                return response.text().then(text => {
                    console.error('Server response:', text);
                    throw new Error(`HTTP ${response.status}: ${text}`);
                });
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                const serverData = data.data;
                
                // Update notices
                renderNotices(serverData.notices || []);
                
                // Update admin notices list if on admin page
                renderAdminNoticesList(serverData.notices || []);
                
                // Update intervals if changed
                const serverAdminInterval = serverData.admin_interval || 5;
                const serverScoreboardInterval = serverData.scoreboard_interval || 10;
                
                if (serverAdminInterval !== adminInterval) {
                    adminInterval = serverAdminInterval;
                    const adminIntervalInput = document.getElementById('admin-interval-input');
                    if (adminIntervalInput) {
                        adminIntervalInput.value = adminInterval;
                    }
                }
                
                if (serverScoreboardInterval !== scoreboardInterval) {
                    scoreboardInterval = serverScoreboardInterval;
                    const scoreboardIntervalInput = document.getElementById('scoreboard-interval-input');
                    if (scoreboardIntervalInput) {
                        scoreboardIntervalInput.value = scoreboardInterval;
                    }
                }
                
                // Update event status display if present (not the dropdown)
                if (serverData.event_status) {
                    updateEventStatusDisplay(serverData.event_status);
                }
                
                // Update current interval based on page type and restart if changed
                const newCurrentInterval = isAdminPage ? adminInterval : scoreboardInterval;
                if (newCurrentInterval !== currentInterval) {
                    currentInterval = newCurrentInterval;
                    startCountdown();
                }
            } else {
                console.error('AJAX error:', data);
            }
        })
        .catch(error => {
            console.error('Error loading data:', error);
        });
        
        // Also load friend file sizes
        loadFriendFileSizes();
        
        // Also load friend predictions
        loadFriendPredictions();
        
        // Also load current category display
        loadCurrentCategory();
        
        // Also load upcoming categories (admin only)
        loadUpcomingCategories();
    }
    
    /**
     * Load friend file sizes
     */
    function loadFriendFileSizes() {
        const formData = new FormData();
        formData.append('action', 'scoreboard_get_friend_file_sizes');
        formData.append('nonce', scoreboardData.nonce);
        
        fetch(scoreboardData.ajaxurl, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateFileSizeDisplays(data.data);
            }
        })
        .catch(error => {
            console.error('Error loading file sizes:', error);
        });
    }
    
    /**
     * Update file size displays
     */
    function updateFileSizeDisplays(fileSizes) {
        Object.keys(fileSizes).forEach(userId => {
            const fileSize = fileSizes[userId];
            const fileSizeEl = document.querySelector(`.file-size[data-user-id="${userId}"]`);
            
            if (fileSizeEl) {
                fileSizeEl.textContent = formatFileSize(fileSize);
            }
        });
    }
    
    /**
     * Load friend predictions
     */
    function loadFriendPredictions() {
        const formData = new FormData();
        formData.append('action', 'scoreboard_get_friend_predictions');
        formData.append('nonce', scoreboardData.nonce);
        
        fetch(scoreboardData.ajaxurl, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayPredictionIcons(data.data);
            }
        })
        .catch(error => {
            console.error('Error loading predictions:', error);
        });
    }
    
    /**
     * Display prediction icons in nominations
     */
    function displayPredictionIcons(predictionsData) {
        // Clear existing prediction icons
        document.querySelectorAll('.prediction-icon').forEach(icon => icon.remove());
        
        // Loop through each user's data
        Object.keys(predictionsData).forEach(userId => {
            const userData = predictionsData[userId];
            const predictions = userData.predictions || [];
            const favourites = userData.favourites || [];
            
            // Get the friend item element to copy the icon from
            const friendItem = document.querySelector(`.friend-item[data-user-id="${userId}"]`);
            if (!friendItem) return;
            
            const friendPhoto = friendItem.querySelector('.friend-photo');
            if (!friendPhoto) return;
            
            // Track nominations we've already processed
            const processed = new Set();
            
            // Process nominations that are BOTH predictions and favourites
            predictions.forEach(nominationId => {
                if (favourites.includes(nominationId)) {
                    const nomination = document.getElementById(`nomination-${nominationId}`);
                    if (nomination) {
                        const container = nomination.querySelector('.prediction-favourites');
                        if (container) {
                            const iconWrapper = createPredictionIcon(friendItem, friendPhoto, userId, 'both');
                            container.appendChild(iconWrapper);
                            processed.add(nominationId);
                        }
                    }
                }
            });
            
            // Process predictions only (not favourites)
            predictions.forEach(nominationId => {
                if (!processed.has(nominationId)) {
                    const nomination = document.getElementById(`nomination-${nominationId}`);
                    if (nomination) {
                        const container = nomination.querySelector('.prediction-favourites');
                        if (container) {
                            const iconWrapper = createPredictionIcon(friendItem, friendPhoto, userId, 'prediction');
                            container.appendChild(iconWrapper);
                            processed.add(nominationId);
                        }
                    }
                }
            });
            
            // Process favourites only (not predictions)
            favourites.forEach(nominationId => {
                if (!processed.has(nominationId)) {
                    const nomination = document.getElementById(`nomination-${nominationId}`);
                    if (nomination) {
                        const container = nomination.querySelector('.prediction-favourites');
                        if (container) {
                            const iconWrapper = createPredictionIcon(friendItem, friendPhoto, userId, 'favourite');
                            container.appendChild(iconWrapper);
                        }
                    }
                }
            });
        });
    }
    
    /**
     * Create a prediction icon element
     */
    function createPredictionIcon(friendItem, friendPhoto, userId, type) {
        // Get display name from friend item
        const friendNameEl = friendItem.querySelector('.friend-name');
        const displayName = friendNameEl ? friendNameEl.textContent : 'User';
        
        // Create wrapper for the icon with the same style attribute (for --randomcolornum)
        const iconWrapper = document.createElement('div');
        iconWrapper.classList.add('prediction-icon');
        iconWrapper.classList.add(`prediction-${type}`);
        iconWrapper.setAttribute('data-user-id', userId);
        
        // Copy the style attribute from parent friend item (contains --randomcolornum)
        const styleAttr = friendItem.getAttribute('style');
        if (styleAttr) {
            iconWrapper.setAttribute('style', styleAttr);
        }
        
        // Clone the friend photo
        const iconClone = friendPhoto.cloneNode(true);
        iconWrapper.appendChild(iconClone);
        
        // Create tooltip
        const tooltip = document.createElement('span');
        tooltip.classList.add('prediction-tooltip');
        let tooltipText = displayName + "'s ";
        if (type === 'both') {
            tooltipText += 'Prediction & Favourite';
        } else if (type === 'prediction') {
            tooltipText += 'Prediction';
        } else {
            tooltipText += 'Favourite';
        }
        tooltip.textContent = tooltipText;
        iconWrapper.appendChild(tooltip);
        
        return iconWrapper;
    }
    
    /**
     * Format file size in human-readable format
     */
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 B';
        
        const k = 1024;
        const sizes = ['B', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        
        return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
    }
    
    /**
     * Load and update upcoming categories section
     */
    function loadUpcomingCategories() {
        const upcomingController = document.querySelector('.upcoming-categories-controller');
        
        // Only proceed if we're on admin page with the upcoming categories controller
        if (!upcomingController) {
            return;
        }
        
        const categorySelect = document.getElementById('category-select');
        const setBtn = document.getElementById('set-category-btn');
        
        // Don't update if user is actively interacting with the dropdown or button
        if (categorySelect && (document.activeElement === categorySelect || 
            document.activeElement === setBtn)) {
            return;
        }
        
        const formData = new FormData();
        formData.append('action', 'scoreboard_get_upcoming_categories');
        formData.append('nonce', scoreboardData.nonce);
        
        fetch(scoreboardData.ajaxurl, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data.html) {
                // Check if there are actual changes before updating
                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = data.data.html;
                
                const newOptions = Array.from(tempDiv.querySelectorAll('#category-select option'))
                    .map(opt => opt.getAttribute('data-slug')).filter(Boolean);
                const newChips = Array.from(tempDiv.querySelectorAll('.category-chip'))
                    .map(chip => chip.getAttribute('data-slug'));
                
                const existingSelect = upcomingController.querySelector('#category-select');
                const existingOptions = existingSelect ? 
                    Array.from(existingSelect.querySelectorAll('option'))
                        .map(opt => opt.getAttribute('data-slug')).filter(Boolean) : [];
                const existingChips = Array.from(upcomingController.querySelectorAll('.category-chip'))
                    .map(chip => chip.getAttribute('data-slug'));
                
                // Check if options or chips have changed
                const optionsChanged = JSON.stringify(newOptions) !== JSON.stringify(existingOptions);
                const chipsChanged = JSON.stringify(newChips) !== JSON.stringify(existingChips);
                
                if (!optionsChanged && !chipsChanged) {
                    // No changes, skip update
                    return;
                }
                
                // Save the current selected value if it exists
                const currentValue = categorySelect ? categorySelect.value : '';
                
                // Find the container that holds the category control and completed chips
                const existingControl = upcomingController.querySelector('#category-control');
                const existingCompleted = upcomingController.querySelector('#completed-categories-display');
                const existingMessage = upcomingController.querySelector('p');
                
                // Remove existing elements (but not the description paragraph)
                if (existingControl) existingControl.remove();
                if (existingCompleted) existingCompleted.remove();
                if (existingMessage && existingMessage.textContent.includes('No upcoming')) {
                    existingMessage.remove();
                }
                
                // Insert the new HTML after the <p> description
                const description = Array.from(upcomingController.querySelectorAll('p'))
                    .find(p => p.textContent.includes('Select the current category'));
                if (description) {
                    description.insertAdjacentHTML('afterend', data.data.html);
                } else {
                    // If no description, append to the controller
                    upcomingController.insertAdjacentHTML('beforeend', data.data.html);
                }
                
                // Restore the selected value if it still exists in the new dropdown
                const newSelect = document.getElementById('category-select');
                if (newSelect && currentValue) {
                    for (let i = 0; i < newSelect.options.length; i++) {
                        if (newSelect.options[i].value === currentValue) {
                            newSelect.value = currentValue;
                            break;
                        }
                    }
                }
                
                // Re-attach event listener for the activate button
                handleCategoryActivate();
            }
        })
        .catch(error => {
            console.error('Error loading upcoming categories:', error);
        });
    }
    
    /**
     * Load and update current category display
     */
    function loadCurrentCategory() {
        const currentCategoryContainer = document.getElementById('current-category-container');
        
        // Only proceed if we're on a page with the current category container
        if (!currentCategoryContainer) {
            return;
        }
        
        // Fetch the JSON file to get the active category
        fetch('/wp-content/uploads/2026-results.json')
            .then(response => response.json())
            .then(data => {
                const activeCategory = data.active_category;
                
                if (activeCategory) {
                    // Hide all category displays
                    const allDisplays = currentCategoryContainer.querySelectorAll('.current-category-display');
                    allDisplays.forEach(display => {
                        display.style.display = 'none';
                    });
                    
                    // Show the active category display
                    const activeDisplay = currentCategoryContainer.querySelector(`.current-category-display[data-category-slug="${activeCategory}"]`);
                    if (activeDisplay) {
                        activeDisplay.style.display = 'block';
                    }
                }
            })
            .catch(error => {
                console.error('Error loading current category from JSON:', error);
            });
    }
    
    /**
     * Render notices in the list
     */
    function renderNotices(notices) {
        const list = document.getElementById('notices-list');
        if (!list) return;
        
        const dismissedNotices = getDismissedNotices();
        
        // Filter out dismissed notices
        const activeNotices = notices.filter(notice => 
            !dismissedNotices.includes(notice.timestamp)
        );
        
        // Only update if there are changes
        if (activeNotices.length === lastNoticeCount) return;
        
        lastNoticeCount = activeNotices.length;
        list.innerHTML = '';
        
        // Render in reverse order (newest first)
        activeNotices.slice().reverse().forEach((notice) => {
            const li = document.createElement('li');
            const formattedTime = formatTimestamp(notice.timestamp);
            
            li.innerHTML = `
                <div class="notice-header">
                    <span class="notice-label">Admin Notice</span>
                    <span class="timestamp">${formattedTime}</span>
                    <button class="close-btn" aria-label="Close notice">
                        <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M12 4L4 12M4 4L12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>
                </div>
                <div class="notice-body">
                    <span class="message">${escapeHtml(notice.message)}</span>
                </div>
            `;
            
            // Add click handler for close button
            const closeBtn = li.querySelector('.close-btn');
            closeBtn.addEventListener('click', () => {
                // Save dismissed state
                dismissNotice(notice.timestamp);
                
                // Animate removal
                li.style.opacity = '0';
                li.style.transform = 'translateX(20px)';
                li.style.transition = 'opacity 0.3s, transform 0.3s';
                setTimeout(() => {
                    li.remove();
                    lastNoticeCount--;
                }, 300);
            });
            
            list.appendChild(li);
        });
    }
    
    /**
     * Render admin notices management list
     */
    function renderAdminNoticesList(notices) {
        const list = document.getElementById('admin-notices-list');
        const countEl = document.getElementById('notice-count');
        
        if (!list) return;
        
        // Update count
        if (countEl) {
            countEl.textContent = notices.length;
        }
        
        list.innerHTML = '';
        
        if (notices.length === 0) {
            list.innerHTML = '<li class="empty-state">No notices currently posted</li>';
            return;
        }
        
        // Render in reverse order (newest first)
        notices.slice().reverse().forEach((notice) => {
            const li = document.createElement('li');
            const formattedTime = formatTimestamp(notice.timestamp);
            
            li.innerHTML = `
                <div class="notice-header">
                    <span class="notice-label">Admin Notice</span>
                    <span class="timestamp">${formattedTime}</span>
                    <button class="close-btn" data-timestamp="${notice.timestamp}" aria-label="Delete notice">
                        <svg width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M12 4L4 12M4 4L12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </button>
                </div>
                <div class="notice-body">
                    <span class="message">${escapeHtml(notice.message)}</span>
                </div>
            `;
            
            // Add click handler for delete button
            const deleteBtn = li.querySelector('.close-btn');
            deleteBtn.addEventListener('click', () => {
                handleDeleteNotice(notice.timestamp);
            });
            
            list.appendChild(li);
        });
    }
    
    /**
     * Escape HTML to prevent XSS
     */
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    /**
     * Handle category activation
     */
    function handleCategoryActivate() {
        const categorySelect = document.getElementById('category-select');
        const activateBtn = document.getElementById('set-category-btn');
        
        if (!categorySelect || !activateBtn) return;
        
        activateBtn.addEventListener('click', function() {
            const selectedOption = categorySelect.options[categorySelect.selectedIndex];
            
            if (!selectedOption || !selectedOption.value) {
                console.error('Please select a category first');
                return;
            }
            
            const categorySlug = selectedOption.getAttribute('data-slug');
            const categoryName = selectedOption.textContent;
            
            if (!categorySlug) {
                console.error('Invalid category selection');
                return;
            }
            
            console.log('Activating category:', categoryName, 'with slug:', categorySlug);
            
            // Disable button during request
            activateBtn.disabled = true;
            activateBtn.textContent = 'Activating...';
            
            // Send AJAX request
            fetch(scoreboardData.ajaxurl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'scoreboard_set_active_category',
                    category_slug: categorySlug,
                    nonce: scoreboardData.nonce
                })
            })
            .then(response => response.json())
            .then(data => {
                console.log('Server response:', data);
                if (data.success) {
                    console.log(`✅ Category "${categoryName}" activated successfully!`);
                    console.log('Response data:', data.data);
                    // Optionally reset the select
                    // categorySelect.selectedIndex = 0;
                } else {
                    console.error('❌ Error activating category:', data.data || 'Failed to activate category');
                }
            })
            .catch(error => {
                console.error('❌ Error during category activation:', error);
            })
            .finally(() => {
                // Re-enable button
                activateBtn.disabled = false;
                activateBtn.textContent = 'Activate';
            });
        });
    }
    
    /**
     * Handle category completion
     */
    function handleCategoryComplete() {
        const completeBtn = document.getElementById('complete-category-btn');
        
        if (!completeBtn) return;
        
        completeBtn.addEventListener('click', function() {
            // if (!confirm('Are you sure you want to mark the current category as complete? This will move it to past categories.')) {
            //     return;
            // }
            
            console.log('Marking current category as complete');
            
            // Disable button during request
            completeBtn.disabled = true;
            completeBtn.textContent = 'Processing...';
            
            // Send AJAX request
            fetch(scoreboardData.ajaxurl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'scoreboard_complete_category',
                    nonce: scoreboardData.nonce
                })
            })
            .then(response => response.json())
            .then(data => {
                console.log('Server response:', data);
                if (data.success) {
                    console.log('✅ Category marked as complete successfully!');
                    console.log('Response data:', data.data);
                    
                    // Hide all category displays
                    const currentCategoryContainer = document.getElementById('current-category-container');
                    if (currentCategoryContainer) {
                        const allDisplays = currentCategoryContainer.querySelectorAll('.current-category-display');
                        allDisplays.forEach(display => {
                            display.style.display = 'none';
                        });
                    }
                    
                    // Update the upcoming categories section
                    updateUpcomingCategories(data.completed_category);
                    
                    // Re-enable and update button text
                    completeBtn.disabled = false;
                    completeBtn.textContent = 'Mark Category as Complete';
                } else {
                    console.error('❌ Error completing category:', data.data || 'Failed to complete category');
                    completeBtn.disabled = false;
                    completeBtn.textContent = 'Mark Category as Complete';
                }
            })
            .catch(error => {
                console.error('❌ Error during category completion:', error);
                completeBtn.disabled = false;
                completeBtn.textContent = 'Mark Category as Complete';
            });
        });
    }
    
    /**
     * Update upcoming categories section after completion
     */
    function updateUpcomingCategories(completedSlug) {
        const categorySelect = document.getElementById('category-select');
        
        if (!categorySelect) return;
        
        // Find and remove the completed category from the dropdown
        let completedOptionText = '';
        for (let i = 0; i < categorySelect.options.length; i++) {
            const option = categorySelect.options[i];
            if (option.getAttribute('data-slug') === completedSlug) {
                completedOptionText = option.textContent;
                categorySelect.remove(i);
                break;
            }
        }
        
        // If no completed categories display exists, create it
        let completedDisplay = document.getElementById('completed-categories-display');
        if (!completedDisplay && completedOptionText) {
            completedDisplay = document.createElement('div');
            completedDisplay.id = 'completed-categories-display';
            completedDisplay.style.marginTop = '20px';
            completedDisplay.innerHTML = '<h3>Completed Categories</h3><div class="completed-chips"></div>';
            
            const upcomingController = document.querySelector('.upcoming-categories-controller');
            if (upcomingController) {
                upcomingController.appendChild(completedDisplay);
            }
        }
        
        // Add the completed category chip
        if (completedDisplay && completedOptionText) {
            const chipsContainer = completedDisplay.querySelector('.completed-chips');
            if (chipsContainer) {
                const chip = document.createElement('span');
                chip.className = 'category-chip';
                chip.setAttribute('data-slug', completedSlug);
                chip.textContent = completedOptionText;
                chipsContainer.appendChild(chip);
            }
        }
    }
    
    /**
     * Handle marking a nomination as winner
     */
    function handleMarkWinner() {
        // Use event delegation for dynamically added buttons
        document.addEventListener('click', function(e) {
            if (e.target.closest('.mark-winner-btn')) {
                const btn = e.target.closest('.mark-winner-btn');
                const nominationId = btn.getAttribute('data-nomination-id');
                
                if (!nominationId) {
                    console.error('No nomination ID found');
                    return;
                }
                
                console.log('Marking nomination as winner:', nominationId);
                
                // Disable button during request
                btn.disabled = true;
                
                // Send AJAX request
                fetch(scoreboardData.ajaxurl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'scoreboard_mark_winner',
                        nomination_id: nominationId,
                        nonce: scoreboardData.nonce
                    })
                })
                .then(response => response.json())
                .then(data => {
                    console.log('Server response:', data);
                    if (data.success) {
                        console.log('✅ Winner marked successfully!');
                        console.log('Response data:', data.data);
                        
                        // Add visual feedback - add 'winner' class to the nomination
                        const nominationLi = btn.closest('li');
                        if (nominationLi) {
                            nominationLi.classList.add('winner');
                        }
                        
                        // Re-enable button
                        btn.disabled = false;
                    } else {
                        console.error('❌ Error marking winner:', data.data || 'Failed to mark winner');
                        btn.disabled = false;
                    }
                })
                .catch(error => {
                    console.error('❌ Error during winner marking:', error);
                    btn.disabled = false;
                });
            }
        });
    }
    
    // Wait for DOM to be ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            initScoreboard();
            handleCategoryActivate();
            handleCategoryComplete();
            handleMarkWinner();
        });
    } else {
        initScoreboard();
        handleCategoryActivate();
        handleCategoryComplete();
        handleMarkWinner();
    }

    
    // Cleanup on page unload
    window.addEventListener('beforeunload', () => {
        if (countdownInterval) {
            clearInterval(countdownInterval);
        }
    });
    
})();
