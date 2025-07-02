/**
 * @file
 * Provides CSS3 Native Grid for dynamic multi-breakpoint grids.
 *
 * @see https://developer.mozilla.org/en-US/docs/Web/CSS/CSS_Grid_Layout
 */

(function ($, Drupal) {

  'use strict';

  $.dyGrid = $.dyGrid || {};

  var NICK = 'nativegrid';
  var ID_ONCE = 'b-' + NICK;
  var C_NAME = 'is-' + ID_ONCE;
  var DATA_ID = 'data-b-' + NICK;
  var S_ELEMENT = '[' + DATA_ID + ']';
  var VALID = false;
  var UNLOAD;

  /**
   * Attaches Blazy behavior to HTML element identified by .b-nativegrid.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.blazyNativeGrid = {
    attach: function (context) {

      $.wwoBigPipe(function () {
        var roots = $.once(ID_ONCE, S_ELEMENT, context);

        if (roots.length) {
          VALID = true;
          var opts = {
            nick: NICK,
            cName: C_NAME,
            dataId: DATA_ID,
            md: '--bn-md',
            lg: '--bn-lg',
            selector: S_ELEMENT,
            unload: UNLOAD
          };

          $.dyGrid.init(roots, opts);
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
