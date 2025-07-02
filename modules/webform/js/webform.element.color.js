/**
 * @file
 * JavaScript behaviors for color element integration.
 */

(function ($, Drupal, once) {

  'use strict';

  /**
   * Enhance HTML5 color element.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.webformColor = {
    attach: function (context) {
      $(once('webform-color', '.form-color:not(.form-color-output)', context)).each(function () {
        var $element = $(this);
        // Handle browser that don't support the HTML5 color input.
        if (Modernizr.inputtypes.color === false) {
          // Remove swatch sizes.
          $element.removeClass('form-color-small')
            .removeClass('form-color-medium')
            .removeClass('form-color-large');
        }
        else {
          // Display color input's output w/ visually-hidden label to
          // the end user.
          var $output = $('<input class="form-color-output ' + $element.attr('class') + ' js-webform-input-mask" data-inputmask-mask="\\#######" />').uniqueId();
          var $label = $element.parent('.js-form-type-color').find('label').clone();
          var id = $output.attr('id');
          $label.attr({for: id, class: 'visually-hidden'});
          if ($.fn.inputmask) {
            $output.inputmask();
          }
          $output[0].value = $element[0].value;
          $element
            .after($output)
            .after($label)
            .css({float: 'left'});

          // Sync $element and $output.
          $element.on('input', function () {
            $output[0].value = $element[0].value;
          });
          $output.on('input', function () {
            $element[0].value = $output[0].value;
          });
        }
      });
    }
  };

})(jQuery, Drupal, once);
