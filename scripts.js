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
    
            // Update progress heading only if it exists on the page
            if (progressHeading) {
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
                if (firstFriendItem) {
                    handleFriendItemClick(firstFriendItem, 0);
                }
    
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
            console.log('[UserDataSync] No current user ID found, skipping sync');
            return;
        }

        // Get friend IDs from the DOM (they're already rendered in the friends list)
        const friendIds = getUserFriendIdsFromDOM();
        console.log(`[UserDataSync] Found ${friendIds.length} friends to sync`);
        
        // Combine current user and friends
        const allUserIds = [currentUserId, ...friendIds];
        
        // Sync each user's data files
        for (const userId of allUserIds) {
            await syncUserFile(userId);
        }
        
        console.log('[UserDataSync] All user data synced successfully');
        
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
            // File doesn't exist - try to create it for the main user file (not pred_fav)
            if (!suffix && response.status === 404) {
                const initResponse = await fetch('/wp-admin/admin-ajax.php', {
                    method: 'POST',
                    credentials: 'include',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=init_user_data&user_id=${userId}`
                });
                
                if (initResponse.ok) {
                    const result = await initResponse.json();
                    if (result.success && result.data) {
                        // Store the newly created data in localStorage
                        const jsonData = result.data;
                        jsonData._cachedTimestamp = Date.now();
                        jsonData._cachedDate = new Date().toISOString();
                        const dataToStore = JSON.stringify(jsonData);
                        try {
                            localStorage.setItem(localStorageKey, dataToStore);
                        } catch (storageError) {
                            console.error(`[UserDataSync] Failed to store ${filename} in localStorage:`, storageError);
                        }
                        return;
                    }
                }
            }
            return;
        }
        
        // Get server last modified timestamp from headers
        const serverLastModified = response.headers.get('Last-Modified');
        const serverTimestamp = serverLastModified ? new Date(serverLastModified).getTime() : Date.now();
        
        const data = await response.text();
        
        // Verify it's valid JSON before storing
        let jsonData;
        try {
            jsonData = JSON.parse(data);
        } catch (e) {
            console.error(`[UserDataSync] Invalid JSON in ${filename}:`, e);
            return;
        }
        
        // Add timestamp to the data itself (simpler than separate meta file)
        jsonData._cachedTimestamp = serverTimestamp;
        jsonData._cachedDate = new Date().toISOString();
        
        // Store in LocalStorage
        const dataToStore = JSON.stringify(jsonData);
        try {
            localStorage.setItem(localStorageKey, dataToStore);
        } catch (storageError) {
            console.error(`[UserDataSync] Failed to store ${filename} in localStorage:`, storageError);
        }
        
    } catch (error) {
        console.error(`[UserDataSync] Error syncing ${filename}:`, error);
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
 * Apply watched, favourite, and prediction status to items from cached user data
 */
function applyUserStatusFromCache() {
    // Detect whether the page has any nomination items
    const hasNominations = document.querySelector('li[class*="film-id-"]');
    
    if (!hasNominations) {
        return;
    }

    const currentUserId = (window.OscarsChecklist && OscarsChecklist.userId)
        ? OscarsChecklist.userId 
        : null;

    if (!currentUserId) {
        return;
    }

    const userData = getUserDataFromCache(currentUserId);
    const predFavData = getUserDataFromCache(currentUserId, '_pred_fav');

    if (!userData) {
        return;
    }

    // Process watched films (by film-id)
    const watchedFilmIds = new Set();
    if (userData.watched && Array.isArray(userData.watched)) {
        userData.watched.forEach(film => {
            if (film['film-id']) watchedFilmIds.add(film['film-id']);
        });
    }

    // Process favourites (by nomination-id) from pred_fav file
    const favouriteNominationIds = new Set();
    if (predFavData && predFavData.favourites && Array.isArray(predFavData.favourites)) {
        predFavData.favourites.forEach(nominationId => {
            favouriteNominationIds.add(parseInt(nominationId));
        });
    }

    // Process predictions (by nomination-id) from pred_fav file
    const predictionNominationIds = new Set();
    if (predFavData && predFavData.predictions && Array.isArray(predFavData.predictions)) {
        predFavData.predictions.forEach(nominationId => {
            predictionNominationIds.add(parseInt(nominationId));
        });
    }

    const filmItems = document.querySelectorAll('li[class*="film-id-"]');
    let watchedCount = 0;
    let favCount = 0;
    let predictCount = 0;

    filmItems.forEach(item => {
        const classList = Array.from(item.classList);
        const filmIdClass = classList.find(cls => cls.startsWith('film-id-'));

        // Apply watched status (by film-id)
        if (filmIdClass) {
            const filmId = parseInt(filmIdClass.replace('film-id-', ''));
            if (watchedFilmIds.has(filmId)) {
                item.classList.add('watched');
                watchedCount++;
                
                // Update watched button
                const watchedButton = item.querySelector('.mark-as-watched-button');
                if (watchedButton) {
                    watchedButton.classList.remove('mark-as-watched-button');
                    watchedButton.classList.add('mark-as-unwatched-button');
                    watchedButton.setAttribute('data-action', 'unwatched');
                }
            }
        }

        // Apply favourite and prediction status (by nomination-id)
        const nominationId = item.id ? parseInt(item.id.replace('nomination-', '')) : null;
        if (nominationId) {
            if (favouriteNominationIds.has(nominationId)) {
                item.classList.add('fav');
                favCount++;
                
                // Update favourite button
                const favButton = item.querySelector('.mark-as-fav-button');
                if (favButton) {
                    favButton.classList.remove('mark-as-fav-button');
                    favButton.classList.add('mark-as-unfav-button');
                    favButton.setAttribute('data-action', 'unfav');
                }
            }
            if (predictionNominationIds.has(nominationId)) {
                item.classList.add('predict');
                predictCount++;
                
                // Update prediction button
                const predictButton = item.querySelector('.mark-as-predict-button');
                if (predictButton) {
                    predictButton.classList.remove('mark-as-predict-button');
                    predictButton.classList.add('mark-as-unpredict-button');
                    predictButton.setAttribute('data-action', 'unpredict');
                }
            }
        }
    });
}

/**
 * Apply friends' watched status to film items
 * Populates the .friends-watched divs with avatars of friends who have watched each film
 */
async function applyFriendsWatchedStatus() {
    const friendIds = getUserFriendIdsFromDOM();
    
    if (friendIds.length === 0) {
        console.log('[FriendsWatched] No friends found in DOM, skipping');
        return;
    }
    
    console.log(`[FriendsWatched] Applying watched status for ${friendIds.length} friends`);

    // Get all film items on the page
    const filmItems = document.querySelectorAll('li[class*="film-id-"]');
    
    filmItems.forEach(item => {
        const classList = Array.from(item.classList);
        const filmIdClass = classList.find(cls => cls.startsWith('film-id-'));
        
        if (!filmIdClass) return;
        
        const filmId = parseInt(filmIdClass.replace('film-id-', ''));
        const friendsWatchedContainer = item.querySelector('.friends-watched');
        
        if (!friendsWatchedContainer) return;
        
        // Clear any existing content
        friendsWatchedContainer.innerHTML = '';
        
        // Check each friend to see if they've watched this film
        friendIds.forEach(friendId => {
            const friendData = getUserDataFromCache(friendId);
            
            if (!friendData || !friendData.watched) return;
            
            // Check if friend has watched this film
            const hasWatched = friendData.watched.some(film => 
                film['film-id'] && film['film-id'] === filmId
            );
            
            if (hasWatched) {
                // Get friend info from the friend list item
                const friendItem = document.querySelector(`#friends-list .friend-item[onclick*="updateTOC(${friendId})"]`);
                
                if (friendItem) {
                    const displayName = friendItem.getAttribute('title') || 'Friend';
                    const avatarImg = friendItem.querySelector('img');
                    
                    // Get the random color number from the friend item's inline style
                    const randomcolornum = friendItem.style.getPropertyValue('--randomcolornum');
                    
                    // Get initials from the existing friend-initials element
                    const initialsElement = friendItem.querySelector('.friend-initials');
                    const initials = initialsElement ? initialsElement.textContent : '';
                    
                    // Create avatar element
                    const avatarDiv = document.createElement('div');
                    avatarDiv.className = `friend-avatar friend-id-${friendId}`;
                    avatarDiv.style.setProperty('--randomcolornum', randomcolornum);
                    
                    // Clone the avatar image if it exists
                    if (avatarImg) {
                        const clonedAvatar = avatarImg.cloneNode(true);
                        avatarDiv.appendChild(clonedAvatar);
                        clonedAvatar.classList.remove('friend-avatar');
                    }
                    
                    // Add initials
                    const initialsDiv = document.createElement('div');
                    initialsDiv.className = 'friend-initials';
                    initialsDiv.title = displayName;
                    initialsDiv.textContent = initials;
                    avatarDiv.appendChild(initialsDiv);
                    
                    friendsWatchedContainer.appendChild(avatarDiv);
                }
            }
        });
    });
}

