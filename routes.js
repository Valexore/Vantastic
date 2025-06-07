document.addEventListener('DOMContentLoaded', function() {
  // Carousel functionality
  const routesCarousel = document.querySelector('.routes-carousel-js');
  const routeCards = Array.from(document.querySelectorAll('.route-card-js'));
  const prevCarouselBtn = document.querySelector('.carousel-nav-js.prev-js');
  const nextCarouselBtn = document.querySelector('.carousel-nav-js.next-js');
  const dotsContainer = document.querySelector('.carousel-dots-js');
  const destinationInfoModal = document.getElementById('destinationInfoModal');
  const closeModalBtn = document.querySelector('.destination-modal-js .close-modal-js');
  
  // Create dots
  routeCards.forEach((_, index) => {
    const dot = document.createElement('button');
    dot.classList.add('carousel-dot-js');
    dot.setAttribute('aria-label', `Go to slide ${index + 1}`);
    if (index === 0) dot.classList.add('active-js');
    dot.addEventListener('click', () => {
      scrollToRouteCard(index);
    });
    dotsContainer.appendChild(dot);
  });
  
  const dots = Array.from(document.querySelectorAll('.carousel-dot-js'));
  
  // Scroll to specific card with smooth behavior
  function scrollToRouteCard(index) {
    const card = routeCards[index];
    const carouselRect = routesCarousel.getBoundingClientRect();
    const cardRect = card.getBoundingClientRect();
    
    routesCarousel.scrollTo({
      left: card.offsetLeft - routesCarousel.offsetLeft - (carouselRect.width - cardRect.width) / 2,
      behavior: 'smooth'
    });
  }
  
  // Update controls based on scroll position
  function updateCarouselControls() {
    const scrollPosition = routesCarousel.scrollLeft;
    const cardWidth = routeCards[0].offsetWidth + 30; // including gap
    const currentIndex = Math.min(
      Math.round(scrollPosition / cardWidth),
      routeCards.length - 1
    );
    
    // Update dots
    dots.forEach((dot, index) => {
      dot.classList.toggle('active-js', index === currentIndex);
      dot.setAttribute('aria-current', index === currentIndex ? 'true' : 'false');
    });
    
    // Update buttons
    prevCarouselBtn.disabled = scrollPosition <= 10;
    nextCarouselBtn.disabled = scrollPosition >= routesCarousel.scrollWidth - routesCarousel.clientWidth - 10;
  }
  
  // Button click handlers with smooth scroll
  prevCarouselBtn.addEventListener('click', () => {
    const currentScroll = routesCarousel.scrollLeft;
    const cardWidth = routeCards[0].offsetWidth + 30;
    const targetScroll = Math.max(0, currentScroll - cardWidth);
    
    routesCarousel.scrollTo({
      left: targetScroll,
      behavior: 'smooth'
    });
  });
  
  nextCarouselBtn.addEventListener('click', () => {
    const currentScroll = routesCarousel.scrollLeft;
    const cardWidth = routeCards[0].offsetWidth + 30;
    const targetScroll = Math.min(
      routesCarousel.scrollWidth - routesCarousel.clientWidth,
      currentScroll + cardWidth
    );
    
    routesCarousel.scrollTo({
      left: targetScroll,
      behavior: 'smooth'
    });
  });
  
  // Touch/swipe support with momentum
  let touchStartX = 0;
  let touchStartTime = 0;
  let isDragging = false;
  
  routesCarousel.addEventListener('touchstart', (e) => {
    touchStartX = e.changedTouches[0].screenX;
    touchStartTime = Date.now();
    isDragging = true;
  }, {passive: true});
  
  routesCarousel.addEventListener('touchmove', (e) => {
    if (!isDragging) return;
    e.preventDefault(); // Prevent page scroll during horizontal drag
  }, {passive: false});
  
  routesCarousel.addEventListener('touchend', (e) => {
    if (!isDragging) return;
    const touchEndX = e.changedTouches[0].screenX;
    const touchEndTime = Date.now();
    const distance = touchEndX - touchStartX;
    const duration = touchEndTime - touchStartTime;
    const velocity = distance / duration;
    
    // Only trigger swipe if movement was fast/long enough
    if (Math.abs(velocity) > 0.3 || Math.abs(distance) > 50) {
      if (distance < 0) nextCarouselBtn.click();
      else prevCarouselBtn.click();
    }
    
    isDragging = false;
  }, {passive: true});
  
  // Card click handler to open modal with animation
  routeCards.forEach(card => {
    card.addEventListener('click', function(e) {
      if (e.target.closest('.book-route-btn-js')) return;
      
      const destination = this.dataset.destination;
      const travelTime = this.dataset.time;
      const fare = this.dataset.fare;
      const popularStop = this.dataset.stop;
      
      openDestinationInfoModal(destination, travelTime, fare, popularStop, this);
    });
  });
  
  // Modal functions with animations
  function openDestinationInfoModal(destination, travelTime, fare, popularStop, cardElement) {
    const [name, province] = destination.split(', ');
    
    // Update basic info
    document.getElementById('modalRouteDestinationName').textContent = name;
    document.getElementById('modalRouteProvince').textContent = province;
    document.getElementById('modalRouteTravelTime').textContent = travelTime;
    document.getElementById('modalRouteFare').textContent = fare;
    document.getElementById('modalRoutePopularStop').textContent = popularStop;
    
    // Update highlights from data attributes
    const highlightsContainer = document.querySelector('.route-highlights-js ul');
    highlightsContainer.innerHTML = '';
    
    try {
      const highlights = JSON.parse(cardElement.dataset.highlights || '[]');
      const icons = JSON.parse(cardElement.dataset.icons || '[]');
      
      highlights.forEach((highlight, index) => {
        const icon = icons[index] || 'circle';
        const li = document.createElement('li');
        li.innerHTML = `<i class="fas fa-${icon}"></i> ${highlight}`;
        highlightsContainer.appendChild(li);
      });
    } catch (e) {
      console.error('Error parsing highlights:', e);
      // Fallback highlights
      const fallbackItems = [
        { icon: 'mountain', text: 'Scenic views' },
        { icon: 'utensils', text: 'Local cuisine' },
        { icon: 'wifi', text: 'Free WiFi' }
      ];
      
      fallbackItems.forEach(item => {
        const li = document.createElement('li');
        li.innerHTML = `<i class="fas fa-${item.icon}"></i> ${item.text}`;
        highlightsContainer.appendChild(li);
      });
    }
    
    // Add animation class
    destinationInfoModal.classList.add('active-js');
    document.body.style.overflow = 'hidden';
    
    // Add keyboard focus trap
    trapModalFocus(destinationInfoModal);
  }
  
  function closeDestinationInfoModal() {
    destinationInfoModal.classList.remove('active-js');
    document.body.style.overflow = '';
    
    // Return focus to the card that opened the modal
    const activeElement = document.activeElement;
    if (activeElement && activeElement.classList.contains('route-card-js')) {
      activeElement.focus();
    }
  }
  
  // Focus trap for accessibility
  function trapModalFocus(modal) {
    const focusableElements = modal.querySelectorAll(
      'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
    );
    const firstElement = focusableElements[0];
    const lastElement = focusableElements[focusableElements.length - 1];
    
    firstElement.focus();
    
    modal.addEventListener('keydown', function(e) {
      if (e.key !== 'Tab') return;
      
      if (e.shiftKey) {
        if (document.activeElement === firstElement) {
          lastElement.focus();
          e.preventDefault();
        }
      } else {
        if (document.activeElement === lastElement) {
          firstElement.focus();
          e.preventDefault();
        }
      }
    });
  }
  
  closeModalBtn.addEventListener('click', closeDestinationInfoModal);
  
  destinationInfoModal.addEventListener('click', function(e) {
    if (e.target === this || e.target.classList.contains('modal-glass-backdrop-js')) {
      closeDestinationInfoModal();
    }
  });
  
  // Close modal with ESC key
  document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && destinationInfoModal.classList.contains('active-js')) {
      closeDestinationInfoModal();
    }
  });
  
  // Update controls on scroll with debounce
  let isScrolling;
  routesCarousel.addEventListener('scroll', () => {
    window.clearTimeout(isScrolling);
    isScrolling = setTimeout(() => {
      updateCarouselControls();
    }, 100);
  }, {passive: true});
  
  // Initial update
  updateCarouselControls();
  
  // Auto-center first card on load (for mobile) with slight delay
  setTimeout(() => {
    if (window.innerWidth < 768 && routeCards.length > 0) {
      scrollToRouteCard(0);
    }
  }, 300);
  
  // Handle window resize
  window.addEventListener('resize', () => {
    updateCarouselControls();
  });
});