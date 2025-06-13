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
    
                fetch('https://stage2.oscarschecklist.com/', {
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
                    document.querySelectorAll('button[data-film-id="' + filmId + '"]').forEach(function(duplicateButton) {
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
    
                fetch('https://stage2.oscarschecklist.com/', {
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
    
                                        fetch('https://stage2.oscarschecklist.com/', {
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
    
                fetch('https://stage2.oscarschecklist.com/', {
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
    
                                        fetch('https://stage2.oscarschecklist.com/', {
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

    

    // Initialize watch buttons and TOC update
    handleWatchButtons();
    updateTOC();
});




document.querySelector('.progress-toggle').addEventListener('click', function() {
    document.getElementById('sidebar').classList.toggle('open');
});



document.querySelectorAll('body:not(.logged-in) .buttons-cntr').forEach(function(element) {
    element.addEventListener('click', function() {
        document.querySelector('body').classList.add('open-logged-in-notice');
    });
});

document.querySelector('.dismiss').addEventListener('click', function() {
    document.querySelector('body').classList.add('close-logged-in-notice');
});


// document.querySelector('.ct-account-item').addEventListener('click', function() {
//     const observer = new MutationObserver(function(mutations) {
//         mutations.forEach(function(mutation) {
//             if (mutation.addedNodes.length > 0) {
//                 const label = document.querySelector('label[for="user_login_register"]');
//                 if (label) {
//                     label.innerHTML = "Full Name";
//                     observer.disconnect();  // Stop observing after the change
//                 }
//             }
//         });
//     });

//     observer.observe(document.body, { childList: true, subtree: true });
// });





document.addEventListener('DOMContentLoaded', function() {
    document.addEventListener('click', function(event) {
        if (event.target.classList.contains('ct-register')) {
            event.preventDefault();
            console.log('Redirecting to /register');
            window.location.href = '/register';
        }
    });
});


if (navigator.userAgent.includes("Safari") && !navigator.userAgent.includes("Chrome"))
    {
    document.documentElement.classList.add("safari-desktop");
}