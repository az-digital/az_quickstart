(($, Drupal, drupalSettings, window, document, undefined) => {

  Drupal.azSelectMenu = Drupal.azSelectMenu || {};

  /**
   * Attaches behavior for select menu.
   */
  Drupal.behaviors.azSelectMenu = {
    attach() {
      //  az_select_menu form id's are added in an array depending
      //  on the page you are on, and how many select menus are on the page.

      for ( let i = 0; i < drupalSettings.azSelectMenu.ids.length; i++ ) {
        const selectFormId =  drupalSettings.azSelectMenu.ids[i];
        $(`#${selectFormId}`).once('az-select-menu', () => {
console.log(drupalSettings.azSelectMenu.ids[i]);
          $(`#${selectFormId} .js_select_menu_button`).on('touchstart click mouseenter mouseleave focus blur', Drupal.azSelectMenu.handleEvents);

          $(`#${selectFormId}-menu`).on('focus mouseenter', Drupal.azSelectMenu.handleEvents);
          // In MS Edge, onchange events can't be passed through the .on()
          // function for some reason.
          $(`#${selectFormId}-menu`).change(Drupal.azSelectMenu.handleEvents);
          // Document event handlers for events not part of the select menu.
          $(document).on('touchstart', Drupal.azSelectMenu.handleEvents);
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
  Drupal.azSelectMenu.handleEvents = function (e) {
    console.log(e);
    const $this = $(this);
    // Hide the popover when user touches any part of the screen, except the
    // select form button regardless of state.
    if (e.type === 'touchstart') {
      if ($this.hasClass('btn')) {
        e.stopPropagation();
      }
      else {
        console.log($this);
        $('.az-select-menu').popover('hide');
        return;
      }
    }
    const $selectForm = $this.closest('form');
    const $selectElement = $selectForm.find('select');
    const $selectBtn = $selectForm.find('.btn');
    const selectElementHref = $selectElement.find('option:selected').data('href');
    //  If a navigable link is selected in the dropdown.
    if (selectElementHref.indexOf('%3Cnolink%3E') <= 0) {
      $selectForm.popover('hide');
      $selectElement.attr('aria-invalid', 'false');
      $selectBtn.removeClass('disabled');
      $selectBtn.attr('aria-disabled', 'false');
      $selectBtn.removeAttr('disabled');
      switch (e.type) {
        case 'click':
          // If the link works, don't allow the button to focus.
          e.stopImmediatePropagation();
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
      switch (e.type) {
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
})(jQuery, Drupal, drupalSettings, this, this.document);
