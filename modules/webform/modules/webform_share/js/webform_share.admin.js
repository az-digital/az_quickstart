/**
 * @file
 * JavaScript behaviors for webform share admin.
 */

(function ($, Drupal, once) {

  'use strict';

  /**
   * Webform share admin copy.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.webformShareAdminCopy = {
    attach: function (context) {
      $(once('webform-share-admin-copy', '.js-webform-share-admin-copy', context)).each(function () {
        var $container = $(this);
        var $textarea = $container.find('textarea');
        var $button = $container.find(':submit, :button');
        var $message = $container.find('.webform-share-admin-copy-message');
        // Copy code from textarea to the clipboard.
        // @see https://stackoverflow.com/questions/47879184/document-execcommandcopy-not-working-on-chrome/47880284
        $button.on('click', function () {
          if (window.navigator.clipboard) {
            window.navigator.clipboard.writeText($textarea.val());
          }
          $message.show().delay(1500).fadeOut('slow');
          Drupal.announce(Drupal.t('Code copied to clipboard…'));
          return false;
        });
      });
    }
  };

})(jQuery, Drupal, once);
