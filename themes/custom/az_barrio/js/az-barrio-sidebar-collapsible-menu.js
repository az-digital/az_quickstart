((Drupal, once) => {
  Drupal.behaviors.azBarrioSidebarCollapsibleMenu = {
    attach: (context) => {
      function addParentItemStyling() {
        once('addParentItemStyling', '.az-sidebar-collapsible-menu').forEach(() => {
          const menuParent = document.querySelector(
            '.az-sidebar-collapsible-parent > a',
          );
          if (menuParent && menuParent.classList) {
            menuParent.classList.add('nav-link');
          }
        }, context);
      }

      once('addMenuParentStyling', '#az-sidebar-collapsible').forEach(
        addParentItemStyling,
        context,
      );
    },
  };
})(Drupal, once);
