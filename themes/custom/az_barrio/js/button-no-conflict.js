// Give $().bootstrapBtn the Bootstrap functionality.
// See https://digital.arizona.edu/arizona-bootstrap/v5/docs/5.2/getting-started/javascript/#no-conflict
(($, Drupal) => {
  Drupal.behaviors.azBarrioButtonNoConflict = {
    attach: () => {
      if ($.fn.button && $.fn.button.noConflict !== undefined) {
        const bootstrapButton = $.fn.button.noConflict();
        $.fn.bootstrapBtn = bootstrapButton;
      }
    },
  };
})(jQuery, Drupal);
