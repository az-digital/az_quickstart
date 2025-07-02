/**
 * @file
 * JavaScript behaviors for input hiding.
 */

(function ($, Drupal, once) {

  'use strict';

  var isChrome = (/chrom(e|ium)/.test(window.navigator.userAgent.toLowerCase()));

  /**
   * Initialize input hiding.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.webformInputHide = {
    attach: function (context) {
      // Apply chrome fix to prevent password input from being autofilled.
      // @see https://stackoverflow.com/questions/15738259/disabling-chrome-autofill
      if (isChrome) {
        $(once('webform-input-hide-chrome-workaround', 'form:has(input.js-webform-input-hide)', context))
          .each(function () {
            $(this).prepend('<input style="display:none" type="text" name="chrome_autocomplete_username"/><input style="display:none" type="password" name="chrome_autocomplete_password"/>');
          });
      }

      // Convert text based inputs to password input on blur.
      $(once('webform-input-hide', 'input.js-webform-input-hide', context))
        .each(function () {
          var type = this.type;
          // Initialize input hiding.
          this.type = 'password';

          // Attach blur and focus event handlers.
          $(this)
            .on('blur', function () {
              this.type = 'password';
              var off = /chrom(e|ium)/.test(window.navigator.userAgent.toLowerCase()) ? 'chrome-off-' + Math.floor(Math.random() * 100000000) : 'off';
              $(this).attr('autocomplete', off);
            })
            .on('focus', function () {
              this.type = type;
              $(this).removeAttr('autocomplete');
            });
        });
    }
  };

})(jQuery, Drupal, once);
