document.addEventListener('DOMContentLoaded', function() {

    // Function to handle marking films as watched or unwatched
    function handleWatchButtons() {
        document.querySelectorAll('.mark-as-watched-button, .mark-as-unwatched-button').forEach(function(button) {
            button.addEventListener('click', function(event) {
                event.preventDefault();
    
                var filmId = button.getAttribute('data-film-id');
                var action = button.getAttribute('data-action');
                var listItem = button.closest('li'); // Get the closest <li> element
                var formData = new FormData();
                formData.append('watched_post_id', filmId);
                formData.append('watched_action', action);
    
                fetch('https://oscarschecklist.com/', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
    
                    // Toggle the watched status
                    var isWatched = action === 'watched';
                    button.classList.toggle('mark-as-watched-button', !isWatched);
                    button.classList.toggle('mark-as-unwatched-button', isWatched);
    
                    // Update the button's text while preserving the SVG
                    // var textNode = document.createTextNode(isWatched ? 'Watched' : 'Unwatched');
                    // Clear existing text nodes (but keep the SVG)
                    // button.firstChild.replaceWith(textNode);
    
                    button.setAttribute('data-action', isWatched ? 'unwatched' : 'watched');
    
                    // Toggle the 'watched' class on the <li> element
                    listItem.classList.toggle('watched', isWatched);
    
                    // Update all other elements with the same film ID
                    document.querySelectorAll('button.mark-as-watched-button[data-film-id="' + filmId + '"], button.mark-as-unwatched-button[data-film-id="' + filmId + '"]').forEach(function(duplicateButton) {
                        var duplicateListItem = duplicateButton.closest('li');
                        if (duplicateButton !== button) {
                            duplicateButton.classList.toggle('mark-as-watched-button', !isWatched);
                            duplicateButton.classList.toggle('mark-as-unwatched-button', isWatched);
    
                            // Update the duplicate button's text while preserving the SVG
                            // var duplicateTextNode = document.createTextNode(isWatched ? 'Watched' : 'Unwatched');
                            // duplicateButton.firstChild.replaceWith(duplicateTextNode);
    
                            // duplicateButton.setAttribute('data-action', isWatched ? 'unwatched' : 'watched');
    
                            // Toggle the 'watched' class on the corresponding <li> element
                            duplicateListItem.classList.toggle('watched', isWatched);
                        }
                    });
    
                    return response.text();
                })
                .then(data => {
                    // Update the TOC with new watched counts if needed
                    updateTOC();
                })
                .catch(error => {
                    console.error('Error:', error);
                });
            });
        });
    }
    
    // Initialize the button handler
    document.addEventListener('DOMContentLoaded', handleWatchButtons);





    function handleFavButtons() {
        document.querySelectorAll('.mark-as-fav-button, .mark-as-unfav-button').forEach(function (button) {
            button.addEventListener('click', function (event) {
                event.preventDefault();
    
                var nominationId = button.getAttribute('data-nomination-id');
                var favAction = button.getAttribute('data-action');
                var listItem = button.closest('li');
                var list = button.closest('ul'); // Scope to the parent list
    
                if (!listItem || !list) {
                    console.error('No containing <li> or <ul> element found for button:', button);
                    return;
                }
    
                var formData = new FormData();
                formData.append('fav_nom_id', nominationId);
                formData.append('fav_action', favAction);
    
                fetch('https://oscarschecklist.com/', {
                    method: 'POST',
                    body: formData,
                })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
    
                        var isFav = favAction === 'fav';
    
                        if (isFav) {
                            // Remove 'fav' from any other list items in the same list
                            list.querySelectorAll('li.fav').forEach(function (otherListItem) {
                                if (otherListItem !== listItem) {
                                    var otherButton = otherListItem.querySelector('.mark-as-unfav-button');
                                    if (otherButton) {
                                        otherButton.classList.toggle('mark-as-fav-button', true);
                                        otherButton.classList.toggle('mark-as-unfav-button', false);
                                        otherButton.setAttribute('data-action', 'fav');
    
                                        // Update button text
                                        // otherButton.childNodes.forEach(node => {
                                        //     if (node.nodeType === Node.TEXT_NODE) {
                                        //         node.textContent = 'Mark as Favourite';
                                        //     }
                                        // });
    
                                        // Toggle the 'fav' class on the <li> element
                                        otherListItem.classList.toggle('fav', false);
    
                                        // Simulate a POST request for the unfavored item
                                        var unfavFormData = new FormData();
                                        unfavFormData.append('fav_nom_id', otherButton.getAttribute('data-nomination-id'));
                                        unfavFormData.append('fav_action', 'unfav');
    
                                        fetch('https://oscarschecklist.com/', {
                                            method: 'POST',
                                            body: unfavFormData,
                                        }).catch(error => console.error('Error unfaving:', error));
                                    }
                                }
                            });
                        }
    
                        // Update the clicked button and list item
                        button.classList.toggle('mark-as-fav-button', !isFav);
                        button.classList.toggle('mark-as-unfav-button', isFav);
    
                        // button.childNodes.forEach(node => {
                        //     if (node.nodeType === Node.TEXT_NODE) {
                        //         node.textContent = isFav ? 'Unmark as Favourite' : 'Mark as Favourite';
                        //     }
                        // });
    
                        button.setAttribute('data-action', isFav ? 'unfav' : 'fav');
                        listItem.classList.toggle('fav', isFav);
    
                        return response.text();
                    })
                    .catch(error => {
                        console.error('Error:', error);
                    });
            });
        });
    }
    
    handleFavButtons(); // Initialize the button handler



    function handlepredictButtons() {
        document.querySelectorAll('.mark-as-predict-button, .mark-as-unpredict-button').forEach(function (button) {
            button.addEventListener('click', function (event) {
                event.preventDefault();
    
                var nominationId = button.getAttribute('data-nomination-id');
                var predictAction = button.getAttribute('data-action');
                var listItem = button.closest('li');
                var list = button.closest('ul'); // Scope to the parent list
    
                if (!listItem || !list) {
                    console.error('No containing <li> or <ul> element found for button:', button);
                    return;
                }
    
                var formData = new FormData();
                formData.append('predict_nom_id', nominationId);
                formData.append('predict_action', predictAction);
    
                fetch('https://oscarschecklist.com/', {
                    method: 'POST',
                    body: formData,
                })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
    
                        var ispredict = predictAction === 'predict';
    
                        if (ispredict) {
                            // Remove 'predict' from any other list items in the same list
                            list.querySelectorAll('li.predict').forEach(function (otherListItem) {
                                if (otherListItem !== listItem) {
                                    var otherButton = otherListItem.querySelector('.mark-as-unpredict-button');
                                    if (otherButton) {
                                        otherButton.classList.toggle('mark-as-predict-button', true);
                                        otherButton.classList.toggle('mark-as-unpredict-button', false);
                                        otherButton.setAttribute('data-action', 'predict');
    
                                        // Update button text
                                        // otherButton.childNodes.forEach(node => {
                                        //     if (node.nodeType === Node.TEXT_NODE) {
                                        //         node.textContent = 'Mark as predictourite';
                                        //     }
                                        // });
    
                                        // Toggle the 'predict' class on the <li> element
                                        otherListItem.classList.toggle('predict', false);
    
                                        // Simulate a POST request for the unpredictored item
                                        var unpredictFormData = new FormData();
                                        unpredictFormData.append('predict_nom_id', otherButton.getAttribute('data-nomination-id'));
                                        unpredictFormData.append('predict_action', 'unpredict');
    
                                        fetch('https://oscarschecklist.com/', {
                                            method: 'POST',
                                            body: unpredictFormData,
                                        }).catch(error => console.error('Error unpredicting:', error));
                                    }
                                }
                            });
                        }
    
                        // Update the clicked button and list item
                        button.classList.toggle('mark-as-predict-button', !ispredict);
                        button.classList.toggle('mark-as-unpredict-button', ispredict);
    
                        // button.childNodes.forEach(node => {
                        //     if (node.nodeType === Node.TEXT_NODE) {
                        //         node.textContent = ispredict ? 'Unmark as predictourite' : 'Mark as predictourite';
                        //     }
                        // });
    
                        button.setAttribute('data-action', ispredict ? 'unpredict' : 'predict');
                        listItem.classList.toggle('predict', ispredict);
    
                        return response.text();
                    })
                    .catch(error => {
                        console.error('Error:', error);
                    });
            });
        });
    }
    
    handlepredictButtons(); // Initialize the button handler

    
    
    
    // document.addEventListener('DOMContentLoaded', function() {
        // Select all .friend-item elements
        const friendItems = document.querySelectorAll('#friends-list .friend-item');
        // Select the heading element
        const progressHeading = document.querySelector('#your-progress h2');
    
        // Function to handle friend item click
        function handleFriendItemClick(item, index) {
            // Remove "active" class from all friend items
            friendItems.forEach(function(i) {
                i.classList.remove('active');
            });
    
            // Add "active" class to the clicked item
            item.classList.add('active');
    
            // Check if the clicked item is the first item or has the title "you"
            if (index === 0 || item.getAttribute('title').toLowerCase() === "you") {
                progressHeading.textContent = "Your Progress";
            } else {
                // Get the display name from the title attribute
                const displayName = item.getAttribute('title');
                // Update the heading text
                progressHeading.textContent = displayName + "'s Progress";
            }
        }
    
        // Add a click event listener to each .friend-item
        friendItems.forEach(function(item, index) {
            item.addEventListener('click', function() {
                handleFriendItemClick(item, index);
            });
        });
    
        // Function to update TOC and simulate first friend click if friendId is null
        function updateTOC(friendId = null) {
            // Rest of your updateTOC logic here...
            
            const toc = document.getElementById("toc");
            toc.innerHTML = ''; // Clear existing TOC
    
            const categories = document.querySelectorAll(".awards-category");
    
            // Sets to store unique film IDs and watched film IDs
            const uniqueFilmIds = new Set();
            const watchedFilmIds = new Set();
    
            // Variables to track TOC stats
            let totalTocItems = 0;
            let fullTocItems = 0;
    
            categories.forEach(function(category) {
                const categoryTitle = category.querySelector(".category-title");
                const categoryName = categoryTitle.querySelector("h2").textContent;
                const listItem = document.createElement("li");
                listItem.textContent = categoryName;
                listItem.classList.add(categoryName.replace(/\s+/g, '-')); // Add class with title name
    
                const ul = category.querySelector("ul"); // Get the UL under the H2
                let totalLiCount = 0;
                let watchedCount = 0;
                if (ul) { // Check if UL exists
                    const films = ul.querySelectorAll("li:not(li li)");
                    totalLiCount = films.length;
                    films.forEach(film => {
                        const filmClasses = Array.from(film.classList);
                        const filmIdClass = filmClasses.find(cls => cls.startsWith("film-id-"));
                        if (filmIdClass) {
                            uniqueFilmIds.add(filmIdClass); // Add film ID to unique film IDs set
                            if (friendId) {
                                // Check if the selected friend has watched this film
                                const friendsWatchedDiv = film.querySelector(".friends-watched");
                                if (friendsWatchedDiv && friendsWatchedDiv.querySelector(`.friend-id-${friendId}`)) {
                                    watchedFilmIds.add(filmIdClass); // Add to watched films for the selected friend
                                    watchedCount++;
                                }
                            } else if (film.classList.contains("watched")) {
                                watchedFilmIds.add(filmIdClass); // Add film ID to watched film IDs set if it has the "watched" class
                                watchedCount++;
                            }
                        }
                    });
                }
    
                // Update the TOC item
                const countContainer = document.createElement("span");
    
                const watchedSpan = document.createElement("span");
                watchedSpan.textContent = watchedCount; // Display watched count
                watchedSpan.classList.add("watched-count");
    
                const totalSpan = document.createElement("span");
                totalSpan.textContent = totalLiCount; // Display total count
                totalSpan.classList.add("total-count");
    
                countContainer.appendChild(watchedSpan);
                countContainer.appendChild(totalSpan);
                countContainer.classList.add("count-container");
    
                const progressBarContainer = document.createElement("div");
                progressBarContainer.classList.add("progress-bar-container");
    
                const linearProgressBar = document.createElement("div");
                linearProgressBar.classList.add("progress-bar");
                linearProgressBar.style.width = `${(watchedCount / totalLiCount) * 100}%`; // Update the progress bar width directly
    
                if (watchedCount === totalLiCount && totalLiCount !== 0) {
                    linearProgressBar.classList.add("full");
                    fullTocItems++; // Increment full TOC items count
                }
    
                progressBarContainer.appendChild(linearProgressBar);
                listItem.appendChild(countContainer); // Append count to the list item
                listItem.appendChild(progressBarContainer); // Append progress bar to the list item
    
                listItem.addEventListener("click", function() {
                    category.scrollIntoView({ behavior: "smooth", block: "start" });
                });
                toc.appendChild(listItem);
    
                totalTocItems++; // Increment total TOC items count
    
                // Conditionally update the circular progress bar for the category
                if (!friendId) {
                    const progressContainer = categoryTitle.querySelector(".circular-progress-container");
                    const progressBar = progressContainer.querySelector(".circular-progress");
                    const progressText = progressContainer.querySelector(".circular-progress-container .progress");
                    const totalText = progressContainer.querySelector(".circular-progress-container .total");
    
                    const progress = (watchedCount / totalLiCount) * 100;
                    progressBar.style.setProperty('--progress', `${progress}%`);
                    progressText.textContent = watchedCount;
                    totalText.textContent = totalLiCount;
    
                    if (watchedCount === totalLiCount) {
                        category.classList.add("complete");
                    } else {
                        category.classList.remove("complete");
                    }
                }
            });
    
            // Calculate total unique films and total unique watched films
            const totalUniqueFilms = uniqueFilmIds.size;
            const totalWatchedFilms = watchedFilmIds.size;
    
            // Calculate progress for films and categories
            const totalWatchedFilmsProgress = (totalWatchedFilms / totalUniqueFilms) * 100;
            const totalCategoriesProgress = (fullTocItems / totalTocItems) * 100;
    
            // Set CSS variables for progress
            document.documentElement.style.setProperty('--total-watched-films-progress', `${totalWatchedFilmsProgress}%`);
            document.documentElement.style.setProperty('--total-categories-progress', `${totalCategoriesProgress}%`);
    
            // Conditionally set total progress bars
                document.querySelector(".circular-progress-bar.films .total").innerHTML = totalUniqueFilms;
                document.querySelector(".circular-progress-bar.films .progress").innerHTML = totalWatchedFilms;
                document.querySelector(".circular-progress-bar.categories .total").innerHTML = totalTocItems;
                document.querySelector(".circular-progress-bar.categories .progress").innerHTML = fullTocItems;
    
                if (totalUniqueFilms === totalWatchedFilms) {
                    document.querySelector(".circular-progress-bar.films").classList.add("complete");
                } else {
                    document.querySelector(".circular-progress-bar.films").classList.remove("complete");
                }
                if (totalTocItems === fullTocItems) {
                    document.querySelector(".circular-progress-bar.categories").classList.add("complete");
                } else {
                    document.querySelector(".circular-progress-bar.categories").classList.remove("complete");
                }
    
                // Simulate click on the first friend item
                const firstFriendItem = friendItems[0];
                handleFriendItemClick(firstFriendItem, 0);
    
            // Reapply scroll event listener after updating TOC
            handleScroll();
        }
    
        // Expose updateTOC globally
        window.updateTOC = updateTOC;
    // });
    
    
    
    
    
    

    // Function to handle scroll events and update the active TOC item
    function handleScroll() {
        const tocItems = document.querySelectorAll("#toc li");
        const categories = document.querySelectorAll(".awards-category");

        const observerOptions = {
            root: null,
            rootMargin: '0px',
            threshold: 0.5 // Adjust this value as needed
        };

        const observer = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                const index = Array.from(categories).indexOf(entry.target);
                if (entry.isIntersecting) {
                    if (index !== -1) {
                        tocItems[index].classList.add('active');
                    }
                } else {
                    if (index !== -1) {
                        tocItems[index].classList.remove('active');
                    }
                }
            });
        }, observerOptions);

        categories.forEach(category => observer.observe(category));
    }

    



    
    // Function to handle favourite and hidden buttons
    function handleCategoryButtons() {
        document.querySelectorAll('.favourite-btn, .hidden-btn').forEach(function (button) {
            button.addEventListener('click', function (event) {
                event.preventDefault();

                const categoryElement = button.closest('.awards-category');
                const categorySlug = categoryElement.getAttribute('data-category-slug');
                const action = button.classList.contains('favourite-btn') ? 'favourite' : 'hidden-category';
                const isAdding = !categoryElement.classList.contains(action);
                const userId = (window.OscarsChecklist && OscarsChecklist.userId) ? OscarsChecklist.userId : null;
                // console.log('[CategoryBtn] Clicked:', {categoryElement, categorySlug, action, isAdding, userId, button});
                if (!userId) {
                    console.error('[CategoryBtn] User ID not found.');
                    return;
                }

                const formData = new FormData();
                formData.append('action', 'update_category');
                formData.append('user_id', userId);
                formData.append('category_slug', categorySlug);
                formData.append('category_action', action);
                formData.append('is_adding', isAdding);

                fetch('/wp-admin/admin-ajax.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    // console.log('[CategoryBtn] AJAX response:', response);
                    return response.json();
                })
                .then(data => {
                    // console.log('[CategoryBtn] AJAX data:', data);
                    if (!data.success) {
                        throw new Error('Failed to update category');
                    }
                    categoryElement.classList.toggle(action, isAdding);
                })
                .catch(error => {
                    console.error('[CategoryBtn] Error:', error);
                });
            });
        });
    }

    // Function to initialize classes on page load
    function initializeCategoryClasses() {
        const userId = (window.OscarsChecklist && OscarsChecklist.userId) ? OscarsChecklist.userId : null;
        // console.log('[InitCatClasses] userId:', userId);
        if (!userId) {
            console.error('[InitCatClasses] User ID not found.');
            return;
        }
        fetch(`/wp-admin/admin-ajax.php?action=get_user_data&user_id=${userId}`)
            .then(response => {
                // console.log('[InitCatClasses] AJAX response:', response);
                return response.json();
            })
            .then(userData => {
                // console.log('[InitCatClasses] userData:', userData);
                if (!userData || !userData['favourite-categories']) return;
                document.querySelectorAll('.awards-category').forEach(function (categoryElement) {
                    const categorySlug = categoryElement.getAttribute('data-category-slug');
                    if (userData['favourite-categories'] && userData['favourite-categories'].includes(categorySlug)) {
                        categoryElement.classList.add('favourite');
                        // console.log(`[InitCatClasses] Added .favourite to`, categoryElement);
                    }
                    if (userData['hidden-categories'] && userData['hidden-categories'].includes(categorySlug)) {
                        categoryElement.classList.add('hidden-category');
                        // console.log(`[InitCatClasses] Added .hidden-category to`, categoryElement);
                    }
                });
            })
            .catch(error => {
                console.error('[InitCatClasses] Error fetching user data:', error);
            });
    }

    // Initialize the buttons and classes
    handleCategoryButtons();
    initializeCategoryClasses();


    // Initialize watch buttons and TOC update
    handleWatchButtons();
    updateTOC();
});




