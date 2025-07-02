(function ($, window, Drupal) {
  'use strict';

  $(window).on('dialog:beforeclose', (event, dialog, $element) => {
    // Preserve save callback, for using it in "editor:dialogsave" event.
    if ($element.hasClass('ib-dam-browser-dialog')) {
      if (typeof Drupal.ckeditor !== "undefined") {
        Drupal.ibDamAppCkeditorSaveCallback = Drupal.ckeditor.saveCallback;
      }
      else if (typeof Drupal.ckeditor5 !== "undefined") {
        Drupal.ibDamAppCkeditorSaveCallback = Drupal.ckeditor5.saveCallback;
      }
    }
  });

  $(window).on('editor:dialogsave', (event, values) => {
    if (Drupal.ibDamAppCkeditorSaveCallback) {
      // Execute save callback manually.
      Drupal.ibDamAppCkeditorSaveCallback(values);
      // And drop it as we don't need it anymore.
      Drupal.ibDamAppCkeditorSaveCallback = null;
    }
  });

})(jQuery, window, Drupal);
