/**
 * @file paragraphs.modal.js
 *
 */

(function ($, Drupal, once) {

  'use strict';

  /**
   * Click handler for click "Add" button between paragraphs.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.paragraphsModalAdd = {
    attach: function (context) {
      $(once('add-click-handler', '.paragraph-type-add-modal-button', context)).on('click', function (event) {
        var $button = $(this);
        Drupal.paragraphsAddModal.openDialog($button.parent().siblings('.paragraphs-add-dialog'), $button.val());

        // Stop default execution of click event.
        event.preventDefault();
        event.stopPropagation();
      });
    }
  };

  /**
   * Namespace for modal related javascript methods.
   *
   * @type {Object}
   */
  Drupal.paragraphsAddModal = {};

  /**
   * Open modal dialog for adding new paragraph in list.
   *
   * @param {Object} element
   *   The element that holds the dialog.
   * @param {string} title
   *   The title of the dialog.
   *
   * @return {Object}
   *   Dialog object.
   */
  Drupal.paragraphsAddModal.openDialog = function (element, title) {
    var $element = $(element);

    // Get the delta element before moving $element to dialog element.
    var $modalDelta = $element.parent().find('.paragraph-type-add-delta');

    // Deep clone with all attached events. We need to work on cloned element
    // and not directly on origin because Drupal dialog.ajax.js
    // Drupal.behaviors.dialog will do remove of origin element on dialog close.
    $element = $element.clone(true);

    var dialog = Drupal.dialog($element, {
      autoResize: true,
      resizable: false,
      title: title,
      width: 'auto',
      paragraphsModalDelta: $modalDelta,
    });
    dialog.showModal();

    // Close the dialog after a button was clicked.
    // Use mousedown event, because we are using ajax in the modal add mode
    // which explicitly suppresses the click event.
    $(once('paragraphs-add-more-submit-modal', $element.find('.field-add-more-submit'))).on('mousedown', function () {
      dialog.close();
    });

    return dialog;
  };

  $(window).on({
    'dialog:afterclose': function (event, dialog, $element) {
      // Check first if dialog instance exist because dialog:afterclose will
      // be triggered two times, first from once from dialog.ajax.js
      // Drupal.behaviors.dialog and second time from dialog.js.
      if ($element.dialog('instance') && $element.dialog('option', 'paragraphsModalDelta')) {
        // Reset modal delta value.
        $element.dialog('option', 'paragraphsModalDelta').val('');
      }
    }
  });

})(jQuery, Drupal, once);
