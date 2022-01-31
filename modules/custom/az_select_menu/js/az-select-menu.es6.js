(($, Drupal, window, document, once) => {

  Drupal.azSelectMenu = Drupal.azSelectMenu || {};

  /**
   * Attaches behavior for select menu.
   */
  Drupal.behaviors.azSelectMenu = {
    attach: function (context, settings) {
      //  az_select_menu form id's are added in an array depending
      //  on the page you are on, and how many select menus are on the page.
      for ( let i = 0; i < settings.azSelectMenu.ids.length; i++ ) {
        const selectFormId =  settings.azSelectMenu.ids[i];
        const selectForm = document.querySelector(`#${selectFormId}`);
        // const [selectFormOnce] = once('az-select-menu', selectForm);
        once('azSelectMenu', selectForm, context).forEach(function (element) {
          $(element).popover();
          element.addEventListener('focus', (event) => {
            Drupal.azSelectMenu.handleEvents(event);
          }),
          element.addEventListener('change', (event) => {
            Drupal.azSelectMenu.handleEvents(event);
          }),
          element.addEventListener('mouseenter', (event) => {
            Drupal.azSelectMenu.handleEvents(event);
          }),
          button = element.querySelector('button');
          console.log(button);
          button.addEventListener('click', (event) => {
            Drupal.azSelectMenu.handleEvents(event);
          }),
          button.addEventListener('touchstart', (event) => {
            Drupal.azSelectMenu.handleEvents(event);
          }),
          button.addEventListener('mouseenter', (event) => {
            Drupal.azSelectMenu.handleEvents(event);
          }),
          button.addEventListener('mouseleave', (event) => {
            Drupal.azSelectMenu.handleEvents(event);
          }),
          button.addEventListener('focus', (event) => {
            Drupal.azSelectMenu.handleEvents(event);
          }),
          button.addEventListener('blur', (event) => {
            Drupal.azSelectMenu.handleEvents(event);
          }),
          document.addEventListener('touchstart', (event) => {
            Drupal.azSelectMenu.handleEvents(event);
          }),
          element.classList.add('processed');
        });

      };
    }
  };

  /**
   * Select menu event handler.
   *
   * Handles mouse and click events for the select menu
   * elements.
   */
  Drupal.azSelectMenu.handleEvents = function (event) {
    const $this = $(this);
    // Hide the popover when user touches any part of the screen, except the
    // select form button regardless of state.
    if (event.type === 'touchstart') {
      if ($this.hasClass('js_select_menu_button')) {
        event.stopPropagation();
      }
      else {
        $('.az-select-menu').popover('hide');
        return;
      }
    }

    const $selectForm = $this.closest('form');
    const selectForm = event.target.closest('form');
    const selectElement = selectForm.querySelector('select');
    const [optionsSelected] = selectElement.selectedOptions;
    const selectElementHref = optionsSelected.dataset.href;
    console.log(selectElementHref);
    const $selectElement = $selectForm.find('select');
    const $selectBtn = $selectForm.find('button');

    //  If a navigable link is selected in the dropdown.
    if (selectElementHref !== undefined) {
      $selectForm.popover('hide');
      $selectElement.attr('aria-invalid', 'false');
      $selectBtn.removeClass('disabled');
      $selectBtn.attr('aria-disabled', 'false');
      $selectBtn.removeAttr('disabled');
      console.log(event.type);
      switch (event.type) {
        case 'click':
          // If the link works, don't allow the button to focus.
          event.stopImmediatePropagation();
          console.log('going to ' + selectElementHref);
          window.location = selectElementHref;
          break;
      }
    }
    //  Don't follow link if using the nolink setting.
    else {
      $selectBtn.addClass('disabled');
      $selectBtn.attr('aria-disabled', 'true');
      $selectBtn.attr('disabled', true);
      $selectElement.attr('aria-invalid', 'true');
      switch (event.type) {
        case 'click':
          if ($this.hasClass('btn')) {
            $selectForm.popover('show');
            $selectElement.focus();
          }
          break;

        case 'focus':
        case 'mouseenter':
          if ($this.hasClass('btn')) {
            $selectForm.popover('show');
          }
          else {
            $selectForm.popover('hide');
          }
          break;

        case 'mouseleave':
          $selectForm.popover('hide');
          break;
      }
    }
  };
})(jQuery, Drupal, this, this.document, once);
