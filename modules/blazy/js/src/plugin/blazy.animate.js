/**
 * @file
 * Provides animate extension for dBlazy when using blur or animate.css.
 *
 * Alternative for native Element.animate, only with CSS animation instead.
 * @see https://developer.mozilla.org/en-US/docs/Web/API/Element/animate
 */

(function ($) {

  'use strict';

  var ANI = 'animation';
  var BLUR = 'blur';
  var B_BLUR = 'b-' + BLUR;
  var P_DATA = 'data-';
  // @todo remove at 3.x:
  var DATA_ANIM = P_DATA + 'animation';
  var DATA_B_ANIM = P_DATA + 'b-animation';

  $.aniElement = function (el) {
    // @todo remove the last at 3.x:
    // If BG, the container itself is the animated element.
    if ($.hasAttr(el, DATA_B_ANIM + ' ' + DATA_ANIM)) {
      return el;
    }

    // Else anything else, will traverse the parent/ closest animated element.
    return $.closest(el, '[' + DATA_B_ANIM + ']') || $.closest(el, '[' + DATA_ANIM + ']');
  };

  /**
   * A simple wrapper to animate anything using animate.css.
   *
   * @param {dBlazy|Array.<Element>|Element} els
   *   The HTML element(s), or dBlazy instance.
   * @param {string|Function} cb
   *   Any custom animation name, fallbacks to [data-animation], or a callback.
   *
   * @return {Object}
   *   This dBlazy object.
   */
  function animate(els, cb) {
    var me = this;

    var chainCallback = function (el) {
      var _set = el.dataset;

      if (!$.isElm(el) || !_set) {
        return me;
      }

      var $el = $(el);
      var animation = _set.animation || _set.bAnimation;

      if ($.isStr(cb)) {
        animation = cb;
      }

      if (!animation) {
        return me;
      }

      var _animated = 'animated';
      var _aniEnd = ANI + 'end.' + animation;
      var _style = el.style;
      var classes = _animated + ' ' + animation;
      var props = [
        ANI,
        ANI + '-duration',
        ANI + '-delay',
        ANI + '-iteration-count'
      ];

      $el.addClass(classes);

      $.each(['Duration', 'Delay', 'IterationCount'], function (key) {
        var _aniKey = ANI + key;
        if (_set && _aniKey in _set) {
          _style[_aniKey] = _set[_aniKey];
        }
      });

      // Supports both BG and regular image.
      var cn = $.closest(el, '.media') || el;
      var bg = $.isBg(el);
      var isBlur = animation === BLUR;
      var an = el;

      // The animated blur is image not this container, except a background.
      if (isBlur && !bg) {
        var img = $.find(cn, 'img:not(.' + B_BLUR + ')');
        an = $.isElm(img) ? img : an;
      }

      function ended(e) {
        $el.addClass('is-b-' + _animated)
          .removeClass(classes)
          .removeAttr(props, P_DATA);

        $.each(props, function (key) {
          _style.removeProperty(key);
        });

        if ($.isFun(cb)) {
          cb(e);
        }

        $.trigger(cn, 'blazy:animated', {
          animation: e
        });
      }

      return $.one(an, _aniEnd, ended, false);
    };

    return $.chain(els, chainCallback);
  }

  $.animate = animate.bind($);
  $.fn.animate = function (animation) {
    return animate(this, animation);
  };

}(dBlazy));
