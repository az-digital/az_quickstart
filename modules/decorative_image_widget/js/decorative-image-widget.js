/**
 * @file
 * JS behaviors for the decorative image widget.
 */
(($, Drupal, once) => {
  function enableOrDisableAltTextField($altTextField, enable) {
    if (!enable) {
      // Edit input field attributes.
      $altTextField
        .prop('disabled', true)
        .prop('required', false)
        .removeClass('required');

      // Edit parent element classes.
      $altTextField.parent().addClass('form-disabled');

      // Edit label element.
      $altTextField.parent().find('label').removeClass('form-required');
    } else {
      // Edit input field attributes.
      $altTextField
        .prop('disabled', false)
        .prop('required', true)
        .addClass('required');

      // Edit parent element classes.
      $altTextField.parent().removeClass('form-disabled');

      // Edit label element.
      $altTextField.parent().find('label').addClass('form-required');
    }
  }

  Drupal.behaviors.decorativeImageWidget = {
    attach(context) {
      // When the decorative image checkbox is checked, disable the alt
      // text field.
      $(
        once(
          'decorative-image-widget',
          '.image-widget .decorative-checkbox',
          context,
        ),
      ).each(function processCheckbox() {
        const $altTextField = $(this).parent().parent().find('.alt-textfield');

        $(this).change(function onChange() {
          enableOrDisableAltTextField($altTextField, !this.checked);
        });
        enableOrDisableAltTextField($altTextField, !this.checked);
      });
    },
  };
})(jQuery, Drupal, once);