let activeToggle = null;
const sidebar = document.getElementById('sidebar');

function handleToggle(buttonName) {
    if (activeToggle === buttonName) {
        // Same button → toggle open/close
        sidebar.classList.toggle('open');
        if (!sidebar.classList.contains('open')) {
            activeToggle = null; // reset when closing
            sidebar.classList.remove('progress-mode', 'watchlist-mode');
        }
    } else {
        // Different button → ensure open, switch mode
        sidebar.classList.add('open');
        sidebar.classList.remove('progress-mode', 'watchlist-mode'); // clear old mode
        sidebar.classList.add(buttonName + '-mode'); // e.g. 'progress-mode'
        activeToggle = buttonName;
    }
}

document.querySelector('.progress-toggle').addEventListener('click', function() {
    handleToggle('progress');
});

document.querySelector('.watchlist-toggle').addEventListener('click', function() {
    handleToggle('watchlist');
});



document.querySelectorAll('body:not(.logged-in) .buttons-cntr').forEach(function(element) {
    element.addEventListener('click', function() {
        document.querySelector('body').classList.add('open-logged-in-notice');
    });
});

const dismissButton = document.querySelector('.dismiss');
if (dismissButton) {
    dismissButton.addEventListener('click', function() {
        document.querySelector('body').classList.add('close-logged-in-notice');
    });
}