/**
 * Populate watchlist from cached user data
 * Reads the watchlist from localStorage and populates the .watchlist-cntr container
 */
function populateWatchlistFromCache() {
    const watchlistCntr = document.querySelector('.watchlist-cntr');
    if (!watchlistCntr) {
        return;
    }

    const currentUserId = (window.OscarsChecklist && OscarsChecklist.userId)
        ? OscarsChecklist.userId 
        : null;

    if (!currentUserId) {
        return;
    }

    const userData = getUserDataFromCache(currentUserId);

    if (!userData || !userData.watchlist || !Array.isArray(userData.watchlist)) {
        return;
    }

    // Apply watchlist settings classes based on user data
    // Default to true if not set
    const settings = {
        'this_page_only': userData.this_page_only !== false, // default true
        'auto_remove_watched': userData.auto_remove_watched !== false, // default true
        'compact_view': userData.compact_view !== false // default true
    };

    // Add classes for enabled settings
    Object.keys(settings).forEach(settingKey => {
        if (settings[settingKey]) {
            watchlistCntr.classList.add(settingKey);
        } else {
            watchlistCntr.classList.remove(settingKey);
        }
    });

    // Check if watchlist already exists, otherwise create it
    let watchlistUl = watchlistCntr.querySelector('.watchlist');
    
    if (!watchlistUl) {
        watchlistUl = document.createElement('ul');
        watchlistUl.className = 'watchlist';
        watchlistCntr.appendChild(watchlistUl);
    }

    // Clear existing items (in case PHP rendered some)
    watchlistUl.innerHTML = '';

    // Build array of watched film IDs for quick lookup
    const watchedFilmIds = new Set();
    if (userData.watched && Array.isArray(userData.watched)) {
        userData.watched.forEach(film => {
            if (film['film-id']) watchedFilmIds.add(film['film-id']);
        });
    }

    // SVG icons for buttons
    const SVG_WATCHED = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><path d="M528 320C528 205.1 434.9 112 320 112C205.1 112 112 205.1 112 320C112 434.9 205.1 528 320 528C434.9 528 528 434.9 528 320zM64 320C64 178.6 178.6 64 320 64C461.4 64 576 178.6 576 320C576 461.4 461.4 576 320 576C178.6 576 64 461.4 64 320z"></path></svg><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><path d="M320 112C434.9 112 528 205.1 528 320C528 434.9 434.9 528 320 528C205.1 528 112 434.9 112 320C112 205.1 205.1 112 320 112zM320 576C461.4 576 576 461.4 576 320C576 178.6 461.4 64 320 64C178.6 64 64 178.6 64 320C64 461.4 178.6 576 320 576zM404.4 276.7C411.4 265.5 408 250.7 396.8 243.6C385.6 236.5 370.8 240 363.7 251.2L302.3 349.5L275.3 313.5C267.3 302.9 252.3 300.7 241.7 308.7C231.1 316.7 228.9 331.7 236.9 342.3L284.9 406.3C289.6 412.6 297.2 416.2 305.1 415.9C313 415.6 320.2 411.4 324.4 404.6L404.4 276.6z"></path></svg><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><path d="M320 576C461.4 576 576 461.4 576 320C576 178.6 461.4 64 320 64C178.6 64 64 178.6 64 320C64 461.4 178.6 576 320 576zM404.4 276.7L324.4 404.7C320.2 411.4 313 415.6 305.1 416C297.2 416.4 289.6 412.8 284.9 406.4L236.9 342.4C228.9 331.8 231.1 316.8 241.7 308.8C252.3 300.8 267.3 303 275.3 313.6L302.3 349.6L363.7 251.3C370.7 240.1 385.5 236.6 396.8 243.7C408.1 250.8 411.5 265.5 404.4 276.8z"></path></svg>';
    const SVG_REMOVE = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640"><path d="M183.1 137.4C170.6 124.9 150.3 124.9 137.8 137.4C125.3 149.9 125.3 170.2 137.8 182.7L275.2 320L137.9 457.4C125.4 469.9 125.4 490.2 137.9 502.7C150.4 515.2 170.7 515.2 183.2 502.7L320.5 365.3L457.9 502.6C470.4 515.1 490.7 515.1 503.2 502.6C515.7 490.1 515.7 469.8 503.2 457.3L365.8 320L503.1 182.6C515.6 170.1 515.6 149.8 503.1 137.3C490.6 124.8 470.3 124.8 457.8 137.3L320.5 274.7L183.1 137.4z"></path></svg>';

    // Build a Set of all film IDs on the current page
    const filmIdsOnPage = new Set();
    document.querySelectorAll('li[class*="film-id-"]').forEach(item => {
        const classList = Array.from(item.classList);
        const filmIdClass = classList.find(cls => cls.startsWith('film-id-'));
        if (filmIdClass) {
            const filmId = parseInt(filmIdClass.replace('film-id-', ''));
            filmIdsOnPage.add(filmId);
        }
    });

    // Process each watchlist item
    userData.watchlist.forEach(watchlistItem => {
        const filmId = parseInt(watchlistItem['film-id']);
        const order = watchlistItem['order'];
        
        if (!filmId) return;

        // Check if this film is on the current page
        const isOnCurrentPage = filmIdsOnPage.has(filmId);

        // Find ALL instances of the film on the page
        const filmElements = document.querySelectorAll(`li.film-id-${filmId}`);

        const isWatched = watchedFilmIds.has(filmId);
        
        // Variables for film details
        let filmName = 'Unknown Film';
        let filmLink = '#';
        let posterSrc = '';
        let posterAlt = '';

        // If film is on the current page, extract details from DOM
        if (filmElements.length > 0) {
            const firstFilmElement = filmElements[0];
            const posterImg = firstFilmElement.querySelector('.film-poster img');
            const filmNameLink = firstFilmElement.querySelector('.film-name');
            filmName = filmNameLink ? filmNameLink.textContent.trim() : 'Unknown Film';
            filmLink = filmNameLink ? filmNameLink.href : '#';
            posterSrc = posterImg ? posterImg.src : '';
            posterAlt = posterImg ? posterImg.alt : filmName;
        } else {
            // Film not on current page - use saved details from JSON
            if (watchlistItem['film-name']) {
                filmName = watchlistItem['film-name'];
            } else {
                filmName = `Film ${filmId}`;
            }
            
            if (watchlistItem['film-url']) {
                filmLink = `/films/${watchlistItem['film-url']}/`;
            } else {
                filmLink = `/?p=${filmId}`; // Fallback to WordPress post link
            }
            // No poster available for films not on page
        }

        // Create watchlist item
        const li = document.createElement('li');
        li.className = isWatched ? 'watched' : 'unwatched';
        
        // Add current-page class if film is on current page
        if (isOnCurrentPage) {
            li.classList.add('current-page');
        }
        
        li.setAttribute('data-film-id', filmId);
        if (order) {
            li.style.setProperty('--order', order);
        }

        // Add poster if available
        if (posterSrc) {
            const img = document.createElement('img');
            img.src = posterSrc;
            img.alt = posterAlt;
            img.decoding = 'async';
            li.appendChild(img);
        }

        // Add film title link
        const titleLink = document.createElement('a');
        titleLink.className = 'film-title';
        titleLink.href = filmLink;
        titleLink.textContent = filmName;
        li.appendChild(titleLink);

        // Add watched button
        const watchedBtn = document.createElement('button');
        watchedBtn.type = 'button';
        watchedBtn.title = 'Watched';
        watchedBtn.setAttribute('data-film-id', filmId);
        if (isWatched) {
            watchedBtn.className = 'mark-as-unwatched-button';
            watchedBtn.setAttribute('data-action', 'unwatched');
        } else {
            watchedBtn.className = 'mark-as-watched-button';
            watchedBtn.setAttribute('data-action', 'watched');
        }
        watchedBtn.innerHTML = SVG_WATCHED;
        li.appendChild(watchedBtn);

        // Add remove button
        const removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.title = 'Remove from Watchlist';
        removeBtn.className = 'remove-from-watchlist-button';
        removeBtn.setAttribute('data-film-id', filmId);
        removeBtn.setAttribute('data-action', 'remove');
        removeBtn.innerHTML = SVG_REMOVE;
        li.appendChild(removeBtn);

        // Add to watchlist
        watchlistUl.appendChild(li);

        // Update ALL instances of the film on the page to mark as in watchlist (if film is on page)
        if (isOnCurrentPage && filmElements.length > 0) {
            filmElements.forEach(filmElement => {
                filmElement.classList.add('in-watchlist');
                
                // Update watchlist button on each instance
                const watchlistButton = filmElement.querySelector('.mark-as-watchlist-button');
                if (watchlistButton) {
                    watchlistButton.classList.remove('mark-as-watchlist-button');
                    watchlistButton.classList.add('mark-as-unwatchlist-button');
                    watchlistButton.setAttribute('data-action', 'unwatchlist');
                    watchlistButton.setAttribute('title', 'Remove from Watchlist');
                }
            });
        }
    });
    
    // After populating, add reorder buttons to each item
    addReorderButtonsToWatchlist();
    
    // Update the watchlist badge count
    if (typeof window.updateWatchlistBadge === 'function') {
        window.updateWatchlistBadge();
    }
}

