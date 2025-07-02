/**
 * @file
 * JavaScript behaviors for jquery.inputmask integration.
 */

(function ($, Drupal, once) {

  'use strict';

  // Revert: Set currency prefix to empty by default #2066.
  // @see https://github.com/RobinHerbots/Inputmask/issues/2066
  if (window.Inputmask) {
    window.Inputmask.extendAliases({
      currency: {
        prefix: '$ ',
        groupSeparator: ',',
        alias: 'numeric',
        placeholder: '0',
        autoGroup: true,
        digits: 2,
        digitsOptional: false,
        clearMaskOnLostFocus: false
      },
      currency_negative: {
        prefix: '$ ',
        groupSeparator: ',',
        alias: 'numeric',
        placeholder: '0',
        autoGroup: true,
        digits: 2,
        digitsOptional: false,
        clearMaskOnLostFocus: false
      },
      currency_positive_negative: {
        prefix: '$ ',
        groupSeparator: ',',
        alias: 'numeric',
        placeholder: '0',
        autoGroup: true,
        digits: 2,
        digitsOptional: false,
        clearMaskOnLostFocus: false
      },
      decimal: {
        prefix: '',
        groupSeparator: ',',
        alias: 'numeric',
        placeholder: '0',
        autoGroup: true,
        digits: 3,
        digitsOptional: true,
        clearMaskOnLostFocus: false
      },
      decimal_negative: {
        prefix: '',
        groupSeparator: ',',
        alias: 'numeric',
        placeholder: '0',
        autoGroup: true,
        digits: 3,
        digitsOptional: true,
        clearMaskOnLostFocus: false
      },
      decimal_positive_negative: {
        prefix: '',
        groupSeparator: ',',
        alias: 'numeric',
        placeholder: '0',
        autoGroup: true,
        digits: 3,
        digitsOptional: true,
        clearMaskOnLostFocus: false
      }
    });
  }

  /**
   * Initialize input masks.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.webformInputMask = {
    attach: function (context) {
      if (!$.fn.inputmask) {
        return;
      }

      $(once('webform-input-mask', 'input.js-webform-input-mask', context)).inputmask();
    }
  };

})(jQuery, Drupal, once);
