/**
 * @file
 * Provides Intersection Observer API AJAX helper.
 *
 * Blazy IO works fine with AJAX, until using VIS, or alike. Adds a helper.
 * Required to fix for what Native lazy doesn't support Blur, Video, BG.
 * Similar to core responsive_image/ajax fix, only different approach.
 *
 * @todo remove once bio.js plays nice for media, VIS, blocks, or if core/once
 * fixes this type of issue when min D9.2.
 */

(function ($, Drupal, _doc) {

  'use strict';

  var D_BLAZY = Drupal.blazy || {};
  var D_AJAX = Drupal.Ajax || {};
  var PROTO = D_AJAX.prototype;
  var REV_TIMER;

  if (!PROTO) {
    return;
  }

  // Overrides Drupal.Ajax.prototype.success to re-observe new AJAX contents.
  PROTO.success = (function (D_AJAX) {
    return function (response, status) {
      var me = D_BLAZY.init;
      var opts;

      clearTimeout(REV_TIMER);

      // DOM ready fix. Be sure Views "Use field template" is disabled.
      REV_TIMER = setTimeout(function () {
        if (response && response.length) {
          $.once.unload = true;

          if (me) {
            opts = D_BLAZY.options;
            var el = $.find(_doc, $.selector(opts, true));
            if (el) {
              // See blazy.load.js.
              $.once.removeSafely('b-root', 'body', _doc);

              Drupal.attachBehaviors(_doc.body);
            }
          }

          $.trigger('blazy:ajaxSuccess', [me, response, status]);
        }
      }, 100);

      return D_AJAX.apply(this, arguments);
    };
  })(PROTO.success);

})(dBlazy, Drupal, this.document);
