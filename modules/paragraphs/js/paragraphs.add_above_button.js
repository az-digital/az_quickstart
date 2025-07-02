/**
 * @file
 * Paragraphs actions JS code for paragraphs actions button.
 */

(function ($, Drupal, once) {

  'use strict';

  /**
   * Handle event when "Add above" button is clicked.
   *
   * @param event
   *   Click event.
   */
  var clickHandler = function (event) {
    event.preventDefault();
    // We need to stop event propagation in order to prevent triggering jQuery
    // UI dialog.js mousedown method. This method order call is not predictable.
    // When we are in dialog for editing reusable library (parent dialog) then
    // for 'Add Paragraph' button it will be called before child dialog
    // creation, but for 'Add above' button it will be called after child dialog
    // creation and this will result in moving parent dialog in front of
    // child dialog.
    event.stopPropagation();

    var $button = $(this);

    // Find delta for row without interference of unrelated table rows.
    var $anchorRow = $button.closest('tr');
    var delta = $anchorRow.parent().find('> .draggable').index($anchorRow);
    // If the form table has a layout wrapper use that (see claro / gin).
    var $table = $button.closest('.field-multiple-table');
    var $layer_wrapper = $table.closest('.layer-wrapper');
    $table = $layer_wrapper.length > 0 ? $layer_wrapper : $table;
    // We need the siblings function to avoid finding the 'Add paragraph' button inside a container.
    var $add_more_wrapper = $table.siblings('.clearfix,.form-actions,.multiple-value-form-actions,.field-actions').find('.paragraphs-add-wrapper');

    // Set delta before opening of dialog.
    $add_more_wrapper.find('.paragraph-type-add-delta').val(delta);

    // If the add mode is modal open the dialog, otherwise press the add button.
    if ($add_more_wrapper.find('.paragraph-type-add-delta').hasClass('modal')) {
      Drupal.paragraphsAddModal.openDialog($add_more_wrapper.find('.paragraphs-add-dialog'), Drupal.t('Add above'));
    }
    else {
      $add_more_wrapper.find('.field-add-more-submit').trigger('mousedown');
    }
  };

  /**
   * Process paragraph_AddAboveButton elements.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.paragraphsAddAboveButton = {
    attach: function (context, settings) {
      $(once('paragraphs-add-above-button', '.paragraphs-dropdown-actions', context)).each(function () {
        var $actions = $(this);
        if ($actions.closest('.paragraph-top').hasClass('add-above-on')) {
          var $add_above = false;
          // If the form table has a layout wrapper use that (see claro / gin).
          var $table = $actions.closest('.field-multiple-table');
          var $layer_wrapper = $table.closest('.layer-wrapper');
          $table = $layer_wrapper.length > 0 ? $layer_wrapper : $table;
          var $add_more_wrapper = $table.siblings('.clearfix,.form-actions,.multiple-value-form-actions,.field-actions').find('.paragraphs-add-wrapper');
          // The Add Above button is added when the add mode is modal or when
          // there is only one add button in the other add modes.
          if ($add_more_wrapper.find('.paragraph-type-add-delta').hasClass('modal') || $add_more_wrapper.find('.field-add-more-submit').length === 1) {
            $add_above = true;
          }
          // Prepend the Add above button only if there is only one button.
          if ($add_above) {
            var $button = $('<input class="paragraphs-dropdown-action paragraphs-dropdown-action--add-above button button--small js-form-submit form-submit" type="submit" onClick="return false;" value="' + Drupal.t('Add above') + '">');
            // "Mousedown" is used since the original actions created by
            // paragraphs use the event "focusout" to hide the actions dropdown.
            $button.on('mousedown', clickHandler);

            $actions.prepend($button);
          }
        }
      });
    }
  };

})(jQuery, Drupal, once);