/**
 * Add reorder buttons (up, down, top) to watchlist items
 */
function addReorderButtonsToWatchlist() {
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

    // Add buttons to all existing items
    watchlistUl.querySelectorAll('li[data-film-id]').forEach(addReorderButtons);

    // Watch for new items being added and add buttons to them too
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
}

/**
 * Setup watchlist button interactions
 * Handles watched button clicks in watchlist and auto-remove functionality
 */
function setupWatchlistInteractions() {
    // Handle watched button clicks in watchlist
    document.addEventListener('click', function(e) {
        const watchlistItem = e.target.closest('.watchlist li[data-film-id]');
        if (!watchlistItem) return;

        const watchedBtn = e.target.closest('.mark-as-watched-button, .mark-as-unwatched-button');
        if (!watchedBtn) return;

        const filmId = watchlistItem.getAttribute('data-film-id');
        const action = watchedBtn.getAttribute('data-action');

        // Find corresponding button on the page and trigger it
        const pageButton = document.querySelector(`li.film-id-${filmId} .mark-as-watched-button, li.film-id-${filmId} .mark-as-unwatched-button`);
        if (pageButton) {
            pageButton.click();
        }

        // Update watchlist item watched status
        const isWatched = action === 'watched';
        watchlistItem.classList.toggle('watched', isWatched);
        watchlistItem.classList.toggle('unwatched', !isWatched);

        // Update button in watchlist
        if (isWatched) {
            watchedBtn.classList.remove('mark-as-watched-button');
            watchedBtn.classList.add('mark-as-unwatched-button');
            watchedBtn.setAttribute('data-action', 'unwatched');
        } else {
            watchedBtn.classList.remove('mark-as-unwatched-button');
            watchedBtn.classList.add('mark-as-watched-button');
            watchedBtn.setAttribute('data-action', 'watched');
        }

        // Auto-remove if setting is enabled and film was marked as watched
        if (isWatched) {
            const watchlistCntr = document.querySelector('.watchlist-cntr');
            if (watchlistCntr && watchlistCntr.classList.contains('auto_remove_watched')) {
                // Trigger remove button
                const removeBtn = watchlistItem.querySelector('.remove-from-watchlist-button');
                if (removeBtn) {
                    setTimeout(() => removeBtn.click(), 300); // Small delay for visual feedback
                }
            }
        }
    });
}

