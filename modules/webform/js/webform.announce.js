/**
 * @file
 * JavaScript behaviors for announcing changes.
 */

(function ($, Drupal, once) {

  'use strict';

  /**
   * Provide Webform announce attribute behavior.
   *
   * Announces changes using [data-webform-announce] attribute.
   *
   * The announce attributes allows FAPI Ajax callbacks to easily
   * trigger announcements.
   *
   * @see \Drupal\webform\Element\WebformComputedBase::ajaxWebformComputedCallback
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches the behavior to [data-webform-announce] attribute.
   */
  Drupal.behaviors.webformAnnounce = {
    attach: function (context) {
      $(once('data-webform-announce', '[data-webform-announce]', context)).each(function () {
        Drupal.announce($(this).data('webform-announce'));
      });
    }
  };

})(jQuery, Drupal, once);
