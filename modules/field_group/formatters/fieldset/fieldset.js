/**
 * @file
 * Provides the processing logic for fieldsets.
 */

(($) => {
  Drupal.FieldGroup = Drupal.FieldGroup || {};
  Drupal.FieldGroup.Effects = Drupal.FieldGroup.Effects || {};

  /**
   * This script adds the required and error classes to the fieldset wrapper.
   */
  Drupal.behaviors.fieldGroupFieldset = {
    attach(context) {
      $(once('field-group-fieldset', '.field-group-fieldset', context)).each(
        (index, element) => {
          const $this = $(element);

          if (
            element.matches('.required-fields') &&
            ($this.find('[required]').length > 0 ||
              $this.find('.form-required').length > 0)
          ) {
            $('legend', $this).first().addClass('form-required');
          }
        },
      );
    },
  };
})(jQuery);
