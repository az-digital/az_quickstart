/**
 * @file
 * Provides CSS3 Native Grid treated as Masonry based on Grid Layout.
 *
 * @see https://developer.mozilla.org/en-US/docs/Web/CSS/CSS_Grid_Layout
 * The two-dimensional Native Grid does not use JS until treated as a Masonry.
 * If you need GridStack kind, avoid inputting numeric value for Grid.
 * Below is the cheap version of GridStack.
 *
 * @credit: https://css-tricks.com/a-lightweight-masonry-solution/
 */

(function ($, Drupal) {

  'use strict';

  $.masonry = $.masonry || {};

  var ENGINE = 'nativegrid';
  var NICK = 'masonry';
  var ID_ONCE = 'b-' + NICK;
  var IS_MASONRY = 'is-' + ID_ONCE;
  var S_ELEMENT = '.' + IS_MASONRY;
  var VALID = false;
  var UNLOAD;

  /**
   * Attaches Blazy behavior to HTML element identified by .b-nativegrid.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.blazyNativeGridMasonry = {
    attach: function (context) {

      $.wwoBigPipe(function () {
        var grids = $.once(ID_ONCE, S_ELEMENT, context);

        if (grids.length &&
          getComputedStyle(grids[0]).gridTemplateRows !== 'masonry') {
          VALID = true;
          var opts = {
            nick: NICK,
            engine: ENGINE,
            cName: IS_MASONRY,
            cDisabled: IS_MASONRY + '-disabled',
            selector: S_ELEMENT,
            unload: UNLOAD
          };
          $.masonry.init(grids, opts);
        }
      });

    },
    detach: function (context, setting, trigger) {
      if (VALID && trigger === 'unload') {
        // Prevents from BigPipe problematic multiple invocations.
        $.wwoBigPipe(function () {
          $.once.removeSafely(ID_ONCE, S_ELEMENT, context);
          UNLOAD = $.once.unload;
        });
      }
    }

  };

}(dBlazy, Drupal));