document.addEventListener('DOMContentLoaded', function() {
    document.addEventListener('click', function(event) {
        if (event.target.classList.contains('ct-register')) {
            event.preventDefault();
            // console.log('Redirecting to /register');
            window.location.href = '/register';
        }
    });
});


if (navigator.userAgent.includes("Safari") && !navigator.userAgent.includes("Chrome"))
    {
    document.documentElement.classList.add("safari-desktop");
}

/**
 * User Data Sync Manager
 * Syncs user JSON files from server to LocalStorage
 * Only downloads when server file is newer than cached version
 */
async function syncUserDataFiles() {
    try {
        // Get current user ID from window object
        const currentUserId = (window.OscarsChecklist && OscarsChecklist.userId) ? OscarsChecklist.userId : null;
        
        if (!currentUserId) {
            // console.log('[UserDataSync] No user logged in, skipping data sync');
            return;
        }

        // Get friend IDs from the DOM (they're already rendered in the friends list)
        const friendIds = getUserFriendIdsFromDOM();
        
        // Combine current user and friends
        const allUserIds = [currentUserId, ...friendIds];
        
        // console.log(`[UserDataSync] Starting sync for ${allUserIds.length} users (user ${currentUserId} + ${friendIds.length} friends: [${friendIds.join(', ')}])`);
        
        // Sync each user's data files
        for (const userId of allUserIds) {
            await syncUserFile(userId);
        }
        
        // console.log('[UserDataSync] Sync completed successfully');
        
    } catch (error) {
        console.error('[UserDataSync] Error syncing user data:', error);
    }
}

