/**
 * @file
 * Provides Slick vanilla where options can be directly injected via data-slick.
 */

(function ($, Drupal, _d) {

  'use strict';

  var _id = 'slick-vanilla';
  var _mounted = _id + '--on';
  // @fixme typo at 3.x, should be BEM modifier: .slick--vanilla.
  var _element = '.' + _id + ':not(.' + _mounted + ')';

  /**
   * Slick utility functions.
   *
   * @param {HTMLElement} elm
   *   The slick HTML element.
   */
  function doSlickVanilla(elm) {
    var $elm = $(elm);
    $elm.slick();
    $elm.addClass(_mounted);
  }

  /**
   * Attaches slick behavior to HTML element identified by .slick-vanilla.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.slickVanilla = {
    attach: function (context) {
      _d.once(doSlickVanilla, _id, _element, context);
    },
    detach: function (context, setting, trigger) {
      if (trigger === 'unload' && _d.once.removeSafely) {
        _d.once.removeSafely(_id, _element, context);
      }
    }
  };

})(jQuery, Drupal, dBlazy);