// Initialize sync on page load
document.addEventListener('DOMContentLoaded', async function () {
    // First, sync the current user's data file (friends will sync after their list loads)
    const currentUserId = (window.OscarsChecklist && OscarsChecklist.userId) ? OscarsChecklist.userId : null;
    if (currentUserId) {
        await syncUserFile(currentUserId);
        await syncUserFile(currentUserId, '_pred_fav');
    }
    
    // Then apply user status (watched, favourites, predictions) from cache
    applyUserStatusFromCache();
    
    // Update TOC and progress indicators after applying watched status
    try {
        if (typeof window.updateTOC === 'function') {
            window.updateTOC();
        }
    } catch (error) {
        console.error('[App] Error updating TOC (non-critical):', error);
    }
    
    // Then populate watchlist from cache
    populateWatchlistFromCache();
    
    // Setup watchlist interactions (watched buttons, auto-remove)
    setupWatchlistInteractions();
    
    // Then apply friends' watched status
    await applyFriendsWatchedStatus();
});

// Listen for friends list loaded event and sync friends' data
document.addEventListener('friendsListLoaded', async function () {
    console.log('[UserDataSync] Friends list loaded, syncing friends data...');
    
    // Get friend IDs from the now-loaded DOM
    const friendIds = getUserFriendIdsFromDOM();
    
    // Sync each friend's data file (both main and pred_fav)
    for (const userId of friendIds) {
        await syncUserFile(userId);
        await syncUserFile(userId, '_pred_fav');
    }
    
    console.log('[UserDataSync] Friends data sync complete');
    
    // Re-apply friends' watched status with the fresh data
    await applyFriendsWatchedStatus();
});