/**
 * Get friend IDs from the DOM
 * Extracts user IDs from the rendered friends list by parsing onclick attributes
 */
function getUserFriendIdsFromDOM() {
    const friendItems = document.querySelectorAll('#friends-list .friend-item');
    const friendIds = [];
    
    // console.log(`[UserDataSync] Found ${friendItems.length} friend items in DOM`);
    
    friendItems.forEach((item, index) => {
        // Friends have onclick="updateTOC(123)" where 123 is the user ID
        const onclick = item.getAttribute('onclick');
        if (onclick) {
            const match = onclick.match(/updateTOC\((\d+)\)/);
            if (match && match[1]) {
                const userId = parseInt(match[1]);
                friendIds.push(userId);
                // console.log(`[UserDataSync] Found friend ID: ${userId} from onclick attribute`);
            }
        }
    });
    
    // console.log(`[UserDataSync] Extracted ${friendIds.length} friend IDs: [${friendIds.join(', ')}]`);
    return friendIds;
}

/**
 * Sync individual user file
 * Downloads file from server and stores in LocalStorage with timestamp tracking
 */
async function syncUserFile(userId, suffix = '') {
    const filename = `user_${userId}${suffix}.json`;
    const fileUrl = `/wp-content/uploads/user_meta/${filename}`;
    const localStorageKey = `userdata_${userId}${suffix}`;
    
    try {
        // Fetch file with cache-busting to ensure fresh data on every page load
        const cacheBuster = `?t=${Date.now()}`;
        const response = await fetch(fileUrl + cacheBuster, {
            credentials: 'include',
            cache: 'no-store'
        });
        
        if (!response.ok) {
            // console.log(`[UserDataSync] ⚠ File not found: ${filename}`);
            return;
        }
        
        // Get server last modified timestamp from headers
        const serverLastModified = response.headers.get('Last-Modified');
        const serverTimestamp = serverLastModified ? new Date(serverLastModified).getTime() : Date.now();
        
        // Always download on every page load
        // console.log(`[UserDataSync] ⬇ Downloading ${filename}...`);
        
        const data = await response.text();
        
        // Verify it's valid JSON before storing
        let jsonData;
        try {
            jsonData = JSON.parse(data);
        } catch (e) {
            console.error(`[UserDataSync] ✗ Invalid JSON in ${filename}:`, e);
            return;
        }
        
        // Add timestamp to the data itself (simpler than separate meta file)
        jsonData._cachedTimestamp = serverTimestamp;
        jsonData._cachedDate = new Date().toISOString();
        
        // Store in LocalStorage
        const dataToStore = JSON.stringify(jsonData);
        localStorage.setItem(localStorageKey, dataToStore);
        
        // console.log(`[UserDataSync] ✓ ${filename} cached (${(dataToStore.length / 1024).toFixed(2)} KB, modified: ${new Date(serverTimestamp).toLocaleString()})`);

        
    } catch (error) {
        console.error(`[UserDataSync] ✗ Error syncing ${filename}:`, error);
    }
}

