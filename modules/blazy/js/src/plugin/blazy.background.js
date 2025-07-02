/**
 * @file
 * Provides background extension for dBlazy.
 */

(function ($) {

  'use strict';

  var DATA_SRC = 'data-src';
  var DATA_B = 'data-b-';
  var C_ENCODED = 'is-b-encoded';
  var CACHES = {};

  /**
   * Updates CSS background with multi-breakpoint images.
   *
   * @private
   *
   * @param {dBlazy|Array.<Element>|Element} els
   *   The container HTML element(s), or dBlazy instance.
   * @param {Object} winData
   *   Containing ww: windowWidth, and up: to use min-width or max-width.
   *
   * @return {Object}
   *   This dBlazy object.
   */
  function bg(els, winData) {
    var chainCallback = function (el) {
      if ($.isElm(el)) {
        var url = $.bgUrl(el, winData);

        if (url) {
          el.style.backgroundImage = 'url("' + url + '")';
          $.removeAttr(el, DATA_SRC);
        }
      }
    };

    return $.chain(els, chainCallback);
  }

  $.bgUrl = function (el, winData) {
    var str = $.attr(el, DATA_B + 'bg');
    var token = $.attr(el, DATA_B + 'token');
    var data = CACHES[token];

    if (!data) {
      if ($.hasClass(el, C_ENCODED)) {
        str = atob(str);
      }

      data = $.parse(str);
      CACHES[token] = data;
    }

    if (!$.isEmpty(data)) {
      var obj = $.activeWidth(data, winData);
      if (obj && !$.isUnd(obj)) {
        var ratio = obj.ratio;

        // Allows to disable Aspect ratio if it has known/ fixed heights such as
        // gridstack multi-size boxes.
        if (ratio && !$.hasClass(el, 'b-noratio')) {
          el.style.paddingBottom = ratio + '%';
        }
        return obj.src;
      }
    }
    return $.attr(el, DATA_SRC);
  };

  $.bg = bg;
  $.fn.bg = function (winData) {
    return bg(this, winData);
  };

}(dBlazy));
