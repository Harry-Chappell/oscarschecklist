document.addEventListener('DOMContentLoaded', function() {
    const modal = document.querySelector('.quick-check-modal');
    const triggerButton = document.querySelector('.quick-check-trigger');
    const closeButton = document.querySelector('.quick-check-close');
    const container = document.querySelector('.quick-check-container');
    if (!container) return;
    
    const cardsWrapper = container.querySelector('.cards-wrapper');
    const resultsDiv = container.querySelector('.results');
    const resultsList = container.querySelector('.results-list');
    const resetButton = container.querySelector('.reset-button');
    
    let cards = [];
    let currentCardIndex = 0;
    let results = [];
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
    }
    
    // Progress ring update
    function updateProgressRings() {
        // Calculate watched films progress (including already watched + newly swiped right)
        const newlyWatched = results.filter(r => r.direction === 'right').length;
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
            isInitialized = true;
            init();
        }
    }
    
    // Listen for the custom event dispatched when watched classes are applied
    document.addEventListener('watchedClassesApplied', initializeWhenReady);
    
    // Fallback: also try after a short delay if event doesn't fire
    setTimeout(initializeWhenReady, 1000);
    
    // Build cards from film data
    function buildCards() {
        try {
            // Categories are the UL elements with class nominations-list
            const nominationLists = document.querySelectorAll('ul.nominations-list');
            
            // Filter to only include lists that have film items
            categoryElements = Array.from(nominationLists).filter(ul => 
                ul.querySelectorAll('li[data-film-id]').length > 0
            );
            
            totalCategories = categoryElements.length;
            console.log(`Quick Check: Found ${totalCategories} categories (nominations-list elements)`);
            
            // Find all unique film elements, excluding watched ones
            const filmElements = document.querySelectorAll('li[data-film-id]');
            const uniqueFilms = new Map();
            const alreadyWatched = new Set();
            
            filmElements.forEach(el => {
                const filmId = el.getAttribute('data-film-id');
                const classes = el.className || '';
                const hasWatched = classes.split(' ').includes('watched');
                
                if (filmId) {
                    if (hasWatched) {
                        alreadyWatched.add(filmId);
                    } else if (!uniqueFilms.has(filmId)) {
                        uniqueFilms.set(filmId, el);
                    }
                }
            });
            
            // Set watched count to include already watched films
            watchedFilms = alreadyWatched.size;
            
            if (uniqueFilms.size > 0) {
                // Create cards from film data (reversed so first film is on top)
                const filmsArray = Array.from(uniqueFilms.entries()).reverse();
                totalFilms = filmsArray.length + alreadyWatched.size;
                filmsArray.forEach(([filmId, filmEl], index) => {
                    const card = createFilmCard(filmId, filmEl, index, filmsArray.length);
                    if (card) {
                        cardsWrapper.appendChild(card);
                    }
                });
                
                // Add welcome card on top
                const welcomeCard = createWelcomeCard();
                cardsWrapper.appendChild(welcomeCard);
            } else {
                // Fallback to default cards
                totalFilms = 5;
                for (let i = 5; i >= 1; i--) {
                    const card = createFallbackCard(i, 5);
                    cardsWrapper.appendChild(card);
                }
                
                // Add welcome card on top
                const welcomeCard = createWelcomeCard();
                cardsWrapper.appendChild(welcomeCard);
            }
            
            cards = Array.from(cardsWrapper.querySelectorAll('.card'));
            currentCardIndex = cards.length - 1;
            
            // Initialize progress rings
            updateProgressRings();
        } catch (error) {
            console.error('Error building cards:', error);
            // Fallback to default cards on error
            totalFilms = 5;
            for (let i = 5; i >= 1; i--) {
                const card = createFallbackCard(i, 5);
                cardsWrapper.appendChild(card);
            }
            
            // Add welcome card on top
            const welcomeCard = createWelcomeCard();
            cardsWrapper.appendChild(welcomeCard);
            
            cards = Array.from(cardsWrapper.querySelectorAll('.card'));
            currentCardIndex = cards.length - 1;
            
            // Initialize progress rings
            updateProgressRings();
        }
    }
    
    // Create welcome card
    function createWelcomeCard() {
        const card = document.createElement('div');
        card.className = 'card welcome-card';
        card.setAttribute('data-card', 'welcome');
        
        const cardContent = document.createElement('div');
        cardContent.className = 'card-content';
        cardContent.style.padding = '20px';
        cardContent.style.textAlign = 'center';
        
        const titleEl = document.createElement('h2');
        titleEl.textContent = 'Welcome to Quick Check!';
        titleEl.style.fontSize = '1.8em';
        titleEl.style.marginBottom = '20px';
        cardContent.appendChild(titleEl);
        
        const instructionsEl = document.createElement('div');
        instructionsEl.innerHTML = `
            <p style="margin: 15px 0; font-size: 1.1em;">Swipe or use arrow keys to sort films:</p>
            <p style="margin: 15px 0; font-size: 1.1em;">⬅️ <strong>Left/Swipe Left</strong> = Not Watched Yet</p>
            <p style="margin: 15px 0; font-size: 1.1em;">➡️ <strong>Right/Swipe Right</strong> = Watched</p>
            <p style="margin: 20px 0 10px 0; font-size: 0.95em; opacity: 0.9;">Swipe this card to begin!</p>
        `;
        cardContent.appendChild(instructionsEl);
        
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
    
    // Create a card from film data
    function createFilmCard(filmId, filmEl, index, total) {
        const card = document.createElement('div');
        card.className = 'card';
        card.setAttribute('data-card', index + 1);
        card.setAttribute('data-film-id', filmId);
        
        // Extract film data - get text from element inside .film-name
        const filmNameEl = filmEl.querySelector('.film-name');
        let filmTitle = 'Unknown Film';
        if (filmNameEl) {
            // Get the text content of the child element (h3, h4, or p)
            const childEl = filmNameEl.querySelector('h3, h4, p');
            filmTitle = childEl ? childEl.textContent.trim() : filmNameEl.textContent.trim();
        }
        
        const posterImg = filmEl.querySelector('.film-poster img');
        const posterSrc = posterImg ? posterImg.getAttribute('src') : '';
        const posterAlt = posterImg ? posterImg.getAttribute('alt') : filmTitle;
        
        // Clone buttons (excluding watched button)
        const buttonsContainer = filmEl.querySelector('.buttons-cntr');
        let buttonsHTML = '';
        if (buttonsContainer) {
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = buttonsContainer.innerHTML;
            // Remove watched button
            const watchedBtn = tempDiv.querySelector('.mark-as-unwatched-button, .mark-as-watched-button');
            if (watchedBtn) {
                watchedBtn.remove();
            }
            buttonsHTML = tempDiv.innerHTML;
        }
        
        // Build card content
        const cardContent = document.createElement('div');
        cardContent.className = 'card-content';
        
        if (posterSrc) {
            const posterEl = document.createElement('img');
            posterEl.src = posterSrc;
            posterEl.alt = posterAlt;
            posterEl.className = 'card-poster';
            cardContent.appendChild(posterEl);
        }
        
        const titleEl = document.createElement('h2');
        titleEl.textContent = filmTitle;
        cardContent.appendChild(titleEl);
        
        const counterEl = document.createElement('p');
        counterEl.className = 'card-counter';
        counterEl.textContent = `${index + 1} / ${total}`;
        cardContent.appendChild(counterEl);
        
        if (buttonsHTML.trim()) {
            const buttonsDiv = document.createElement('div');
            buttonsDiv.className = 'card-buttons';
            buttonsDiv.innerHTML = buttonsHTML;
            cardContent.appendChild(buttonsDiv);
        }
        
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
        buildCards();
        
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
            console.error('Error in handleStart:', error);
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
            const rotation = deltaX * 0.1;
            
            card.style.transform = `translate(calc(-50% + ${deltaX}px), ${deltaY}px) rotate(${rotation}deg)`;
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
            console.error('Error in handleMove:', error);
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
            console.error('Error in handleEnd:', error);
            isDragging = false;
        }
    }
    
    // Swipe card off screen
    function swipeCard(card, direction, velocity = 200) {
        const cardNumber = card.getAttribute('data-card');
        const filmId = card.getAttribute('data-film-id');
        const filmTitle = card.querySelector('h2') ? card.querySelector('h2').textContent : `Card ${cardNumber}`;
        const isWelcomeCard = card.classList.contains('welcome-card');
        
        // Don't add welcome card to results or undo stack
        if (!isWelcomeCard) {
            results.push({ 
                card: cardNumber, 
                direction: direction,
                filmId: filmId,
                filmTitle: filmTitle
            });
            
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
        } else {
            // Wait for animation to finish before showing results
            setTimeout(() => {
                showResults();
            }, duration);
        }
        
        // Remove swiped card after animation completes
        setTimeout(() => {
            card.remove();
        }, duration);
    }
    
    // Keyboard controls
    document.addEventListener('keydown', function(e) {
        if (resultsDiv && !resultsDiv.classList.contains('hidden')) return;
        
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
    
    // Show results
    function showResults() {
        resultsList.innerHTML = '';
        
        results.forEach(result => {
            const item = document.createElement('div');
            item.className = 'result-item';
            
            let displayText = result.filmTitle;
            if (result.filmId) {
                item.setAttribute('data-film-id', result.filmId);
            }
            
            const statusText = result.direction === 'right' ? 'Watched' : 'Not Watched Yet';
            
            item.innerHTML = `
                <span class="card-number">${displayText}</span>
                <span class="direction ${result.direction}">${statusText}</span>
            `;
            resultsList.appendChild(item);
        });
        
        cardsWrapper.style.display = 'none';
        resultsDiv.classList.remove('hidden');
    }
    
    // Undo functionality
    const undoButton = container.querySelector('.undo-button');
    
    function updateUndoButton() {
        if (undoButton) {
            if (undoStack.length > 0) {
                undoButton.style.display = 'block';
            } else {
                undoButton.style.display = 'none';
            }
        }
    }
    
    function undoLastSwipe() {
        if (undoStack.length === 0) return;
        
        const lastSwipe = undoStack.pop();
        
        // Remove last result
        results.pop();
        
        // If it was marked as watched (swiped right), remove .watched class from matching film elements
        if (lastSwipe.direction === 'right' && lastSwipe.filmId) {
            const matchingFilms = document.querySelectorAll(`li[data-film-id="${lastSwipe.filmId}"]`);
            matchingFilms.forEach(filmEl => {
                // Only remove if it wasn't already watched before this session
                // Check if this film was in the alreadyWatched set
                const wasAlreadyWatched = !results.some(r => r.filmId === lastSwipe.filmId);
                if (!wasAlreadyWatched) {
                    filmEl.classList.remove('watched');
                }
            });
        }
        
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
    }
    
    if (undoButton) {
        undoButton.addEventListener('click', undoLastSwipe);
        updateUndoButton();
    }
    
    // Reset button
    resetButton.addEventListener('click', function() {
        location.reload();
    });
});
