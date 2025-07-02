/**
 * @file
 * Provides base methods to bridge drupal-related codes with generic ones.
 *
 * @todo watch out for Drupal namespace removal, likely becomes under window.
 */

(function ($, Drupal, _win) {

  'use strict';

  $.debounce = function (cb, arg, scope, delay) {
    var _cb = function () {
      cb.call(scope, arg);
    };

    if (scope) {
      return Drupal.debounce(_cb, delay || 201, true);
    }

    return Drupal.debounce.call(this, cb);
  };

  $.matchMedia = function (width, minmax) {
    if (_win.matchMedia) {
      if ($.isUnd(minmax)) {
        minmax = 'max';
      }
      var mq = _win.matchMedia('(' + minmax + '-device-width: ' + width + ')');
      return mq.matches;
    }
    return false;
  };

  function real(el) {
    return el.target || el;
  }

  function is(el, name) {
    return $.hasClass(real(el), name);
  }

  $.isBg = function (el, opts) {
    return is(el, opts && opts.bgClass || 'b-bg');
  };

  $.isBlur = function (el) {
    return is(el, 'b-blur');
  };

  $.isGrid = function (el) {
    return $.isElm($.closest(real(el), '.grid'));
  };

  $.isHtml = function (el) {
    return is(el, 'b-html');
  };

  $.image = {

    alt: function (el, fallback) {
      var img = $.find(el, 'img:not(.b-blur)');
      var alt = $.attr(img, 'alt');

      fallback = fallback || 'Video preview';

      // If using BG.
      if (!alt) {
        var cn = $.find(el, '.media');
        alt = $.attr(cn, 'title');
      }

      // If nobody put the important info, add a fallback.
      return alt ? Drupal.checkPlain(alt) : Drupal.t(fallback);
    },

    ratio: function (data) {
      var width = $.toInt(data.width, 640);
      var height = $.toInt(data.height, 360);

      return ((height / width) * 100).toFixed(2);
    },

    // https://stackoverflow.com/questions/3971841
    scale: function (srcWidth, srcHeight, maxWidth, maxHeight) {
      var ratio = Math.min(maxWidth / srcWidth, maxHeight / srcHeight);

      return {
        width: srcWidth * ratio,
        height: srcHeight * ratio,
        ratio: ratio
      };
    },

    dimension: function (w, h) {
      return {
        width: w,
        height: h
      };
    },

    hack: function (a, b) {
      return {
        paddingBottom: a,
        height: b
      };
    }
  };

})(dBlazy, Drupal, this);
