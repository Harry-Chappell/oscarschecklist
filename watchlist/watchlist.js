document.addEventListener('click', function (e) {
  const btn = e.target.closest(
    '.mark-as-watchlist-button, .mark-as-unwatchlist-button, .remove-from-watchlist-button'
  );
  if (!btn) return;

  e.preventDefault();

  const filmId = btn.getAttribute('data-film-id');
  const action = btn.getAttribute('data-action');

  // Determine if adding or removing watchlist
  const addToWatchlist = (action === 'watchlist');

  if (addToWatchlist) {
    // Find nomination <li> for this film id (assuming nomination has .film-id-XXXX class)
    const nominationLi = document.querySelector(`li.film-id-${filmId}`);
    console.log('Nomination element:', nominationLi);

    if (!nominationLi) {
      console.warn('Nomination item not found for film id', filmId);
      return;
    }

    // Find watchlist <ul> container
    const watchlistUl = document.querySelector('.watchlist-cntr .watchlist');
        console.log('Watchlist container:', watchlistUl);
    if (!watchlistUl) {
      console.warn('Watchlist container not found');
      return;
    }

    // Check for duplicates, skip if already present
    if (watchlistUl.querySelector(`li[data-film-id="${filmId}"]`)) {
      console.info('Film already in watchlist:', filmId);
      return;
    }

    // Create new watchlist item and append it
    const newWatchlistItem = convertNominationToWatchlistItem(nominationLi);
        console.log('New watchlist item element:', newWatchlistItem);

        if (newWatchlistItem) {
        watchlistUl.appendChild(newWatchlistItem);

        newWatchlistItem.setAttribute('data-newly-added', '1');

        // Immediately check if it exists in DOM
        const foundInDom = watchlistUl.querySelector(`li[data-film-id="${filmId}"]`);
        console.log('Found appended item in watchlist after append?', foundInDom);

        // Check if it's visible
        if (foundInDom) {
            const style = window.getComputedStyle(foundInDom);
            console.log('Appended item display style:', style.display, 'visibility:', style.visibility, 'opacity:', style.opacity);
        }
    }

    // Update clicked button UI to "unwatchlist"
    btn.classList.remove('mark-as-watchlist-button');
    btn.classList.add('mark-as-unwatchlist-button');
    btn.setAttribute('data-action', 'unwatchlist');
    btn.setAttribute('title', 'Remove from Watchlist');

  } else if (action === 'unwatchlist' || action === 'unwatched' || action === 'remove') {
    // Remove from watchlist UI
    const liToRemove = document.querySelector(`.watchlist li[data-film-id="${filmId}"]`);
    if (liToRemove) liToRemove.remove();

    // Only target watchlist buttons (both states) — NOT watched buttons
    const relatedWatchlistBtns = document.querySelectorAll(
    `button[data-film-id="${filmId}"].mark-as-unwatchlist-button, button[data-film-id="${filmId}"].mark-as-watchlist-button`
    );
    relatedWatchlistBtns.forEach((button) => {
    button.classList.remove('mark-as-unwatchlist-button');
    button.classList.add('mark-as-watchlist-button');
    button.setAttribute('data-action', 'watchlist');
    button.setAttribute('title', 'Add to Watchlist');
    });
  }

  // Send AJAX request
  const xhr = new XMLHttpRequest();
  xhr.open('POST', '/wp-admin/admin-ajax.php', true);
  xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

  const actionType = addToWatchlist ? 'add' : 'remove';

  xhr.onload = function () {
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
      '&film_id=' +
      encodeURIComponent(filmId) +
      '&action_type=' +
      encodeURIComponent(actionType)
  );
});

