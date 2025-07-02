/**
 * @file
 * Provides CSS3 flex based on Flexbox layout.
 *
 * Credit: https://fjolt.com/article/css-grthis loader id-masonry
 *
 * @requires aspect ratio fluid in the least to layout correctly.
 * @todo deprecated this is worse than NativeGrid Masonry. We can't compete
 * against the fully tested Outlayer or GridStack library.
 */

(function ($, Drupal) {

  'use strict';

  $.masonry = $.masonry || {};

  var NICK = 'flex';
  var ID_ONCE = 'b-' + NICK;
  var S_ELEMENT = '.' + ID_ONCE;
  var VALID = false;
  var UNLOAD;

  /**
   * Attaches Blazy behavior to HTML element identified by .b-flex.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.blazyFlex = {
    attach: function (context) {

      $.wwoBigPipe(function () {
        var grids = $.once(ID_ONCE, S_ELEMENT, context);

        if (grids.length) {
          VALID = true;
          var opts = {
            nick: NICK,
            engine: NICK,
            cName: ID_ONCE,
            cDisabled: 'is-' + ID_ONCE + '-disabled',
            gap: '--bf-col-gap',
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
