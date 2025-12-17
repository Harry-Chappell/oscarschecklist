document.addEventListener('DOMContentLoaded', function() {
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
    
    // Wait for watched classes to be applied before building cards
    function initializeWhenReady() {
        // Check if watched classes have been applied
        const hasWatchedClass = document.querySelector('li[data-film-id].watched');
        const hasFilmElements = document.querySelectorAll('li[data-film-id]').length > 0;
        
        if (hasFilmElements) {
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
            // Find all unique film elements, excluding watched ones
            const filmElements = document.querySelectorAll('li[data-film-id]');
            const uniqueFilms = new Map();
            
            filmElements.forEach(el => {
                const filmId = el.getAttribute('data-film-id');
                const classes = el.className || '';
                const hasWatched = classes.split(' ').includes('watched');
                
                // Check if the element has the 'watched' class
                if (filmId && !hasWatched && !uniqueFilms.has(filmId)) {
                    uniqueFilms.set(filmId, el);
                }
            });
            
            if (uniqueFilms.size > 0) {
                // Create cards from film data (reversed so first film is on top)
                const filmsArray = Array.from(uniqueFilms.entries()).reverse();
                filmsArray.forEach(([filmId, filmEl], index) => {
                    const card = createFilmCard(filmId, filmEl, index, filmsArray.length);
                    if (card) {
                        cardsWrapper.appendChild(card);
                    }
                });
            } else {
                // Fallback to default cards
                for (let i = 5; i >= 1; i--) {
                    const card = createFallbackCard(i, 5);
                    cardsWrapper.appendChild(card);
                }
            }
            
            cards = Array.from(cardsWrapper.querySelectorAll('.card'));
            currentCardIndex = cards.length - 1;
        } catch (error) {
            console.error('Error building cards:', error);
            // Fallback to default cards on error
            for (let i = 5; i >= 1; i--) {
                const card = createFallbackCard(i, 5);
                cardsWrapper.appendChild(card);
            }
            cards = Array.from(cardsWrapper.querySelectorAll('.card'));
            currentCardIndex = cards.length - 1;
        }
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
        
        results.push({ 
            card: cardNumber, 
            direction: direction,
            filmId: filmId,
            filmTitle: filmTitle
        });
        
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
                swipeCard(activeCard, 'left');
            } else if (e.key === 'ArrowRight') {
                e.preventDefault();
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
            
            item.innerHTML = `
                <span class="card-number">${displayText}</span>
                <span class="direction ${result.direction}">${result.direction.toUpperCase()}</span>
            `;
            resultsList.appendChild(item);
        });
        
        cardsWrapper.style.display = 'none';
        resultsDiv.classList.remove('hidden');
    }
    
    // Reset button
    resetButton.addEventListener('click', function() {
        location.reload();
    });
});
