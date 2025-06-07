 // Scroll Animations
  // Intersection Observer for Scroll Animations
  const animateElements = document.querySelectorAll('[data-animate]');

  const observer = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        const animation = entry.target.getAttribute('data-animate');
        if (entry.isIntersecting) {
          // Add animation class when element is in view
          entry.target.classList.add(animation);
        } else {
          // Remove animation class when element is out of view
          entry.target.classList.remove(animation);
        }
      });
    },
    {
      threshold: 0.1, // Trigger when 10% of the element is visible
    }
  );

  // Observe all elements with data-animate attribute
  animateElements.forEach((element) => {
    observer.observe(element);
  });



   //--------------------------------------------------------------------------------------------------------------
  //--------------------------------------------------------------------------------------------------------------
  //--------------------------------------------------------------------------------------------------------------
  //--------------------------------------------------------------------------------------------------------------
  //--------------------------------------------------------------------------------------------------------------

  //for header when it scrolls
  document.addEventListener('DOMContentLoaded', function() {
    const header = document.querySelector('header');
    const seatBookingSection = document.getElementById('book-seat');
    
    // Function to check scroll position
    function checkScroll() {
      const seatBookingPosition = seatBookingSection.getBoundingClientRect().top;
      const scrollPosition = window.scrollY;
      
      // When the seat-booking section reaches the top of the viewport
      if (scrollPosition > seatBookingPosition - 300) {
        header.classList.add('scrolled');
      } else {
        header.classList.remove('scrolled');
      }
    }
    
    // Run on initial load
    checkScroll();
    
    // Run on scroll
    window.addEventListener('scroll', checkScroll);
  });
