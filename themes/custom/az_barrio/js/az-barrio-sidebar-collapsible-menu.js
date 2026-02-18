((Drupal, once) => {
  Drupal.behaviors.azBarrioSidebarCollapsibleMenu = {
    attach: (context) => {
      const azSidebarCollapsibleMenu = document.querySelector(
        '.az-sidebar-collapsible-menu',
      );
      function closeOtherMenuItems(shownMenuItemContent) {
        const collapseElementsNotInActiveTrail =
          azSidebarCollapsibleMenu.querySelectorAll(
            `.collapse:not(:has([id='${shownMenuItemContent.id}']))`,
          );
        const collapseElementsToClose = [];
        [...collapseElementsNotInActiveTrail].forEach((el) => {
          if (el !== shownMenuItemContent && el.classList.contains('show')) {
            collapseElementsToClose.push(el);
          }
        });
        if (collapseElementsToClose.length !== 0) {
          [...collapseElementsToClose].forEach((el) => {
            azSidebarCollapsibleMenu
              .querySelector(`[href='#${el.id}']`)
              .click();
          });
        }
      }
      function handleCollapseEvent(event) {
        if (event.target.classList.contains('az-sidebar-collapse')) {
          closeOtherMenuItems(event.target);
        }
      }

      once('addParentStyling', '.az-sidebar-collapsible-menu').forEach(() => {
        const menuParent = azSidebarCollapsibleMenu.querySelector(
          '.az-sidebar-collapsible-parent > a',
        );
        if (menuParent && menuParent.classList) {
          menuParent.classList.add('nav-link');
        }
      }, context);

      once(
        'addMenuItemCollapseHandling',
        '.az-sidebar-collapsible-menu',
      ).forEach(() => {
        azSidebarCollapsibleMenu.addEventListener(
          'show.bs.collapse',
          handleCollapseEvent,
        );
      }, context);
    },
  };
})(Drupal, once);
