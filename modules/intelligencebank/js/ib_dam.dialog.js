/**
 * @file
 * Extends methods from core/misc/dialog/dialog.ajax.js.
 *
 * Based on https://www.drupal.org/project/media_library_form_element/issues/3155697#comment-13725927
 */
(function ($, window, Drupal, drupalSettings) {

  'use strict';

  drupalSettings.dialogStack = [];

  Drupal.behaviors.stackedDialog = {
    attach: function attach(context, settings) {
      // Remove the core drupal modal.
      if ($('#drupal-modal').length) {
        $('#drupal-modal').remove();
      }

      let originalClose = settings.dialog.close;
      settings.dialog.close = function (event) {
        originalClose.apply(settings.dialog, arguments);
        $(event.target).remove();

        let lastSelector = drupalSettings.dialogStack[drupalSettings.dialogStack.length - 1] || false;

        if (lastSelector && $(event.target).attr('id') === lastSelector.replace(/^#/, '')) {
          settings.dialogStack.pop();
        }

        // Set current modal visible
        $('.ui-dialog').css('visibility', 'hidden')
          .last()
          .css('visibility', 'visible');
      };
    }
  };

  Drupal.AjaxCommands.prototype.openDialog = function (ajax, response, status) {
    if (!response.selector) {
      return false;
    }
    let $dialog = $(response.selector);

    if (!$dialog.length) {
      if (response.selector === '#drupal-modal') {
        response.selector = '#drupal-modal-' + Math.random().toString(36).substr(2, 16);
      }
      $dialog = $('<div id="' + response.selector.replace(/^#/, '') + '" class="ui-front"/>').appendTo('body');

      drupalSettings.dialogStack.push(response.selector);
    }

    if (!ajax.wrapper) {
      ajax.wrapper = $dialog.attr('id');
    }

    response.command = 'insert';
    response.method = 'html';
    ajax.commands.insert(ajax, response, status);

    if (!response.dialogOptions.buttons) {
      response.dialogOptions.drupalAutoButtons = true;
      response.dialogOptions.buttons = Drupal.behaviors.dialog.prepareDialogButtons($dialog);
    }

    $dialog.on('dialogButtonsChange', function () {
      let buttons = Drupal.behaviors.dialog.prepareDialogButtons($dialog);
      $dialog.dialog('option', 'buttons', buttons);
    });

    response.dialogOptions = response.dialogOptions || {};
    let dialog = Drupal.dialog($dialog.get(0), response.dialogOptions);
    if (response.dialogOptions.modal) {
      dialog.showModal();
    }
    else {
      dialog.show();
    }

    $dialog.parent().find('.ui-dialog-buttonset').addClass('form-actions');

    // Temporarily hide lower modals
    $('.ui-dialog').css('visibility', 'hidden')
      .last()
      .css('visibility', 'visible');

    // Remove additional overlays
    $('.ui-widget-overlay').not(':first').remove();
  };

  Drupal.AjaxCommands.prototype.closeDialog = function (ajax, response, status) {
    if (response.selector === '#drupal-modal') {
      response.selector = drupalSettings.dialogStack[drupalSettings.dialogStack.length - 1] || '#drupal-modal';
    }

    let $dialog = $(response.selector);
    if ($dialog.length) {
      drupalSettings.dialogStack.pop();

      Drupal.dialog($dialog.get(0)).close();
      if (!response.persist) {
        $dialog.remove();
      }
    }

    $dialog.off('dialogButtonsChange');
  };
})(jQuery, this, Drupal, drupalSettings);
