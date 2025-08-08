document.addEventListener('DOMContentLoaded', function() {
    document.body.addEventListener('click', function(e) {
        const btn = e.target.closest(
            '.mark-as-watchlist-button, .mark-as-unwatchlist-button, .remove-from-watchlist-button'
        );
        if (!btn) return;
        e.preventDefault();

        const filmId = btn.getAttribute('data-film-id');
        const action = btn.getAttribute('data-action');
        let addToWatchlist = null;

        if (action === 'watchlist') {
            addToWatchlist = true;
        } else if (action === 'unwatchlist' || action === 'remove') {
            addToWatchlist = false;
        }

        // If it's a REMOVE action, also remove the <li> from DOM immediately
        if (action === 'remove') {
            const li = btn.closest('li');
            if (li) {
                li.remove();
            }

            // Also change *all* buttons for this filmId back to watchlist mode
            const relatedBtns = document.querySelectorAll(
                `.mark-as-unwatchlist-button[data-film-id="${filmId}"]`
            );
            relatedBtns.forEach(function(button) {
                button.classList.remove('mark-as-unwatchlist-button');
                button.classList.add('mark-as-watchlist-button');
                button.setAttribute('data-action', 'watchlist');
                button.setAttribute('title', 'Add to Watchlist');
            });
        } else {
            // Toggle UI for watchlist buttons with same filmId
            const allBtns = document.querySelectorAll(
                `.mark-as-watchlist-button[data-film-id="${filmId}"], 
                 .mark-as-unwatchlist-button[data-film-id="${filmId}"]`
            );
            allBtns.forEach(function(button) {
                if (addToWatchlist) {
                    button.classList.remove('mark-as-watchlist-button');
                    button.classList.add('mark-as-unwatchlist-button');
                    button.setAttribute('data-action', 'unwatchlist');
                    button.setAttribute('title', 'Remove from Watchlist');
                } else {
                    button.classList.remove('mark-as-unwatchlist-button');
                    button.classList.add('mark-as-watchlist-button');
                    button.setAttribute('data-action', 'watchlist');
                    button.setAttribute('title', 'Add to Watchlist');
                }
            });
        }

        // Send AJAX request
        const xhr = new XMLHttpRequest();
        xhr.open('POST', '/wp-admin/admin-ajax.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

        // Convert UI action to PHP action_type
        let actionType;
        if (addToWatchlist) {
            actionType = 'add';
        } else {
            actionType = 'remove';
        }

        xhr.onload = function() {
            // Optional: check response here
            try {
                const res = JSON.parse(xhr.responseText);
                if (!res.success) {
                    console.error('Watchlist update failed:', res.data);
                }
            } catch (err) {
                console.error('Invalid JSON response', err);
            }
        };

        xhr.send(
            'action=oscars_update_watchlist' +
            '&film_id=' + encodeURIComponent(filmId) +
            '&action_type=' + encodeURIComponent(actionType)
        );
    });
});