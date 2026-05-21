((Drupal, once) => {
  Drupal.behaviors.azMobileNav = {
    attach: (context) => {
      function addCurrentPageClass() {
        const currentPagePath = window.location.pathname;
        const mobileNavMenuLinks = document.querySelectorAll(
          '#az_mobile_nav_menu a',
        );
        Array.from(mobileNavMenuLinks).some((link) => {
          if (link.getAttribute('href') === currentPagePath) {
            link.parentNode.classList.add(
              'text-bg-gray-200',
              'az-mobile-nav-current',
            );
            return true;
          }
          return false;
        });
      }
      once('azMobileNavCurrentPage', '#az_mobile_nav_menu').forEach(
        addCurrentPageClass,
        context,
      );
    },
  };
})(Drupal, once);
