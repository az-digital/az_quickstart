/**
 * @file
 * JavaScript behaviors for form tabs using Tabby.
 */

(function ($, Drupal, once) {

  'use strict';

  // @see https://github.com/cferdinandi/tabby
  Drupal.webform = Drupal.webform || {};
  Drupal.webform.formTabs = Drupal.webform.formTabs || {};
  Drupal.webform.formTabs.options = Drupal.webform.formTabs.options || {};

  /**
   * Initialize webform tabs.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches the behavior for form tabs using jQuery UI.
   *
   * @see \Drupal\webform\Utility\WebformFormHelper::buildTabs
   */
  Drupal.behaviors.webformFormTabs = {
    attach: function (context) {
      if (!window.Tabby) {
        return;
      }

      $(once('webform-tabs', 'div.webform-tabs', context)).each(function () {
        // Set active tab and clear the location hash once it is set.
        var tabIndex = 0;
        if (location.hash) {
          tabIndex = $('a[href="' + Drupal.checkPlain(location.hash) + '"]').data('tab-index');
          if (typeof tabIndex !== 'undefined') {
            location.hash = '';
          }
        }

        var options = jQuery.extend({
          'default': '[data-tab-index="' + tabIndex + '"]',
        }, Drupal.webform.formTabs.options);

        new Tabby('div.webform-tabs .webform-tabs-item-list', options);
      });
    }
  };

})(jQuery, Drupal, once);
