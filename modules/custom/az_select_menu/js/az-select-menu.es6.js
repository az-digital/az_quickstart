(($, Drupal, window, document, once) => {
  Drupal.azSelectMenu = Drupal.azSelectMenu || {};

  /**
   * Attaches behavior for select menu.
   */
  Drupal.behaviors.azSelectMenu = {
    attach(context, settings) {
      //  az_select_menu form id's are added in an array depending
      //  on the page you are on, and how many select menus are on the page.
      Object.keys(settings.azSelectMenu.ids).forEach((property) => {
        if (settings.azSelectMenu.ids.hasOwnProperty(property)) {
          const selectFormId = settings.azSelectMenu.ids[property];
          const selectForm = document.querySelector(`#${selectFormId}`);
          once('azSelectMenu', selectForm, context).forEach((element) => {
            // Bootstrap 5 popover initialization
            if (window.arizonaBootstrap?.Popover) {
              // eslint-disable-next-line no-new
              new window.arizonaBootstrap.Popover(element);
            }

            // Add event listeners using the handler function directly
            const { handleEvents } = Drupal.azSelectMenu;
            element.addEventListener('focus', handleEvents);
            element.addEventListener('change', handleEvents);
            element.addEventListener('mouseenter', handleEvents);

            const button = element.querySelector('button');
            button.addEventListener('click', handleEvents);
            button.addEventListener('touchstart', handleEvents);
            button.addEventListener('mouseenter', handleEvents);
            button.addEventListener('mouseleave', handleEvents);
            button.addEventListener('focus', handleEvents);
            button.addEventListener('blur', handleEvents);
            document.addEventListener('touchstart', handleEvents);
            element.classList.add('processed');
          });
        }
      });
    },
  };

  /**
   * Select menu event handler.
   *
   * Handles mouse and click events for the select menu
   * elements.
   * @param {object} event The javascript event object.
   */

  Drupal.azSelectMenu.handleEvents = (event) => {
    // Hide the popover when user touches any part of the screen, except the
    // select form button regardless of state.
    if (event.type === 'touchstart') {
      if (event.target.classList.contains('js_select_menu_button')) {
        // Don't stop propagation - let it fall through to main logic
        // This will handle disabled state and popover display for touch events
      } else {
        // Hide all popovers
        document.querySelectorAll('.az-select-menu').forEach((form) => {
          const popoverInstance =
            window.arizonaBootstrap?.Popover?.getInstance(form);
          if (popoverInstance) popoverInstance.hide();
        });
        return;
      }
    }

    const selectForm = event.target.closest('form');
    const selectElement = selectForm.querySelector('select');
    const [optionsSelected] = selectElement.selectedOptions;
    const selectElementHref = optionsSelected.dataset.href;
    const button = selectForm.querySelector('button');
    let popoverInstance =
      window.arizonaBootstrap?.Popover?.getInstance(selectForm);

    //  If a navigable link is selected in the dropdown.
    if (selectElementHref !== '') {
      // Destroy popover when button is enabled
      if (popoverInstance) {
        popoverInstance.dispose();
      }
      button.classList.remove('disabled');
      button.setAttribute('aria-disabled', 'false');
      switch (event.type) {
        case 'click':
          // If the link works, don't allow the button to focus.
          event.stopImmediatePropagation();
          window.location = selectElementHref;
          break;
        default:
          break;
      }
    }

    //  Don't follow link if using the nolink setting.
    else {
      button.classList.add('disabled');
      button.setAttribute('aria-disabled', 'true');
      selectElement.setAttribute('aria-disabled', 'true');

      // Recreate popover when button becomes disabled
      if (!popoverInstance && window.arizonaBootstrap?.Popover) {
        // eslint-disable-next-line no-new
        new window.arizonaBootstrap.Popover(selectForm);
        // Get the newly created instance
        popoverInstance =
          window.arizonaBootstrap.Popover.getInstance(selectForm);
      }

      switch (event.type) {
        case 'click':
        case 'touchstart':
          if (event.target.classList.contains('js_select_menu_button')) {
            if (popoverInstance) popoverInstance.show();
            selectElement.focus();
          }
          break;

        case 'focus':
        case 'mouseenter':
          if (event.target.classList.contains('js_select_menu_button')) {
            if (popoverInstance) popoverInstance.show();
          } else if (popoverInstance) {
            popoverInstance.hide();
          }
          break;

        case 'mouseleave':
          if (popoverInstance) popoverInstance.hide();
          break;
        default:
          break;
      }
    }
  };
})(jQuery, Drupal, this, this.document, once);