/**
 * Get user data from LocalStorage
 * Helper function to retrieve cached user data
 * @param {number} userId - The user ID
 * @param {string} suffix - Optional suffix like '_pred_fav'
 * @returns {object|null} User data object or null if not found
 */
function getUserDataFromCache(userId, suffix = '') {
    const localStorageKey = `userdata_${userId}${suffix}`;
    const cachedData = localStorage.getItem(localStorageKey);
    
    if (!cachedData) {
        // console.log(`[UserDataSync] No cached data for ${localStorageKey}`);
        return null;
    }
    
    try {
        const data = JSON.parse(cachedData);
        // Remove our internal cache metadata before returning
        delete data._cachedTimestamp;
        delete data._cachedDate;
        return data;
    } catch (error) {
        console.error(`[UserDataSync] Error parsing cached data for user ${userId}:`, error);
        return null;
    }
}

/**
 * Clear all cached user data
 * Utility function for debugging or resetting
 * @returns {array} Array of removed keys
 */
function clearUserDataCache() {
    const keys = Object.keys(localStorage);
    const removedKeys = [];
    
    keys.forEach(key => {
        if (key.startsWith('userdata_')) {
            localStorage.removeItem(key);
            removedKeys.push(key);
        }
    });
    
    // console.log(`[UserDataSync] Cleared ${removedKeys.length} cached user data files: [${removedKeys.join(', ')}]`);
    return removedKeys;
}

