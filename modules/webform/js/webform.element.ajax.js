/**
 * @file
 * JavaScript behaviors for webforms.
 */

(function ($, Drupal, once) {

  'use strict';

  /**
   * Attach behaviors to trigger submit button from input onchange.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches form trigger submit events.
   */
  Drupal.behaviors.webformSubmitTrigger = {
    attach: function (context) {
      $(once('webform-trigger-submit', '[data-webform-trigger-submit]')).on('change', function () {
        var submit = $(this).attr('data-webform-trigger-submit');
        $(submit).trigger('mousedown');
      });
    }
  };

})(jQuery, Drupal, once);
