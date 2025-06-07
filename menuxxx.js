
  // Toggle Mobile Menu
  const mobileMenu = document.getElementById('mobile-menu');
  const navMenu = document.getElementById('nav-menu');

  mobileMenu.addEventListener('click', () => {
    navMenu.classList.toggle('active');
  });

  // Close Menu When a Link is Clicked
  const navLinks = document.querySelectorAll('#nav-menu ul li a');
  navLinks.forEach(link => {
    link.addEventListener('click', () => {
      navMenu.classList.remove('active');
    });
  });  