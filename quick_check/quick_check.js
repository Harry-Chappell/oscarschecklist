document.addEventListener('DOMContentLoaded', function() {
    const container = document.querySelector('.quick-check-container');
    if (!container) return;
    
    const cardsWrapper = container.querySelector('.cards-wrapper');
    const cards = Array.from(container.querySelectorAll('.card'));
    const resultsDiv = container.querySelector('.results');
    const resultsList = container.querySelector('.results-list');
    const resetButton = container.querySelector('.reset-button');
    
    let currentCardIndex = cards.length - 1;
    let results = [];
    let isDragging = false;
    let startX = 0;
    let startY = 0;
    let currentX = 0;
    let currentY = 0;
    let initialX = 0;
    let initialY = 0;
    
    // Initialize
    function init() {
        cards.forEach((card, index) => {
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
        // Touch events
        card.addEventListener('touchstart', handleStart, { passive: false });
        card.addEventListener('touchmove', handleMove, { passive: false });
        card.addEventListener('touchend', handleEnd, { passive: false });
        
        // Mouse events
        card.addEventListener('mousedown', handleStart);
    }
    
    // Remove event listeners from card
    function removeCardListeners(card) {
        card.removeEventListener('touchstart', handleStart);
        card.removeEventListener('touchmove', handleMove);
        card.removeEventListener('touchend', handleEnd);
        card.removeEventListener('mousedown', handleStart);
    }
    
    // Handle start of drag/swipe
    function handleStart(e) {
        const card = e.currentTarget;
        if (!card.classList.contains('active')) return;
        
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
    }
    
    // Handle drag/swipe movement
    function handleMove(e) {
        if (!isDragging) return;
        
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
    }
    
    // Handle end of drag/swipe
    function handleEnd(e) {
        if (!isDragging) return;
        
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
    }
    
    // Swipe card off screen
    function swipeCard(card, direction, velocity = 200) {
        const cardNumber = card.getAttribute('data-card');
        results.push({ card: cardNumber, direction: direction });
        
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
            item.innerHTML = `
                <span class="card-number">Card ${result.card}</span>
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
    
    // Initialize the feature
    init();
});