/**
 * Get info about all cached user data
 * Utility function to see what's cached
 * @returns {array} Array of cache info objects
 */
function getCacheInfo() {
    const keys = Object.keys(localStorage);
    const cacheInfo = [];
    
    keys.forEach(key => {
        if (key.startsWith('userdata_')) {
            const data = localStorage.getItem(key);
            if (data) {
                try {
                    const parsed = JSON.parse(data);
                    cacheInfo.push({
                        key: key,
                        size: (data.length / 1024).toFixed(2) + ' KB',
                        cachedDate: parsed._cachedDate || 'unknown',
                        username: parsed.username || 'unknown'
                    });
                } catch (e) {
                    cacheInfo.push({
                        key: key,
                        size: (data.length / 1024).toFixed(2) + ' KB',
                        error: 'Invalid JSON'
                    });
                }
            }
        }
    });
    
    console.table(cacheInfo);
    return cacheInfo;
}

/**
 * Apply watched status to film items from cached user data
 */
function applyWatchedStatusFromCache() {
    // Detect whether the page has any nomination items
    const hasNominations = document.querySelector('li[class*="film-id-"]');
    
    if (!hasNominations) {
        console.log('[WatchedStatus] No nomination items found on this page, skipping');
        return;
    }

    const currentUserId = (window.OscarsChecklist && OscarsChecklist.userId)
        ? OscarsChecklist.userId 
        : null;

    if (!currentUserId) {
        console.log('[WatchedStatus] No user logged in');
        return;
    }

    const userData = getUserDataFromCache(currentUserId);

    if (!userData || !userData.watched) {
        console.log('[WatchedStatus] No cached user data found');
        return;
    }

    const watchedFilmIds = new Set();
    userData.watched.forEach(film => {
        if (film['film-id']) watchedFilmIds.add(film['film-id']);
    });

    console.log(`[WatchedStatus] Found ${watchedFilmIds.size} watched films in cache`);

    const filmItems = document.querySelectorAll('li[class*="film-id-"]');
    let appliedCount = 0;

    filmItems.forEach(item => {
        const classList = Array.from(item.classList);
        const filmIdClass = classList.find(cls => cls.startsWith('film-id-'));

        if (filmIdClass) {
            const filmId = parseInt(filmIdClass.replace('film-id-', ''));
            if (watchedFilmIds.has(filmId)) {
                item.classList.add('test-watched');
                appliedCount++;
            }
        }
    });
}

// Initialize sync on page load
document.addEventListener('DOMContentLoaded', async function () {
    // First, sync the data files
    await syncUserDataFiles();
    
    // Then apply watched status from cache
    applyWatchedStatusFromCache();
});



