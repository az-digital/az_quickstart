/**
 * @file
 * Provides the processing logic for details element.
 */

(($, once) => {
  Drupal.FieldGroup = Drupal.FieldGroup || {};
  Drupal.FieldGroup.Effects = Drupal.FieldGroup.Effects || {};

  /**
   * This script adds the required and error classes to the details wrapper.
   */
  Drupal.behaviors.fieldGroupDetails = {
    attach(context) {
      $(once('field-group-details', '.field-group-details', context)).each(
        (index, element) => {
          const $this = $(element);

          if (
            element.matches('.required-fields') &&
            ($this.find('[required]').length > 0 ||
              $this.find('.form-required').length > 0)
          ) {
            $('summary', $this).first().addClass('form-required');
          }
        },
      );
    },
  };
})(jQuery, once);