// Helper function outside event listener
function convertNominationToWatchlistItem(nominationLi) {
  if (!nominationLi) return null;

  let filmId = nominationLi.getAttribute('data-film-id');

  // fallback: try to get film id from class list, e.g. class="film-id-51865"
  if (!filmId) {
    const filmIdClass = Array.from(nominationLi.classList).find(c => c.startsWith('film-id-'));
    if (filmIdClass) {
      filmId = filmIdClass.replace('film-id-', '');
    }
  }

  if (!filmId) return null;

  const posterImg = nominationLi.querySelector('.film-poster img');
  const imgSrc = posterImg ? posterImg.src : '';
  const imgAlt = posterImg ? posterImg.alt : '';

  let titleText = '';
  const filmNameEl = nominationLi.querySelector('.film-name');

  if (filmNameEl) {
    const h3 = filmNameEl.querySelector('h3');
    if (h3 && h3.textContent.trim()) {
      titleText = h3.textContent.trim();
    } else if (filmNameEl.textContent.trim()) {
      titleText = filmNameEl.textContent.trim();
    }
  }

  const SVG_CHECK = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512">
    <path d="M438.6 105.4c12.5 12.5 12.5 32.8 0 45.3l-256 256c-12.5 12.5-32.8 12.5-45.3 0l-128-128
             c-12.5-12.5-12.5-32.8 0-45.3s32.8-12.5 45.3 0L160 338.7 393.4 105.4c12.5-12.5 32.8-12.5 45.3 0z"/>
  </svg>`;

  const SVG_REMOVE = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640">
    <path d="M96 320C96 302.3 110.3 288 128 288L512 288C529.7 288 544 302.3 544 320C544 337.7 529.7 352 
             512 352L128 352C110.3 352 96 337.7 96 320z"/>
  </svg>`;

  const li = document.createElement('li');
  // Set class based on nomination's watched state
  li.className = nominationLi.classList.contains('watched') ? 'watched' : 'unwatched';
  li.setAttribute('data-film-id', filmId);

  if (imgSrc) {
    const img = document.createElement('img');
    img.decoding = 'async';
    img.src = imgSrc;
    img.alt = imgAlt || titleText;
    li.appendChild(img);
  }

  const span = document.createElement('span');
  span.className = 'film-title';
  span.textContent = titleText;
  li.appendChild(span);

  // Create toggle button with conditional class/action/title
  const toggleBtn = document.createElement('button');
  toggleBtn.type = 'button';
  toggleBtn.setAttribute('data-film-id', filmId);

  if (nominationLi.classList.contains('watched')) {
    toggleBtn.className = 'mark-as-unwatched-button';
    toggleBtn.setAttribute('data-action', 'unwatched');
    toggleBtn.title = 'Watched';
  } else {
    toggleBtn.className = 'mark-as-watched-button';
    toggleBtn.setAttribute('data-action', 'watched');
    toggleBtn.title = 'Watched';
  }
  toggleBtn.innerHTML = SVG_CHECK;
  li.appendChild(toggleBtn);

  const removeBtn = document.createElement('button');
  removeBtn.type = 'button';
  removeBtn.title = 'Remove from Watchlist';
  removeBtn.className = 'remove-from-watchlist-button';
  removeBtn.setAttribute('data-film-id', filmId);
  removeBtn.setAttribute('data-action', 'remove');
  removeBtn.innerHTML = SVG_REMOVE;
  li.appendChild(removeBtn);

  return li;
}


// Forward clicks on newly-added watchlist items to the original button elsewhere on the page
document.addEventListener('click', function (e) {
  // Find the closest watchlist <li> (works if user clicks anywhere inside the li)
  const li = e.target.closest('.watchlist li[data-film-id]');
  if (!li) return;

  // Only act on newly-added items
  if (!li.hasAttribute('data-newly-added')) return;

  // Ignore clicks that are explicitly on the "remove from watchlist" button
  if (e.target.closest('.remove-from-watchlist-button')) return;

  const filmId = li.getAttribute('data-film-id');
  if (!filmId) return;

  // Find other buttons with the same film id that have the existing watched handlers.
  // Prefer mark-as-watched / mark-as-unwatched (these have handlers attached in scripts.js)
  const candidates = Array.from(document.querySelectorAll(
    `button[data-film-id="${filmId}"].mark-as-watched-button, button[data-film-id="${filmId}"].mark-as-unwatched-button`
  ));

  // Find the first candidate that is NOT inside this watchlist li
  const target = candidates.find(btn => !li.contains(btn));

  if (target) {
    // trigger the existing handler
    target.click();

    // clear the 'new' marker so we don't forward again for the same item
    li.removeAttribute('data-newly-added');

    // Prevent any default / duplicate handling for this click on the li
    e.preventDefault();
    e.stopPropagation();
  }
});


document.addEventListener("DOMContentLoaded", () => {
  // Listen for toggle changes
  document.body.addEventListener("change", (e) => {
    const toggle = e.target.closest(".watchlist-setting-toggle");
    if (!toggle) return;

    const setting = toggle.getAttribute("data-setting");
    const value = toggle.checked;

    const formData = new FormData();
    formData.append("action", "oscars_update_setting");
    formData.append("setting", setting);
    formData.append("value", value);

    fetch(ajaxurl, {
      method: "POST",
      credentials: "same-origin",
      body: formData,
    })
      .then((res) => res.json())
      .then((response) => {
        if (response.success) {
          console.log("Updated setting:", response.data);
        } else {
          alert("Error saving setting: " + response.data);
        }
      })
      .catch((err) => {
        console.error("AJAX error:", err);
      });
  });
});