
document.addEventListener('DOMContentLoaded', function() {

    const navbarCollapse = document.querySelector('.navbar-collapse');
    
    if (navbarCollapse) {

        const navLinks = document.querySelectorAll('.navbar-nav .nav-link, .mobile-menu-item');
        

        navLinks.forEach(link => {
            link.addEventListener('click', function(e) {

                if (window.innerWidth < 992) {

                    if (!this.hasAttribute('data-bs-toggle') || this.getAttribute('data-bs-toggle') !== 'dropdown') {

                        setTimeout(() => {
                            if (typeof bootstrap !== 'undefined' && bootstrap.Collapse) {
                                const bsCollapse = new bootstrap.Collapse(navbarCollapse, {
                                    toggle: false
                                });
                                bsCollapse.hide();
                            } else {
                                navbarCollapse.classList.remove('show');
                            }
                        }, 150); 
                    }
                }
            });
        });
        

        const mobileMenuItems = document.querySelectorAll('.mobile-menu-item');
        mobileMenuItems.forEach(item => {
            item.addEventListener('click', function() {
                if (window.innerWidth < 992) {
                    setTimeout(() => {
                        if (typeof bootstrap !== 'undefined' && bootstrap.Collapse) {
                            const bsCollapse = new bootstrap.Collapse(navbarCollapse, {
                                toggle: false
                            });
                            bsCollapse.hide();
                        } else {
                            navbarCollapse.classList.remove('show');
                        }
                    }, 100);
                }
            });
        });
    }
    

    const navbar = document.querySelector('.navbar');
    let lastScroll = 0;
    
    window.addEventListener('scroll', () => {
        const currentScroll = window.pageYOffset;
        
        if (currentScroll <= 0) {
            navbar.classList.remove('scroll-up');
            return;
        }
        
        if (currentScroll > lastScroll && !navbar.classList.contains('scroll-down')) {

            navbar.classList.remove('scroll-up');
            navbar.classList.add('scroll-down');
        } else if (currentScroll < lastScroll && navbar.classList.contains('scroll-down')) {

            navbar.classList.remove('scroll-down');
            navbar.classList.add('scroll-up');
        }
        lastScroll = currentScroll;
    });
});


document.addEventListener('click', function(event) {
    const navbar = document.querySelector('.navbar');
    const navbarToggler = document.querySelector('.navbar-toggler');
    const navbarCollapse = document.querySelector('.navbar-collapse');
    
    if (navbar && navbarCollapse && navbarToggler) {
        const isClickInside = navbar.contains(event.target);
        const isExpanded = navbarCollapse.classList.contains('show');
        
        if (!isClickInside && isExpanded) {
            const bsCollapse = new bootstrap.Collapse(navbarCollapse, {
                toggle: false
            });
            bsCollapse.hide();
        }
    }
});













