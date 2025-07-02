/**
 * @file
 * JavaScript behaviors for excluded elements.
 */

(function ($, Drupal, once) {

  'use strict';

  /**
   * Add excluded element composite element support.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.webformExcludedElementsComposite = {
    attach: function (context) {
      $(once('webform-excluded-elements', '.form-type-webform-excluded-elements [data-composite] input[type="checkbox"]'))
        .on('click', function () {
          var checked = this.checked;
          var compositeKey = this.value;
          $(this)
            .closest('table')
            .find('[data-composite-parent="' + compositeKey + '"]')
            .each(function () {
              // Toggle selected class on table row.
              $(this).toggleClass('selected', checked);

              // Toggled enabled/disabled and checked on the
              // composite sub-element.
              $('input:checkbox', this)
                .attr('disabled', !checked)
                .prop('checked', checked);
            });
        });
    }
  };

})(jQuery, Drupal, once);
