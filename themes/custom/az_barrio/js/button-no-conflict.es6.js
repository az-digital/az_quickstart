// Give $().bootstrapBtn the Bootstrap functionality.
// See https://digital.arizona.edu/arizona-bootstrap/docs/2.0/getting-started/javascript/#no-conflict
(($, Drupal) => {
  Drupal.behaviors.azBarrioButtonNoConflict = {
    attach: () => {
      const bootstrapButton = $.fn.button.noConflict();
      $.fn.bootstrapBtn = bootstrapButton;
    },
  };
})(jQuery, Drupal);
