/**
 * @file
 * JavaScript behaviors for managed file uploads.
 */

(function ($, Drupal, once) {

  'use strict';

  /**
   * Track file uploads and display confirm dialog when an file upload is in progress.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.webformManagedFileAutoUpload = {
    attach: function attach(context) {
      // Add submit handler to file upload form.
      $(once('webform-auto-file-upload', 'form', context))
        .on('submit', function (event) {
          var $form = $(this);
          if ($form.data('webform-auto-file-uploads') > 0 && Drupal.webformManagedFileBlockSubmit($form)) {
            event.preventDefault();
            return false;
          }
          return true;
        });

      // Add submit handler to form.beforeSend.
      // Update Drupal.Ajax.prototype.beforeSend only once.
      if (typeof Drupal.Ajax !== 'undefined' && typeof Drupal.Ajax.prototype.beforeSubmitWebformManagedFileAutoUploadOriginal === 'undefined') {
        Drupal.Ajax.prototype.beforeSubmitWebformManagedFileAutoUploadOriginal = Drupal.Ajax.prototype.beforeSubmit;
        Drupal.Ajax.prototype.beforeSubmit = function (form_values, element_settings, options) {
          var $form = this.$form;
          var $element = $(this.element);

          // Determine if the triggering element is within .form-actions.
          var isFormActions = $element
            .closest('.form-actions').length;

          // Determine if the triggering element is within a multiple element.
          var isMultipleUpload = $element
            .parents('.js-form-type-webform-multiple, .js-form-type-webform-custom-composite')
            .find('.js-form-managed-file').length;

          // Determine if the triggering element is not within a
          // managed file element.
          var isManagedUploadButton = $element.parents('.js-form-managed-file').length;

          // Only trigger block submit for .form-actions and multiple element
          // with file upload.
          if ($form.data('webform-auto-file-uploads') > 0 &&
            (isFormActions || (isMultipleUpload && !isManagedUploadButton)) &&
            Drupal.webformManagedFileBlockSubmit($form)) {
            this.ajaxing = false;
            return false;
          }
          return this.beforeSubmitWebformManagedFileAutoUploadOriginal.apply(this, arguments);
        };
      }

      $(once('webform-auto-file-upload', 'input[type="file"]', context)).on('change', function () {
        // Track file upload.
        $(this).data('webform-auto-file-upload', true);

        // Increment form file uploads.
        var $form = $(this.form);
        var fileUploads = ($form.data('webform-auto-file-uploads') || 0);
        $form.data('webform-auto-file-uploads', fileUploads + 1);
      });
    },
    detach: function detach(context, settings, trigger) {
      if (trigger === 'unload') {
        const removedElements = once.remove('webform-auto-file-upload', 'input[type="file"]', context);
        $(removedElements).each(function () {
          if ($(this).data('webform-auto-file-upload')) {
            // Remove file upload tracking.
            $(this).removeData('webform-auto-file-upload');

            // Decrease form file uploads.
            var $form = $(this.form);
            var fileUploads = ($form.data('webform-auto-file-uploads') || 0);
            $form.data('webform-auto-file-uploads', fileUploads - 1);
          }
        });
      }
    }
  };

  /**
   * Block form submit.
   *
   * @param {jQuery} form
   *   A form.
   *
   * @return {boolean}
   *   TRUE if form submit should be blocked.
   */
  Drupal.webformManagedFileBlockSubmit = function (form) {
    if ($(form).data('webform-auto-file-uploads') < 0) {
      return false;
    }

    var message = Drupal.t('File upload in progress. Uploaded file may be lost.') +
      '\n' +
      Drupal.t('Click OK to submit the form without finishing the file upload or cancel to return to form.');
    var result = !window.confirm(message);

    // If submit once behavior is available, make sure to clear it if the form
    // can be submitted.
    if (result && Drupal.behaviors.webformSubmitOnce) {
      setTimeout(function () {Drupal.behaviors.webformSubmitOnce.clear();});
    }

    return result;
  }

})(jQuery, Drupal, once);
