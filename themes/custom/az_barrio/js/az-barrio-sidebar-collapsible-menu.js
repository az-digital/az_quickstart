((Drupal, once) => {
  Drupal.behaviors.azBarrioSidebarCollapsibleMenu = {
    attach: (context) => {
      once('addParentStyling', '.az-sidebar-collapsible-menu').forEach(() => {
        const menuParent = document.querySelector(
          '.az-sidebar-collapsible-parent > a',
        );
        if (menuParent && menuParent.classList) {
          menuParent.classList.add('nav-link');
        }
      }, context);
    },
  };
})(Drupal, once);
