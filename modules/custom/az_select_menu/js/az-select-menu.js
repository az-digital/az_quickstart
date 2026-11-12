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
            $(element).popover();
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
        event.stopPropagation();
      } else {
        $('.az-select-menu').popover('hide');
        return;
      }
    }

    const selectForm = event.target.closest('form');
    const $selectForm = $(selectForm);
    const selectElement = selectForm.querySelector('select');
    const [optionsSelected] = selectElement.selectedOptions;
    const selectElementHref = optionsSelected.dataset.href;
    const button = selectForm.querySelector('button');

    //  If a navigable link is selected in the dropdown.
    if (selectElementHref !== '') {
      $selectForm.popover('hide');
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
      switch (event.type) {
        case 'click':
          if (event.target.classList.contains('js_select_menu_button')) {
            $selectForm.popover('show');
            selectElement.focus();
          }
          break;

        case 'focus':
        case 'mouseenter':
          if (event.target.classList.contains('js_select_menu_button')) {
            $selectForm.popover('show');
          } else {
            $selectForm.popover('hide');
          }
          break;

        case 'mouseleave':
          $selectForm.popover('hide');
          break;
        default:
          break;
      }
    }
  };
})(jQuery, Drupal, this, this.document, once);
