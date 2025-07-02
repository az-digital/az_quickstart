/**
 * @file
 * Attaches behaviors for the Clientside Validation jQuery module.
 */

(function ($, drupalSettings, once) {

  'use strict';

  // Disable clientside validation for webforms submitted using Ajax.
  // This prevents Computed elements with Ajax from breaking.
  // @see \Drupal\clientside_validation_jquery\Form\ClientsideValidationjQuerySettingsForm
  drupalSettings.clientside_validation_jquery.validate_all_ajax_forms = 0;

  /**
   * Add .cv-validate-before-ajax to all webform submit buttons.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.webformClientSideValidationAjax = {
    attach: function (context) {
      $(once('webform-clientside-validation-ajax', 'form.webform-submission-form .form-actions input[type="submit"]:not([formnovalidate])'))
        .addClass('cv-validate-before-ajax');
    }
  };

  /**
   * Fix date/time min, max, and step validation issues.
   *
   * @type {Drupal~behavior}
   *
   * @see https://github.com/jquery-validation/jquery-validation/pull/2119/commits
   */
  Drupal.behaviors.webformClientSideValidationDateTimeFix = {
    attach: function (context) {
      $(context).find(':input[type="date"], :input[type="time"], :input[type="datetime"]')
        .removeAttr('step')
        .removeAttr('min')
        .removeAttr('max');
    }
  };

  // Trigger 'cvjquery' once to prevent the cv.jquery.ife.js from initializing.
  // The webform_clientside_validation.module loads before the
  // clientside_validation_jquery.module.
  // @see clientside_validation/clientside_validation_jquery/js/cv.jquery.ife.js
  // @see https://www.drupal.org/project/clientside_validation/issues/3322946
  // @see https://www.drupal.org/node/3158256
  //
  // Drupal 10: Using once can not use `window` or `document` directly.
  once('cvjquery', 'html');
  // Drupal 9: Use jQuery once plugin.
  $(document).once && $(document).once('cvjquery');

  $(document).on('cv-jquery-validate-options-update', function (event, options) {
    options.errorElement = 'strong';
    options.showErrors = function (errorMap, errorList) {
      // Show errors using defaultShowErrors().
      this.defaultShowErrors();

      // Add '.form-item--error-message' class to all errors.
      $(this.currentForm).find('strong.error').addClass('form-item--error-message');

      // Move all radios, checkboxes, and datelist errors to appear after
      // the parent container.
      var selectors = [
        '.form-checkboxes',
        '.form-radios',
        '.form-boolean-group',
        '.form-type-datelist .container-inline',
        '.form-type-tel',
        '.webform-type-webform-height .form--inline',
        '.js-webform-tableselect'
      ];
      $(this.currentForm).find(selectors.join(', ')).each(function () {
        var $container = $(this);
        var $errorMessages = $container.find('strong.error.form-item--error-message');
        $errorMessages.insertAfter($container);
      });

      // Move all select2 and chosen errors to appear after the parent container.
      $(this.currentForm).find('.webform-select2 ~ .select2, .webform-chosen ~ .chosen-container').each(function () {
        var $widget = $(this);
        var $select = $widget.parent().find('select');
        var $errorMessages = $widget.parent().find('strong.error.form-item--error-message');
        if ($select.hasClass('error')) {
          $errorMessages.insertAfter($widget);
          $widget.addClass('error');
        }
        else {
          $errorMessages.hide();
          $widget.removeClass('error');
        }
      });

      // Move checkbox errors to appear as the last item in the
      // parent container.
      $(this.currentForm).find('.js-form-type-checkbox').each(function () {
        var $container = $(this);
        var $errorMessages = $container.find('strong.error.form-item--error-message');
        $container.append($errorMessages);
      });

      // Move all likert errors to question <label>.
      $(this.currentForm).find('.webform-likert-table tbody tr').each(function () {
        var $row = $(this);
        var $errorMessages = $row.find('strong.error.form-item--error-message');
        $errorMessages.appendTo($row.find('td:first-child'));
      });

      // Move error after field suffix.
      $(this.currentForm).find('strong.error.form-item--error-message ~ .field-suffix').each(function () {
        var $fieldSuffix = $(this);
        var $errorMessages = $fieldSuffix.prev('strong.error.form-item--error-message');
        $errorMessages.insertAfter($fieldSuffix);
      });

      // Add custom clear error handling to checkboxes to remove the
      // error message, when any checkbox is checked.
      $(once('webform-clientside-validation-form-checkboxes', '.form-checkboxes', this.currentForm)).each(function () {
        var $container = $(this);
        $container.find('input:checkbox').click( function () {
          var state = $container.find('input:checkbox:checked').length ? 'hide' : 'show';
          var $message = $container.next('strong.error.form-item--error-message');
          $message[state]();

          // Ensure the message is set. This code addresses an expected bug
          // where the error message is emptied when it is toggled.
          var message = $container.find('[data-msg-required]').data('msg-required');
          $message.html(message);
        });
      });
    };
  });

})(jQuery, drupalSettings, once);
