// Make Arizona Bootstrap NavbarHoverDropdown JS work with AJAX/BigPipe.
((Drupal, arizonaBootstrap) => {
  Drupal.behaviors.azNavbar = {
    attach: () => {
      arizonaBootstrap.enableAzNavbarHoverDropdowns();
    },
  };
})(Drupal, window.arizonaBootstrap);
