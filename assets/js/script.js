document.addEventListener('DOMContentLoaded', () => {
    const burgerMenu = document.querySelector('.burger-menu');
    const navLinks = document.querySelector('.nav-links');

    // Toggle menu on burger click
    burgerMenu.addEventListener('click', () => {
        navLinks.classList.toggle('active');
    });

    // Reset menu state on window resize
    window.addEventListener('resize', () => {
        if (window.innerWidth > 768) {
            navLinks.classList.remove('active'); // Hide menu on larger screens
        }
    });
});
