((Drupal, once) => {
  Drupal.behaviors.azBarrioSidebarCollapsibleMenu = {
    attach: (context) => {
      function addParentItemStyling() {
        once('addParentItemStyling', '#az-sidebar-collapsible').forEach(() => {
          const menuParent = document.querySelector(
            '.az-sidebar-collapsible-parent > a',
          );
          if (menuParent && menuParent.classList) {
            menuParent.classList.add('nav-link');
          }
        }, context);
      }

      document.addEventListener('DOMContentLoaded', addParentItemStyling);
    },
  };
})(Drupal, once);
