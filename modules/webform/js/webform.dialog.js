/**
 * @file
 * JavaScript behaviors for webform dialogs.
 */

(function ($, Drupal, drupalSettings, once) {

  'use strict';

  // @see http://api.jqueryui.com/dialog/
  Drupal.webform = Drupal.webform || {};
  Drupal.webform.dialog = Drupal.webform.dialog || {};
  Drupal.webform.dialog.options = Drupal.webform.dialog.options || {};

  /**
   * Programmatically open a webform (or page) in a dialog.
   *
   * @param {string} url
   *   Webform URL.
   * @param {string} type
   *   Webform dialog type defined via /admin/structure/webform/config.
   */
  Drupal.webformOpenDialog = function (url, type) {
    // Create a div with link but don't attach it to the page.
    var $div = $('<div><a href="' + url + '" class="webform-dialog ' + type + '"></a></div>');
    // Init the webform dialog behavior.
    Drupal.behaviors.webformDialog.attach($div.get(0));
    // Trigger the link.
    $div.find('a').trigger('click');
  };

  /**
   * Open webform dialog using preset options.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.webformDialog = {
    attach: function (context) {
      $(once('webform-dialog', 'a.webform-dialog', context)).each(function () {
        var $a = $(this);

        // Get default options.
        var options = $.extend({}, Drupal.webform.dialog.options);

        // Get preset dialog options.
        if ($a.attr('class').match(/webform-dialog-([a-z0-9_]+)/)) {
          var dialogOptionsName = RegExp.$1;
          if (drupalSettings.webform.dialog.options[dialogOptionsName]) {
            options = drupalSettings.webform.dialog.options[dialogOptionsName];

            // Unset title.
            delete options.title;
          }
        }

        // Get custom dialog options.
        if ($(this).data('dialog-options')) {
          $.extend(options, $(this).data('dialog-options'));
        }

        var href = $a.attr('href');

        // Replace ENTITY_TYPE and ENTITY_ID placeholders and update the href.
        // @see webform_page_attachments()
        if (href.indexOf('?source_entity_type=ENTITY_TYPE&source_entity_id=ENTITY_ID') !== -1) {
          if (drupalSettings.webform.dialog.entity_type && drupalSettings.webform.dialog.entity_id) {
            href = href.replace('ENTITY_TYPE', encodeURIComponent(drupalSettings.webform.dialog.entity_type));
            href = href.replace('ENTITY_ID', encodeURIComponent(drupalSettings.webform.dialog.entity_id));
          }
          else {
            href = href.replace('?source_entity_type=ENTITY_TYPE&source_entity_id=ENTITY_ID', '');
          }
          $a.attr('href', href);
        }

        // Append _webform_dialog=1 to href to trigger Ajax support.
        // @see \Drupal\webform\WebformSubmissionForm::setEntity
        href += (href.indexOf('?') === -1 ? '?' : '&') + '_webform_dialog=1';

        var element_settings = {};
        element_settings.progress = {type: 'fullscreen'};
        element_settings.url = href;
        element_settings.event = 'touchstart click';
        element_settings.dialogType = $a.data('dialog-type') || 'modal';
        element_settings.dialog = options;
        element_settings.element = this;
        element_settings.error = function error(xmlhttp, uri) {
          if (xmlhttp.status === 403) {
            window.location.replace(href.split('?')[0]);
          }
        };
        Drupal.ajax(element_settings);
      });
    }
  };

})(jQuery, Drupal, drupalSettings, once);
