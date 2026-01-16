/**
 * Scoreboard Scripts
 * 
 * JavaScript functionality for the Scoreboard page
 * Vanilla JavaScript - No jQuery
 */

(function() {
    'use strict';
    
    let lastSubmissionCount = 0;
    let pollingInterval = null;
    let countdownInterval = null;
    let currentInterval = 5;
    let countdown = 5;
    
    /**
     * Initialize scoreboard functionality
     */
    function initScoreboard() {
        const form = document.getElementById('submission-form');
        const input = document.getElementById('submission-input');
        const intervalInput = document.getElementById('interval-input');
        
        if (form && input) {
            form.addEventListener('submit', handleSubmit);
        }
        
        if (intervalInput) {
            intervalInput.addEventListener('change', handleIntervalChange);
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
        
        countdownInterval = setInterval(() => {
            countdown--;
            updateCountdown();
            
            if (countdown <= 0) {
                loadData();
                countdown = currentInterval;
            }
        }, 1000);
    }
    
    /**
     * Update countdown display and progress bar
     */
    function updateCountdown() {
        const countdownEl = document.getElementById('countdown');
        const progressBar = document.getElementById('progress-bar');
        
        if (countdownEl) {
            countdownEl.textContent = countdown;
        }
        
        if (progressBar) {
            const percentage = ((currentInterval - countdown) / currentInterval) * 100;
            progressBar.style.width = percentage + '%';
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
     * Handle form submission
     */
    function handleSubmit(e) {
        e.preventDefault();
        
        const input = document.getElementById('submission-input');
        const message = input.value.trim();
        
        if (!message) return;
        
        // Disable form while submitting
        input.disabled = true;
        
        const formData = new FormData();
        formData.append('action', 'scoreboard_save_submission');
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
                console.error('Error saving submission:', data);
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
     * Load data from server (submissions and interval)
     */
    function loadData() {
        const formData = new FormData();
        formData.append('action', 'scoreboard_get_submissions');
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
                
                // Update submissions
                renderSubmissions(serverData.submissions || []);
                
                // Update interval if changed
                const serverInterval = serverData.interval || 5;
                if (serverInterval !== currentInterval) {
                    currentInterval = serverInterval;
                    document.getElementById('interval-input').value = currentInterval;
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
     * Render submissions in the list
     */
    function renderSubmissions(submissions) {
        const list = document.getElementById('submissions-list');
        if (!list) return;
        
        // Only update if there are new submissions
        if (submissions.length === lastSubmissionCount) return;
        
        lastSubmissionCount = submissions.length;
        list.innerHTML = '';
        
        // Render in reverse order (newest first)
        submissions.slice().reverse().forEach(submission => {
            const li = document.createElement('li');
            li.innerHTML = `
                <span class="timestamp">${submission.formatted_time}</span>
                <span class="message">${escapeHtml(submission.message)}</span>
            `;
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
