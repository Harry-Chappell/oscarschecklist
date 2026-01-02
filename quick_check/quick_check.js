document.addEventListener('DOMContentLoaded', function() {
    const modal = document.querySelector('.quick-check-modal');
    const triggerButton = document.querySelector('.quick-check-trigger');
    const closeButton = document.querySelector('.quick-check-close');
    const container = document.querySelector('.quick-check-container');
    if (!container) return;
    
    const cardsWrapper = container.querySelector('.cards-wrapper');
    const resetButton = container.querySelector('.reset-button');
    
    let cards = [];
    let currentCardIndex = 0;
    let inactivityTimer = null;
    let isDragging = false;
    let startX = 0;
    let startY = 0;
    let currentX = 0;
    let currentY = 0;
    let initialX = 0;
    let initialY = 0;
    let undoStack = [];
    let isInitialized = false;
    let totalFilms = 0;
    let watchedFilms = 0;
    let totalCategories = 0;
    let completedCategories = 0;
    let categoryElements = [];
    
    // SessionStorage management
    const SESSION_KEY = 'quickCheckWatched';
    
    function getWatchedFromSession() {
        const data = sessionStorage.getItem(SESSION_KEY);
        return data ? JSON.parse(data) : { watched: [] };
    }
    
    function saveWatchedToSession(filmData) {
        const data = getWatchedFromSession();
        // Check if film already exists
        const exists = data.watched.some(f => f['film-id'] === filmData['film-id']);
        if (!exists) {
            data.watched.push(filmData);
            sessionStorage.setItem(SESSION_KEY, JSON.stringify(data));
        }
        resetInactivityTimer();
    }
    
    function outputAndClearSession() {
        const data = getWatchedFromSession();
        if (data.watched.length > 0) {
            console.log('Quick Check Results:', JSON.stringify(data, null, 2));
            sessionStorage.removeItem(SESSION_KEY);
        }
    }
    
    function resetInactivityTimer() {
        if (inactivityTimer) {
            clearTimeout(inactivityTimer);
        }
        inactivityTimer = setTimeout(() => {
            outputAndClearSession();
        }, 2000);
    }
    
    // Function to calculate completed categories
    function calculateCompletedCategories() {
        let completed = 0;
        
        categoryElements.forEach(categoryEl => {
            // Get all LI elements in this nominations-list
            const allLIs = categoryEl.querySelectorAll('li');
            if (allLIs.length === 0) return;
            
            // Check if ALL li elements have .watched class
            const allWatched = Array.from(allLIs).every(liEl => {
                return liEl.classList.contains('watched');
            });
            
            if (allWatched) {
                completed++;
            }
        });
        
        return completed;
    }
    
    // Modal controls
    if (triggerButton) {
        triggerButton.addEventListener('click', function() {
            modal.style.display = 'block';
            document.body.style.overflow = 'hidden';
            if (!isInitialized) {
                initializeWhenReady();
            }
        });
    }
    
    if (closeButton) {
        closeButton.addEventListener('click', closeModal);
    }
    
    // Close on overlay click
    const overlay = document.querySelector('.quick-check-modal-overlay');
    if (overlay) {
        overlay.addEventListener('click', closeModal);
    }
    
    // Close on escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modal.style.display === 'block') {
            closeModal();
        }
    });
    
    function closeModal() {
        modal.style.display = 'none';
        document.body.style.overflow = '';
        outputAndClearSession();
    }
    
    // Progress ring update
    function updateProgressRings() {
        // Calculate watched films progress (including already watched + newly swiped right)
        const sessionData = getWatchedFromSession();
        const newlyWatched = sessionData.watched.length;
        const watchedCount = watchedFilms + newlyWatched;
        const watchedPercent = totalFilms > 0 ? (watchedCount / totalFilms) * 100 : 0;
        
        const watchedRing = document.querySelector('[data-progress="watched"]');
        const watchedText = document.getElementById('watched-count');
        if (watchedRing && watchedText) {
            const circumference = 2 * Math.PI * 40;
            const offset = circumference - (watchedPercent / 100) * circumference;
            watchedRing.style.strokeDashoffset = offset;
            watchedText.textContent = watchedCount + '/' + totalFilms;
        }
        
        // Calculate completed categories based on actual category completion
        const categoryRing = document.querySelector('[data-progress="categories"]');
        const categoryText = document.getElementById('category-count');
        if (categoryRing && categoryText) {
            const completedCount = calculateCompletedCategories();
            const categoryPercent = totalCategories > 0 ? (completedCount / totalCategories) * 100 : 0;
            const circumference = 2 * Math.PI * 40;
            const offset = circumference - (categoryPercent / 100) * circumference;
            categoryRing.style.strokeDashoffset = offset;
            categoryText.textContent = completedCount + '/' + totalCategories;
        }
    }
    
    // Wait for watched classes to be applied before building cards
    function initializeWhenReady() {
        if (isInitialized) return;
        
        // Check if watched classes have been applied
        const hasWatchedClass = document.querySelector('li[data-film-id].watched');
        const hasFilmElements = document.querySelectorAll('li[data-film-id]').length > 0;
        
        if (hasFilmElements) {
            buildCards();
            isInitialized = true;
        }
    }
    
    // Listen for the custom event dispatched when watched classes are applied
    let qcTimerStart = performance.now();
    document.addEventListener('watchedClassesApplied', function() {
        initializeWhenReady();
    });
    
    // Fallback: also try after a short delay if event doesn't fire
    setTimeout(initializeWhenReady, 1000);
    
    // Build cards from film data
    function buildCards() {
        try {
            // Get the all-films-section container
            const allFilmsSection = document.querySelector('.all-films-section');
            if (!allFilmsSection) {
                console.warn('Quick Check: .all-films-section not found');
                buildFallbackCards();
                return;
            }
            
            // Get all film <li> elements from .all-films-section
            const allFilmElements = allFilmsSection.querySelectorAll('li[data-film-id]');
            const unwatchedFilmElements = allFilmsSection.querySelectorAll('li[data-film-id]:not(.watched)');
            
            if (unwatchedFilmElements.length === 0) {
                console.warn('Quick Check: No unwatched films found');
                buildFallbackCards();
                return;
            }
            
            // Categories are the UL elements with class nominations-list
            const nominationLists = document.querySelectorAll('ul.nominations-list');
            categoryElements = Array.from(nominationLists).filter(ul => 
                ul.querySelectorAll('li[data-film-id]').length > 0
            );
            totalCategories = categoryElements.length;
            
            // Set film counts
            totalFilms = allFilmElements.length;
            watchedFilms = allFilmElements.length - unwatchedFilmElements.length;
            
            // Clear the cards wrapper
            cardsWrapper.innerHTML = '';
            
            // Clone and add unwatched film <li> elements as cards (reversed so first is on top)
            Array.from(unwatchedFilmElements).reverse().forEach((filmEl, index) => {
                // Invert the counter so it counts up (0/7, 1/7, 2/7...)
                const invertedIndex = unwatchedFilmElements.length - 2 - index;
                const clonedCard = createCardFromFilmElement(filmEl, invertedIndex, unwatchedFilmElements.length);
                cardsWrapper.appendChild(clonedCard);
            });
            
            cards = Array.from(cardsWrapper.querySelectorAll('.card'));
            currentCardIndex = cards.length - 1;
            
            // Add indicator cards (not part of swipeable cards array)
            createIndicatorCards();
            
            // Initialize card states and listeners
            init();
            
            // Initialize progress rings
            updateProgressRings();
        } catch (error) {
            console.error('Quick Check: Error building cards', error);
            buildFallbackCards();
        }
    }
    
    // Build fallback cards if film data unavailable
    function buildFallbackCards() {
        cardsWrapper.innerHTML = '';
        totalFilms = 5;
        
        for (let i = 5; i >= 1; i--) {
            const card = createFallbackCard(i, 5);
            cardsWrapper.appendChild(card);
        }
        
        cards = Array.from(cardsWrapper.querySelectorAll('.card'));
        currentCardIndex = cards.length - 1;
        
        createIndicatorCards();
        init();
        updateProgressRings();
    }
    
    // Create indicator cards on left and right
    function createIndicatorCards() {
        // Remove existing indicator cards if any
        const existingLeft = document.querySelector('.indicator-card-left');
        const existingRight = document.querySelector('.indicator-card-right');
        if (existingLeft) existingLeft.remove();
        if (existingRight) existingRight.remove();
        
        // Create left indicator card (Not Watched)
        const leftCard = document.createElement('div');
        leftCard.className = 'indicator-card indicator-card-left';
        leftCard.innerHTML = '<span>NOT WATCHED</span>';
        cardsWrapper.appendChild(leftCard);
        
        // Create right indicator card (Watched)
        const rightCard = document.createElement('div');
        rightCard.className = 'indicator-card indicator-card-right';
        rightCard.innerHTML = '<span>WATCHED</span>';
        cardsWrapper.appendChild(rightCard);
    }
    
    // Create a card from film <li> element by cloning its contents
    function createCardFromFilmElement(filmEl, index, total) {
        // Create the card wrapper
        const card = document.createElement('div');
        card.className = 'card';
        const filmId = filmEl.getAttribute('data-film-id');
        card.setAttribute('data-card', index + 1);
        card.setAttribute('data-film-id', filmId);
        
        // Get film data from an element on the page with matching data-film-id
        let filmName = '';
        let filmSlug = '';
        let filmYear = '';
        
        // Try to find a button with film data attributes
        const filmDataElement = document.querySelector(`[data-film-id="${filmId}"][data-film-name]`);
        if (filmDataElement) {
            filmName = filmDataElement.getAttribute('data-film-name') || '';
            filmSlug = filmDataElement.getAttribute('data-film-slug') || '';
            filmYear = filmDataElement.getAttribute('data-film-year') || '';
        }
        
        // Set film data attributes on the card
        if (filmName) card.setAttribute('data-film-name', filmName);
        if (filmSlug) card.setAttribute('data-film-slug', filmSlug);
        if (filmYear) card.setAttribute('data-film-year', filmYear);
        
        // Clone the contents of the <li> element
        const contentWrapper = document.createElement('div');
        contentWrapper.className = 'quick-check-film-card';
        contentWrapper.innerHTML = filmEl.innerHTML;
        
        // Remove the friends-watched section
        const friendsWatched = contentWrapper.querySelector('.friends-watched');
        if (friendsWatched) {
            friendsWatched.remove();
        }
        
        // Remove all links and wrap film title in span
        const links = contentWrapper.querySelectorAll('a');
        links.forEach(link => {
            // Create a span instead of text node
            const span = document.createElement('span');
            span.classList.add('film-name');
            span.textContent = link.textContent;
            link.parentNode.replaceChild(span, link);
        });
        
        // Wrap any remaining film name text in span if not already wrapped
        const filmNameEl = contentWrapper.querySelector('.film-name');
        if (filmNameEl) {
            const childElements = Array.from(filmNameEl.childNodes);
            childElements.forEach(node => {
                if (node.nodeType === Node.TEXT_NODE && node.textContent.trim()) {
                    const span = document.createElement('span');
                    span.textContent = node.textContent;
                    node.parentNode.replaceChild(span, node);
                }
            });
        }
        
        // Remove all buttons
        const buttons = contentWrapper.querySelectorAll('button');
        buttons.forEach(button => {
            button.remove();
        });
        
        // Remove buttons container
        const buttonsContainer = contentWrapper.querySelector('.buttons-cntr');
        if (buttonsContainer) {
            buttonsContainer.remove();
        }
        
        // Replace details.film-categories-details with just the ul.categories-list
        const categoriesDetails = contentWrapper.querySelector('.film-categories-details');
        if (categoriesDetails) {
            const categoriesList = categoriesDetails.querySelector('ul.categories-list');
            if (categoriesList) {
                // Clone the ul element
                const clonedList = categoriesList.cloneNode(true);
                // Replace the entire details element with just the ul
                categoriesDetails.parentNode.replaceChild(clonedList, categoriesDetails);
            } else {
                // If no ul found, just remove the details element
                categoriesDetails.remove();
            }
        }
        
        // Store counter info as data attribute for external display
        card.setAttribute('data-counter', `${index + 1} / ${total}`);
        
        card.appendChild(contentWrapper);
        
        // Add swipe indicator overlays
        const leftIndicator = document.createElement('div');
        leftIndicator.className = 'swipe-indicator swipe-indicator-left';
        leftIndicator.innerHTML = `
            <div class="swipe-indicator-icon">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                    <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
                </svg>
            </div>
            <span>NOT<br>WATCHED<br>YET</span>
        `;
        card.appendChild(leftIndicator);
        
        const rightIndicator = document.createElement('div');
        rightIndicator.className = 'swipe-indicator swipe-indicator-right';
        rightIndicator.innerHTML = `
            <div class="swipe-indicator-icon">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                    <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>
                </svg>
            </div>
            <span>WATCHED</span>
        `;
        card.appendChild(rightIndicator);
        
        return card;
    }
    
    // Create fallback card
    function createFallbackCard(cardNum, total) {
        const card = document.createElement('div');
        card.className = 'card';
        card.setAttribute('data-card', cardNum);
        
        const cardContent = document.createElement('div');
        cardContent.className = 'card-content';
        
        const titleEl = document.createElement('h2');
        titleEl.textContent = `Card ${cardNum} / ${total}`;
        cardContent.appendChild(titleEl);
        
        const instructionEl = document.createElement('p');
        instructionEl.textContent = 'Swipe left or right';
        cardContent.appendChild(instructionEl);
        
        card.appendChild(cardContent);
        
        // Add swipe indicator overlays
        const leftIndicator = document.createElement('div');
        leftIndicator.className = 'swipe-indicator swipe-indicator-left';
        leftIndicator.innerHTML = `
            <div class="swipe-indicator-icon">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                    <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
                </svg>
            </div>
            <span>NOT<br>WATCHED<br>YET</span>
        `;
        card.appendChild(leftIndicator);
        
        const rightIndicator = document.createElement('div');
        rightIndicator.className = 'swipe-indicator swipe-indicator-right';
        rightIndicator.innerHTML = `
            <div class="swipe-indicator-icon">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                    <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>
                </svg>
            </div>
            <span>WATCHED</span>
        `;
        card.appendChild(rightIndicator);
        
        return card;
    }
    
    // Initialize
    function init() {
        cards.forEach((card, index) => {
            // Set z-index dynamically based on position
            card.style.zIndex = index + 1;
            
            if (index === currentCardIndex) {
                card.classList.add('active');
            } else {
                card.classList.remove('active');
            }
        });
        
        const activeCard = cards[currentCardIndex];
        if (activeCard) {
            addCardListeners(activeCard);
            updateCounterDisplay();
        } else {
            console.warn('No active card found in init()');
        }
    }
    
    // Update the external counter display
    function updateCounterDisplay() {
        const counterDisplay = document.querySelector('.quick-check-counter-display');
        
        if (counterDisplay && currentCardIndex >= 0 && currentCardIndex < cards.length) {
            const activeCard = cards[currentCardIndex];
            const counterText = activeCard.getAttribute('data-counter');
            if (counterText) {
                counterDisplay.textContent = counterText;
            } else {
                console.warn('No data-counter attribute found on active card');
            }
        } else {
            console.warn('Counter display conditions not met:', {
                hasCounterDisplay: !!counterDisplay,
                currentCardIndex,
                cardsLength: cards.length
            });
        }
    }
    
    // Add event listeners to card
    function addCardListeners(card) {
        if (!card) return;
        // Touch events
        card.addEventListener('touchstart', handleStart, { passive: false });
        card.addEventListener('touchmove', handleMove, { passive: false });
        card.addEventListener('touchend', handleEnd, { passive: false });
        
        // Mouse events
        card.addEventListener('mousedown', handleStart);
    }
    
    // Remove event listeners from card
    function removeCardListeners(card) {
        if (!card) return;
        card.removeEventListener('touchstart', handleStart);
        card.removeEventListener('touchmove', handleMove);
        card.removeEventListener('touchend', handleEnd);
        card.removeEventListener('mousedown', handleStart);
    }
    
    // Handle start of drag/swipe
    function handleStart(e) {
        try {
            const card = e.currentTarget;
            if (!card || !card.classList.contains('active')) return;
            
            e.preventDefault();
            isDragging = true;
            card.classList.add('swiping');
            resetInactivityTimer();
            
            if (e.type === 'touchstart') {
                startX = e.touches[0].clientX;
                startY = e.touches[0].clientY;
            } else {
                startX = e.clientX;
                startY = e.clientY;
                // Add document-level listeners for mouse
                document.addEventListener('mousemove', handleMove);
                document.addEventListener('mouseup', handleEnd);
            }
            
            const rect = card.getBoundingClientRect();
            initialX = rect.left + rect.width / 2 - window.innerWidth / 2;
            initialY = rect.top;
        } catch (error) {
            isDragging = false;
        }
    }
    
    // Handle drag/swipe movement
    function handleMove(e) {
        if (!isDragging) return;
        
        try {
            e.preventDefault();
            
            const card = document.querySelector('.card.active');
            if (!card) return;
        
            if (e.type === 'touchmove') {
                currentX = e.touches[0].clientX;
                currentY = e.touches[0].clientY;
            } else {
                currentX = e.clientX;
                currentY = e.clientY;
            }
            
            const deltaX = currentX - startX;
            const deltaY = currentY - startY;
            
            card.style.transform = `translate(calc(-50% + ${deltaX}px), ${deltaY}px)`;
            card.style.opacity = 1 - Math.abs(deltaX) / 500;
            
            // Update swipe indicators (skip for welcome card)
            const isWelcomeCard = card.classList.contains('welcome-card');
            if (!isWelcomeCard) {
                const leftIndicator = card.querySelector('.swipe-indicator-left');
                const rightIndicator = card.querySelector('.swipe-indicator-right');
                
                if (deltaX < 0) {
                    // Swiping left - show red indicator
                    const opacity = Math.min(Math.abs(deltaX) / 150, 1);
                    leftIndicator.style.opacity = opacity;
                    rightIndicator.style.opacity = 0;
                } else if (deltaX > 0) {
                    // Swiping right - show green indicator
                    const opacity = Math.min(Math.abs(deltaX) / 150, 1);
                    rightIndicator.style.opacity = opacity;
                    leftIndicator.style.opacity = 0;
                } else {
                    leftIndicator.style.opacity = 0;
                    rightIndicator.style.opacity = 0;
                }
            }
        } catch (error) {
        }
    }
    
    // Handle end of drag/swipe
    function handleEnd(e) {
        if (!isDragging) return;
        
        try {
            isDragging = false;
            const card = document.querySelector('.card.active');
            if (!card) return;
            
            card.classList.remove('swiping');
            
            // Remove document-level mouse listeners
            document.removeEventListener('mousemove', handleMove);
            document.removeEventListener('mouseup', handleEnd);
            
            const deltaX = currentX - startX;
            const threshold = 100;
            
            if (Math.abs(deltaX) > threshold) {
                // Swipe detected
                const direction = deltaX > 0 ? 'right' : 'left';
                swipeCard(card, direction, Math.abs(deltaX));
            } else {
                // Return to original position
                card.style.transform = '';
                card.style.opacity = '';
                
                // Reset indicators
                const leftIndicator = card.querySelector('.swipe-indicator-left');
                const rightIndicator = card.querySelector('.swipe-indicator-right');
                if (leftIndicator) leftIndicator.style.opacity = 0;
                if (rightIndicator) rightIndicator.style.opacity = 0;
            }
        } catch (error) {
            isDragging = false;
        }
    }
    
    // Swipe card off screen
    function swipeCard(card, direction, velocity = 200) {
        const cardNumber = card.getAttribute('data-card');
        const filmId = card.getAttribute('data-film-id');
        const filmName = card.getAttribute('data-film-name') || '';
        const filmSlug = card.getAttribute('data-film-slug') || '';
        const filmYear = card.getAttribute('data-film-year') || '';
        const filmTitle = card.querySelector('h2') ? card.querySelector('h2').textContent : `Card ${cardNumber}`;
        const isWelcomeCard = card.classList.contains('welcome-card');
        
        // Don't add welcome card to undo stack or session
        if (!isWelcomeCard) {
            // If swiped right (watched), save to sessionStorage
            if (direction === 'right' && filmId) {
                const today = new Date().toISOString().split('T')[0];
                saveWatchedToSession({
                    'film-id': parseInt(filmId),
                    'film-name': filmName,
                    'film-year': parseInt(filmYear) || null,
                    'film-url': filmSlug,
                    'watched-date': today
                });
            }
            
            // If swiped right (watched), add .watched class to all matching film elements on the page
            if (direction === 'right' && filmId) {
                const matchingFilms = document.querySelectorAll(`li[data-film-id="${filmId}"]`);
                matchingFilms.forEach(filmEl => {
                    if (!filmEl.classList.contains('watched')) {
                        filmEl.classList.add('watched');
                    }
                });
            }
            
            // Store card info for undo
            undoStack.push({
                card: card,
                cardNumber: cardNumber,
                filmId: filmId,
                filmTitle: filmTitle,
                direction: direction,
                index: currentCardIndex
            });
            
            updateUndoButton();
            updateProgressRings();
        }
        
        removeCardListeners(card);
        card.classList.remove('active');
        card.classList.add(`swipe-${direction}`);
        
        // Calculate animation duration based on velocity
        const duration = Math.max(300, Math.min(600, 1000 / (velocity / 100)));
        card.style.animationDuration = `${duration}ms`;
        
        // Immediately activate next card
        currentCardIndex--;
        
        if (currentCardIndex >= 0) {
            const nextCard = cards[currentCardIndex];
            nextCard.classList.add('active');
            addCardListeners(nextCard);
            updateCounterDisplay();
        } else {
            // All cards swiped - output and clear session
            setTimeout(() => {
                outputAndClearSession();
            }, duration);
        }
        
        // Remove swiped card after animation completes
        setTimeout(() => {
            card.remove();
            // Update counter in case it was the welcome card
            if (currentCardIndex >= 0 && cards[currentCardIndex]) {
                updateCounterDisplay();
            }
        }, duration);
    }
    
    // Keyboard controls
    document.addEventListener('keydown', function(e) {
        
        if (currentCardIndex >= 0 && currentCardIndex < cards.length) {
            const activeCard = cards[currentCardIndex];
            
            if (e.key === 'ArrowLeft') {
                e.preventDefault();
                // Flash left indicator
                const leftIndicator = activeCard.querySelector('.swipe-indicator-left');
                if (leftIndicator) {
                    leftIndicator.style.opacity = 0.8;
                    setTimeout(() => {
                        leftIndicator.style.opacity = 0;
                    }, 200);
                }
                swipeCard(activeCard, 'left');
            } else if (e.key === 'ArrowRight') {
                e.preventDefault();
                // Flash right indicator
                const rightIndicator = activeCard.querySelector('.swipe-indicator-right');
                if (rightIndicator) {
                    rightIndicator.style.opacity = 0.8;
                    setTimeout(() => {
                        rightIndicator.style.opacity = 0;
                    }, 200);
                }
                swipeCard(activeCard, 'right');
            }
        }
    });
    
    // Undo functionality
    const undoButton = container.querySelector('.undo-button');
    
    function updateUndoButton() {
        if (undoButton) {
            if (undoStack.length > 0) {
                undoButton.style.opacity = '1';
            } else {
                undoButton.style.opacity = '0.2';
            }
        }
    }
    
    function undoLastSwipe() {
        if (undoStack.length === 0) return;
        
        const lastSwipe = undoStack.pop();
        
        // If it was marked as watched (swiped right), remove from sessionStorage and remove .watched class
        if (lastSwipe.direction === 'right' && lastSwipe.filmId) {
            // Remove from sessionStorage
            const data = getWatchedFromSession();
            data.watched = data.watched.filter(f => f['film-id'] !== parseInt(lastSwipe.filmId));
            sessionStorage.setItem(SESSION_KEY, JSON.stringify(data));
            
            // Remove .watched class from matching film elements
            const matchingFilms = document.querySelectorAll(`li[data-film-id="${lastSwipe.filmId}"]`);
            matchingFilms.forEach(filmEl => {
                const wasAlreadyWatched = filmEl.classList.contains('watched') && 
                    !data.watched.some(f => f['film-id'] === parseInt(lastSwipe.filmId));
                if (!wasAlreadyWatched) {
                    filmEl.classList.remove('watched');
                }
            });
        }
        
        resetInactivityTimer();
        
        // Recreate the card
        const card = lastSwipe.card.cloneNode(true);
        card.classList.remove('swipe-left', 'swipe-right');
        card.style.transform = '';
        card.style.opacity = '';
        card.style.animationDuration = '';
        
        // Reset indicators
        const leftIndicator = card.querySelector('.swipe-indicator-left');
        const rightIndicator = card.querySelector('.swipe-indicator-right');
        if (leftIndicator) leftIndicator.style.opacity = 0;
        if (rightIndicator) rightIndicator.style.opacity = 0;
        
        // Remove listeners from current active card
        if (currentCardIndex >= 0 && cards[currentCardIndex]) {
            removeCardListeners(cards[currentCardIndex]);
            cards[currentCardIndex].classList.remove('active');
        }
        
        // Re-add the card
        cardsWrapper.appendChild(card);
        
        // Update cards array
        currentCardIndex = lastSwipe.index;
        cards[currentCardIndex] = card;
        
        // Make it active
        card.classList.add('active');
        addCardListeners(card);
        
        updateUndoButton();
        updateProgressRings();
        updateCounterDisplay();
    }
    
    if (undoButton) {
        undoButton.addEventListener('click', undoLastSwipe);
        updateUndoButton();
    }
    
    // Reset button
    resetButton.addEventListener('click', function() {
        location.reload();
    });
    
    // Output session when cursor leaves browser window
    document.addEventListener('mouseleave', function(e) {
        if (e.clientY <= 0 || e.clientX <= 0 || 
            e.clientX >= window.innerWidth || e.clientY >= window.innerHeight) {
            outputAndClearSession();
        }
    });
});
