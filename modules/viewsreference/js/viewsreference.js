/**
 * @file
 */

(($, Drupal) => {
  /**
   * Handles an autocomplete select event.
   *
   * Override the autocomplete method to add a custom event.
   *
   * @param {jQuery.Event} event
   *   The event triggered.
   * @param {object} ui
   *   The jQuery UI settings object.
   *
   * @return {bool}
   *   Returns false to indicate the event status.
   */
  Drupal.autocomplete.options.select = function selectHandler(event, ui) {
    const terms = Drupal.autocomplete.splitValues(event.target.value);
    // Remove the current input.
    terms.pop();
    // Add the selected item.
    if (ui.item.value.search(',') > 0) {
      terms.push(`"${ui.item.value}"`);
    } else {
      terms.push(ui.item.value);
    }
    event.target.value = terms.join(', ');
    // Fire custom event that other controllers can listen to.
    jQuery(event.target).trigger('viewsreference-select');
    // Return false to tell jQuery UI that we've filled in the value already.
    return false;
  };

  Drupal.behaviors.displayMessage = {
    attach(context) {
      $(document).ajaxComplete(() => {
        $(
          '.field--type-viewsreference select.viewsreference-display-id',
          context,
        ).each(() => {
          $('.viewsreference-display-error', context).remove();
          const $parent = $(this).parent().hide();
          if ($(this).find('option').length <= 1) {
            const $error = $(
              '<p class="viewsreference-display-error form-notice color-warning">There is no display available. Please select another view or change the field settings.</p>',
            );
            $parent.after($error);
          } else {
            $parent.show();
          }
        });
      });
    },
  };
})(jQuery, Drupal);
