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
    let watchlistUl = document.querySelector('.watchlist-cntr .watchlist');
    console.log('Watchlist container:', watchlistUl);
    if (!watchlistUl) {
      // If missing, create it inside the .watchlist-cntr
      const watchlistCntr = document.querySelector('.watchlist-cntr');
      if (watchlistCntr) {
        watchlistUl = document.createElement('ul');
        watchlistUl.className = 'watchlist';
        watchlistCntr.appendChild(watchlistUl);
        console.log('Created new watchlist <ul>');
      } else {
        console.warn('Watchlist container not found');
        return;
      }
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
      // Add 'current-page' class if this film is on the current page
      const found = document.querySelector(
        `.film-id-${filmId}:not(.watchlist li)`
      ) || document.querySelector(
        `.nominations-list li.film-id-${filmId}`
      );
      if (found) {
        newWatchlistItem.classList.add('current-page');
      }
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

  // --- SVGs matching PHP output ---
  const SVG_WATCHED = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><path d="M528 320C528 205.1 434.9 112 320 112C205.1 112 112 205.1 112 320C112 434.9 205.1 528 320 528C434.9 528 528 434.9 528 320zM64 320C64 178.6 178.6 64 320 64C461.4 64 576 178.6 576 320C576 461.4 461.4 576 320 576C178.6 576 64 461.4 64 320z"></path></svg><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><path d="M320 112C434.9 112 528 205.1 528 320C528 434.9 434.9 528 320 528C205.1 528 112 434.9 112 320C112 205.1 205.1 112 320 112zM320 576C461.4 576 576 461.4 576 320C576 178.6 461.4 64 320 64C178.6 64 64 178.6 64 320C64 461.4 178.6 576 320 576zM404.4 276.7C411.4 265.5 408 250.7 396.8 243.6C385.6 236.5 370.8 240 363.7 251.2L302.3 349.5L275.3 313.5C267.3 302.9 252.3 300.7 241.7 308.7C231.1 316.7 228.9 331.7 236.9 342.3L284.9 406.3C289.6 412.6 297.2 416.2 305.1 415.9C313 415.6 320.2 411.4 324.4 404.6L404.4 276.6z"></path></svg><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><path d="M320 576C461.4 576 576 461.4 576 320C576 178.6 461.4 64 320 64C178.6 64 64 178.6 64 320C64 461.4 178.6 576 320 576zM404.4 276.7L324.4 404.7C320.2 411.4 313 415.6 305.1 416C297.2 416.4 289.6 412.8 284.9 406.4L236.9 342.4C228.9 331.8 231.1 316.8 241.7 308.8C252.3 300.8 267.3 303 275.3 313.6L302.3 349.6L363.7 251.3C370.7 240.1 385.5 236.6 396.8 243.7C408.1 250.8 411.5 265.5 404.4 276.8z"></path></svg>`;
  const SVG_REMOVE = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><path d="M183.1 137.4C170.6 124.9 150.3 124.9 137.8 137.4C125.3 149.9 125.3 170.2 137.8 182.7L275.2 320L137.9 457.4C125.4 469.9 125.4 490.2 137.9 502.7C150.4 515.2 170.7 515.2 183.2 502.7L320.5 365.3L457.9 502.6C470.4 515.1 490.7 515.1 503.2 502.6C515.7 490.1 515.7 469.8 503.2 457.3L365.8 320L503.1 182.6C515.6 170.1 515.6 149.8 503.1 137.3C490.6 124.8 470.3 124.8 457.8 137.3L320.5 274.7L183.1 137.4z"></path></svg>`;

  const li = document.createElement('li');
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

  // --- Watched button (new SVGs) ---
  const watchedBtn = document.createElement('button');
  watchedBtn.type = 'button';
  watchedBtn.setAttribute('data-film-id', filmId);
  if (nominationLi.classList.contains('watched')) {
    watchedBtn.className = 'mark-as-unwatched-button';
    watchedBtn.setAttribute('data-action', 'unwatched');
    watchedBtn.title = 'Watched';
  } else {
    watchedBtn.className = 'mark-as-watched-button';
    watchedBtn.setAttribute('data-action', 'watched');
    watchedBtn.title = 'Watched';
  }
  // watchedBtn.className = 'mark-as-watched-button';
  // watchedBtn.setAttribute('data-action', 'watched');
  // watchedBtn.title = 'Watched';
  watchedBtn.innerHTML = SVG_WATCHED;
  li.appendChild(watchedBtn);

  // --- Remove from Watchlist button (new SVG) ---
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

    // Update container class immediately
    const container = document.querySelector('.watchlist-cntr');
    if (container && setting) {
        if (value) {
            container.classList.add(setting);
        } else {
            container.classList.remove(setting);
        }
    }

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


document.addEventListener("DOMContentLoaded", () => {
  const toggleBtn = document.getElementById("toggle-watchlist");
  if (!toggleBtn) return;

  // Make/ensure badge element
  let badge = toggleBtn.querySelector(".watchlist-badge");
  if (!badge) {
    badge = document.createElement("span");
    badge.className = "watchlist-badge";
    toggleBtn.appendChild(badge);
  }

  let currentCount = null; // null so first render doesn't animate
  let resetTimer = null;

  function getWatchlistCount() {
    const uls = document.querySelectorAll("ul.watchlist");
    let total = 0;
    uls.forEach((ul) => {
      // Count only direct LI children of the watchlist UL
      total += Array.from(ul.children).filter((el) => el.tagName === "LI").length;
    });
    return total;
  }

  function refreshBadge() {
    const newCount = getWatchlistCount();

    // First render: set without animation
    if (currentCount === null) {
      badge.textContent = newCount;
      currentCount = newCount;
      return;
    }

    if (newCount === currentCount) return;

    const changeClass = newCount > currentCount ? "increasing" : "decreasing";
    currentCount = newCount;
    badge.textContent = newCount;

    // Reset animation classes
    badge.classList.remove("increasing", "decreasing");
    void badge.offsetWidth; // force reflow so animation retriggers
    badge.classList.add(changeClass);

    if (resetTimer) clearTimeout(resetTimer);
    resetTimer = setTimeout(() => {
      badge.classList.remove("increasing", "decreasing");
    }, 1000);
  }

  // Observe all current watchlist ULs for live add/remove updates
  const watchlists = document.querySelectorAll("ul.watchlist");
  const observers = [];
  watchlists.forEach((ul) => {
    const mo = new MutationObserver((mutations) => {
      for (const m of mutations) {
        if (m.type === "childList") {
          refreshBadge();
          break;
        }
      }
    });
    mo.observe(ul, { childList: true });
    observers.push(mo);
  });

  // Also update after any click on add/remove/watchlist buttons
  document.body.addEventListener("click", (e) => {
    const btn = e.target.closest(
      ".mark-as-watchlist-button, .mark-as-unwatchlist-button, .remove-from-watchlist-button"
    );
    if (!btn) return;
    // Defer slightly so DOM changes from your handlers have applied
    setTimeout(refreshBadge, 50);
  });

  // Initial render
  refreshBadge();

  // Optional: expose a manual trigger if you ever need it
  window.updateWatchlistBadge = refreshBadge;
});


document.addEventListener("DOMContentLoaded", () => {
  // Add 'current-page' class to watchlist items that match a film on the current page
  const watchlistLis = document.querySelectorAll('.watchlist li[data-film-id]');
  watchlistLis.forEach((li) => {
    const filmId = li.getAttribute('data-film-id');
    if (!filmId) return;
    // Look for any other element on the page with this film id (excluding the watchlist itself)
    const found = document.querySelector(
      `.film-id-${filmId}:not(.watchlist li)`
    ) || document.querySelector(
      `.nominations-list li.film-id-${filmId}`
    );
    if (found) {
      li.classList.add('current-page');
    } else {
      li.classList.remove('current-page');
    }
  });
});

// Auto-remove watched films from watchlist if .auto_remove_watched is present, and toggle live
(function() {
  document.addEventListener("DOMContentLoaded", function() {
    const watchlistCntr = document.querySelector('.watchlist-cntr');
    if (!watchlistCntr) return;
    let watchlist = watchlistCntr.querySelector('.watchlist');
    if (!watchlist) return;

    let liObserver = null;
    let listObserver = null;
    let enabled = false;

    function triggerRemove(li) {
      const btn = li.querySelector('.remove-from-watchlist-button');
      if (btn) btn.click();
    }

    function enableAutoRemove() {
      if (enabled) return;
      enabled = true;
      // Remove already-watched items on enable
      watchlist.querySelectorAll('li.watched').forEach(triggerRemove);
      // Observe for li gaining .watched
      liObserver = new MutationObserver((mutations) => {
        mutations.forEach(m => {
          if (m.type === 'attributes' && m.attributeName === 'class') {
            const li = m.target;
            if (li.classList.contains('watched')) {
              triggerRemove(li);
            }
          }
        });
      });
      watchlist.querySelectorAll('li').forEach(li => {
        liObserver.observe(li, { attributes: true, attributeFilter: ['class'] });
      });
      // Observe for new li added to the watchlist
      listObserver = new MutationObserver((mutations) => {
        mutations.forEach(m => {
          if (m.type === 'childList' && m.addedNodes.length) {
            m.addedNodes.forEach(node => {
              if (node.nodeType === 1 && node.tagName === 'LI') {
                liObserver.observe(node, { attributes: true, attributeFilter: ['class'] });
                if (node.classList.contains('watched')) {
                  triggerRemove(node);
                }
              }
            });
          }
        });
      });
      listObserver.observe(watchlist, { childList: true });
    }

    function disableAutoRemove() {
      enabled = false;
      if (liObserver) { liObserver.disconnect(); liObserver = null; }
      if (listObserver) { listObserver.disconnect(); listObserver = null; }
    }

    // Watch for .auto_remove_watched class changes
    const cntrObserver = new MutationObserver(() => {
      // In case the watchlist element changes (e.g. via AJAX), re-query
      watchlist = watchlistCntr.querySelector('.watchlist');
      if (!watchlist) { disableAutoRemove(); return; }
      if (watchlistCntr.classList.contains('auto_remove_watched')) {
        enableAutoRemove();
      } else {
        disableAutoRemove();
      }
    });
    cntrObserver.observe(watchlistCntr, { attributes: true, attributeFilter: ['class'] });

    // Initial state
    if (watchlistCntr.classList.contains('auto_remove_watched')) {
      enableAutoRemove();
    }
  });
})();

// --- Watchlist Reordering Buttons ---
document.addEventListener('DOMContentLoaded', function () {
  const watchlistUl = document.querySelector('.watchlist-cntr .watchlist');
  if (!watchlistUl) return;

  // Helper to update order on server
  function updateOrderOnServer() {
    const order = Array.from(watchlistUl.children)
      .filter(li => li.matches('li[data-film-id]'))
      .map(li => li.getAttribute('data-film-id'));
    const formData = new FormData();
    formData.append('action', 'oscars_reorder_watchlist');
    order.forEach(id => formData.append('order[]', id));
    fetch('/wp-admin/admin-ajax.php', {
      method: 'POST',
      credentials: 'same-origin',
      body: formData,
    })
      .then(res => res.json())
      .then(response => {
        if (response.success && response.data && response.data.watchlist) {
          // Update --order CSS variable for each li
          response.data.watchlist.forEach(item => {
            const li = watchlistUl.querySelector(`li[data-film-id="${item['film-id']}"]`);
            if (li) li.style.setProperty('--order', item.order);
          });
        }
      });
  }

  // Add up, down, and top buttons to each li
  function addReorderButtons(li) {
    if (li.querySelector('.move-up-btn')) return; // already added
    const upBtn = document.createElement('button');
    upBtn.className = 'move-up-btn';
    upBtn.title = 'Move Up';
    upBtn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><path d="M297.4 201.4C309.9 188.9 330.2 188.9 342.7 201.4L502.7 361.4C515.2 373.9 515.2 394.2 502.7 406.7C490.2 419.2 469.9 419.2 457.4 406.7L320 269.3L182.6 406.6C170.1 419.1 149.8 419.1 137.3 406.6C124.8 394.1 124.8 373.8 137.3 361.3L297.3 201.3z"/></svg>';
    upBtn.type = 'button';
    upBtn.addEventListener('click', function (e) {
      e.stopPropagation();
      const prev = li.previousElementSibling;
      if (prev && prev.matches('li[data-film-id]')) {
        watchlistUl.insertBefore(li, prev);
        updateOrderOnServer();
      }
    });
    const downBtn = document.createElement('button');
    downBtn.className = 'move-down-btn';
    downBtn.title = 'Move Down';
    downBtn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><path d="M297.4 438.6C309.9 451.1 330.2 451.1 342.7 438.6L502.7 278.6C515.2 266.1 515.2 245.8 502.7 233.3C490.2 220.8 469.9 220.8 457.4 233.3L320 370.7L182.6 233.4C170.1 220.9 149.8 220.9 137.3 233.4C124.8 245.9 124.8 266.2 137.3 278.7L297.3 438.7z"/></svg>';
    downBtn.type = 'button';
    downBtn.addEventListener('click', function (e) {
      e.stopPropagation();
      const next = li.nextElementSibling;
      if (next && next.matches('li[data-film-id]')) {
        watchlistUl.insertBefore(next, li);
        updateOrderOnServer();
      }
    });
    const topBtn = document.createElement('button');
    topBtn.className = 'move-top-btn';
    topBtn.title = 'Move to Top';
    topBtn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><path d="M342.6 105.4C330.1 92.9 309.8 92.9 297.3 105.4L137.3 265.4C124.8 277.9 124.8 298.2 137.3 310.7C149.8 323.2 170.1 323.2 182.6 310.7L320 173.3L457.4 310.6C469.9 323.1 490.2 323.1 502.7 310.6C515.2 298.1 515.2 277.8 502.7 265.3L342.7 105.3zM502.6 457.4L342.6 297.4C330.1 284.9 309.8 284.9 297.3 297.4L137.3 457.4C124.8 469.9 124.8 490.2 137.3 502.7C149.8 515.2 170.1 515.2 182.6 502.7L320 365.3L457.4 502.6C469.9 515.1 490.2 515.1 502.7 502.6C515.2 490.1 515.2 469.8 502.7 457.3z"/></svg>';
    topBtn.type = 'button';
    topBtn.addEventListener('click', function (e) {
      e.stopPropagation();
      if (watchlistUl.firstElementChild !== li) {
        watchlistUl.insertBefore(li, watchlistUl.firstElementChild);
        updateOrderOnServer();
      }
    });
    const btnCntr = document.createElement('span');
    btnCntr.className = 'watchlist-reorder-btns';
    btnCntr.appendChild(upBtn);
    btnCntr.appendChild(downBtn);
    btnCntr.appendChild(topBtn);
    li.appendChild(btnCntr);
  }

  watchlistUl.querySelectorAll('li[data-film-id]').forEach(addReorderButtons);

  // If items are added dynamically, add buttons for them too
  const mo = new MutationObserver(muts => {
    muts.forEach(m => {
      m.addedNodes.forEach(node => {
        if (node.nodeType === 1 && node.matches('li[data-film-id]')) {
          addReorderButtons(node);
        }
      });
    });
  });
  mo.observe(watchlistUl, { childList: true });
});