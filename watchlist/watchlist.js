// console.log('test');
document.addEventListener('DOMContentLoaded', function() {
    document.body.addEventListener('click', function(e) {
        var btn = e.target.closest('.mark-as-watchlist-button, .mark-as-unwatchlist-button');
        if (!btn) return;
        e.preventDefault();
        var filmId = btn.getAttribute('data-film-id');
        var action = btn.getAttribute('data-action');
        var addToWatchlist = (action === 'watchlist');

        // Toggle UI for all watchlist buttons with this filmId
        var allBtns = document.querySelectorAll(
            '.mark-as-watchlist-button[data-film-id="' + filmId + '"], .mark-as-unwatchlist-button[data-film-id="' + filmId + '"]'
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

        // Send AJAX request to update user meta
        var xhr = new XMLHttpRequest();
        xhr.open('POST', '/wp-admin/admin-ajax.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onload = function() {
            // Optionally handle response
        };
        xhr.onerror = function() {
            // Optionally revert UI on error
        };
        xhr.send('action=oscars_update_watchlist&film_id=' + encodeURIComponent(filmId) + '&add=' + (addToWatchlist ? '1' : '0'));
    });
});
