/**
 * @file
 * Views admin UI functionality.
 */

(function ($, Drupal) {

  'use strict';

  /**
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.views_bulk_operations = {
    attach: function (context, settings) {
      once('views-bulk-operations-ui', '.views-bulk-operations-ui', context).forEach(Drupal.viewsBulkOperationsUi);
    }
  };

  /**
   * Callback used in {@link Drupal.behaviors.views_bulk_operations}.
   *
   * @param {object} element
   */
  Drupal.viewsBulkOperationsUi = function (element) {
    var $uiElement = $(element);

    // Select / deselect all functionality.
    var actionsElementWrapper = $uiElement.find('details.vbo-actions-widget > .details-wrapper');
    if (actionsElementWrapper.length) {
      var checked = false;
      var allHandle = $('<a href="#" class="vbo-all-switch">' + Drupal.t('Select / deselect all') + '</a>');
      actionsElementWrapper.prepend(allHandle);
      allHandle.on('click', function (event) {
        event.preventDefault();
        checked = !checked;
        actionsElementWrapper.find('.vbo-action-state').each(function () {
          $(this).prop('checked', checked);
          $(this).trigger('change');
        });
        return false;
      });
    }
  };
})(jQuery, Drupal);
