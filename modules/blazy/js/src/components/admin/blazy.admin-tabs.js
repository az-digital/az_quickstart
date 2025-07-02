/**
 * @file
 * Provides tabs utility.
 */

(function ($, Drupal, _win) {

  'use strict';

  var ID = 'b-tabs';
  var ID_ONCE = ID;
  var S_ELEMENT = '.' + ID;
  var S_LABEL = '.b-tabs__label';
  var IS_ACTIVE = 'is-active';

  /**
   * Utility functions.
   *
   * @param {HTMLElement} el
   *   The HTML element.
   */
  function process(el) {
    var labels = $.findAll(el, S_LABEL);
    $.addClass(labels[0], IS_ACTIVE);

    $.on(el, 'click.' + ID, S_LABEL, function (e) {
      $.removeClass(labels, IS_ACTIVE);
      $.addClass(e.target, IS_ACTIVE);
    });
  }

  /**
   * Attaches Blazy tabs behavior to HTML element.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.blazyAdminTabs = {
    attach: function (context) {
      $.once(process, ID_ONCE, S_ELEMENT, context);
    },
    detach: function (context, setting, trigger) {
      if (trigger === 'unload') {
        $.once.removeSafely(ID_ONCE, S_ELEMENT, context);
      }
    }
  };

})(dBlazy, Drupal, this);
