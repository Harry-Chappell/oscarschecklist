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
    let currentInterval = 10; // Default to 10 seconds
    let countdown = 10;
    
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
        const intervalInput = document.getElementById('interval-input');
        const clearAllBtn = document.getElementById('clear-all-notices');
        
        if (form && input) {
            form.addEventListener('submit', handleNoticeSubmit);
        }
        
        if (intervalInput) {
            intervalInput.addEventListener('change', handleIntervalChange);
        }
        
        if (clearAllBtn) {
            clearAllBtn.addEventListener('click', handleClearAllNotices);
        }
        
        // Load initial data
        loadData();
        
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
        const countdownEl = document.getElementById('countdown');
        
        if (countdownEl) {
            countdownEl.textContent = countdown;
        }
    }
    
    /**
     * Handle interval change - save to server
     */
    function handleIntervalChange(e) {
        const newInterval = parseInt(e.target.value);
        if (newInterval > 0 && newInterval !== currentInterval) {
            const formData = new FormData();
            formData.append('action', 'scoreboard_update_interval');
            formData.append('interval', newInterval);
            formData.append('nonce', scoreboardData.nonce);
            
            fetch(scoreboardData.ajaxurl, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Data will be updated on next load
                    console.log('Interval updated');
                }
            })
            .catch(error => {
                console.error('Error updating interval:', error);
            });
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
                
                // Update interval if changed
                const serverInterval = serverData.interval || 10; // Default to 10 seconds
                if (serverInterval !== currentInterval) {
                    currentInterval = serverInterval;
                    const intervalInput = document.getElementById('interval-input');
                    if (intervalInput) {
                        intervalInput.value = currentInterval;
                    }
                    // Restart countdown with new interval
                    startCountdown();
                }
            } else {
                console.error('AJAX error:', data);
            }
        })
        .catch(error => {
            console.error('Error loading data:', error);
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
    
    // Wait for DOM to be ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initScoreboard);
    } else {
        initScoreboard();
    }
    
    // Cleanup on page unload
    window.addEventListener('beforeunload', () => {
        if (countdownInterval) {
            clearInterval(countdownInterval);
        }
    });
    
})();
