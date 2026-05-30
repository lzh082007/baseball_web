document.addEventListener('DOMContentLoaded', function() {
    const navToggle = document.getElementById('navToggle');
    const nav = document.querySelector('nav');
    const navLinks = document.querySelectorAll('.nav-links a, .header-nav-link, .btn-login, .btn-login-header, .btn-logout');

    if (navToggle && nav) {
        navToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            const isActive = nav.classList.toggle('nav-active');
            navToggle.setAttribute('aria-expanded', isActive);
            
            // Lock body scroll when mobile navigation menu is open
            if (isActive) {
                document.body.style.overflow = 'hidden';
            } else {
                document.body.style.overflow = '';
            }
        });

        // Close navigation menu when clicking links
        navLinks.forEach(link => {
            link.addEventListener('click', function() {
                nav.classList.remove('nav-active');
                navToggle.setAttribute('aria-expanded', 'false');
                document.body.style.overflow = '';
            });
        });

        // Close navigation menu when clicking outside
        document.addEventListener('click', function(e) {
            if (nav.classList.contains('nav-active') && !nav.contains(e.target)) {
                nav.classList.remove('nav-active');
                navToggle.setAttribute('aria-expanded', 'false');
                document.body.style.overflow = '';
            }
        });

        // Reset scroll lock when window is resized to desktop width
        window.addEventListener('resize', function() {
            if (window.innerWidth > 991 && nav.classList.contains('nav-active')) {
                nav.classList.remove('nav-active');
                navToggle.setAttribute('aria-expanded', 'false');
                document.body.style.overflow = '';
            }
        });
    }
});
