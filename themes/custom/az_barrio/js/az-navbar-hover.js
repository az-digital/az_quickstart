((Drupal, once) => {
  Drupal.behaviors.azNavbarHoverDropdowns = {
    attach: (context) => {
      once('azNavbarHoverDropdowns', '.navbar-nav', context).forEach(
        (navList) => {
          if (!navList.closest('.navbar-az')) {
            return;
          }
          const azBootstrap = window.arizonaBootstrap;
          if (
            azBootstrap &&
            typeof azBootstrap.enableAzNavbarHoverDropdowns === 'function'
          ) {
            azBootstrap.enableAzNavbarHoverDropdowns();
          }
        },
      );
    },
  };
})(Drupal, once);
