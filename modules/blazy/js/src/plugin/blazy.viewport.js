/**
 * @file
 * Provides reusable methods across lazyloaders: Bio and bLazy.
 *
 * @internal
 *   This is an internal part of the Blazy system and should only be used by
 *   blazy-related code in Blazy sub-modules.
 *   It is extending dBlazy as a separate plugin.
 */

(function ($, _win, _doc) {

  'use strict';

  function real(el) {
    return el ? (el.target || el) : null;
  }

  /**
   * Returns viewport info.
   *
   * @private
   *
   * @param {Element} offset
   *   The offset defined via UI normally related to header fixed position.
   *
   * @return {Object}
   *   Returns the window viewport info.
   */
  function info(offset) {
    offset = offset || 0;
    var size = $.windowSize();

    return {
      top: 0 - offset,
      left: 0 - offset,
      bottom: size.height + offset,
      right: size.width + offset,
      width: size.width,
      height: size.height
    };
  }

  /**
   * Returns element visibility for oldies.
   *
   * @private
   *
   * @param {Object|Element} el
   *   The bounding rect object, or HTML element to test.
   * @param {Object} vp
   *   The window viewport.
   *
   * @return {bool}
   *   Returns true if visible.
   */
  function isVisible(el, vp) {
    var rect = $.isElm(el) ? $.rect(el) : el;

    if (!vp) {
      vp = info();
    }

    return rect.right >= vp.left &&
      rect.bottom >= vp.top &&
      rect.left <= vp.right &&
      rect.top <= vp.bottom;
  }

  // See https://developer.mozilla.org/en-US/docs/Web/API/HTMLElement.offsetParent
  function isHidden(e) {
    var el = real(e);
    return el && $.isNull(el.offsetParent);
  }

  function visibleParent(e) {
    var el = real(e);
    var cn = $.parent(el);
    var out = cn;

    while (cn) {
      if ($.isElm(cn) && !isHidden(cn)) {
        out = cn;
        break;
      }

      cn = cn.parentElement || cn.parentNode;
    }
    return out;
  }

  $.viewport = {
    vp: {
      top: 0,
      right: 0,
      bottom: 0,
      left: 0
    },

    ww: 0,
    opts: {},

    init: function (opts) {
      var me = this;
      me.opts = opts || {};

      me.vp = info(me.opts.offset);

      return me.vp;
    },

    isResized: function (scope, e) {
      if (!e || !('contentRect' in e)) {
        return false;
      }
      return (!!e.contentRect || !!scope.resizeTrigger || false);
    },

    isHidden: isHidden,

    isVisible: function (e, vp) {
      if (!e) {
        return false;
      }

      var el = real(e);
      return $.isIo && 'isIntersecting' in e
        ? (e.isIntersecting || e.intersectionRatio > 0)
        : isVisible(el, vp);
    },

    onresizing: function (scope, winData) {
      var elms = scope.elms;
      var opts = scope.options || {};

      // Provides a way to fix dynamic aspect ratio, etc.
      if ($.isFun(opts.resizing)) {
        opts.resizing(scope, elms, winData);
      }
    },

    update: function (opts) {
      opts = opts || me.opts;

      var me = this;
      var offset = opts.offset || 0;
      var html = _doc.documentElement;

      me.vp.bottom = (_win.innerHeight || html.clientHeight) + offset;
      me.vp.right = (_win.innerWidth || html.clientWidth) + offset;

      return me.windowData(opts);
    },

    visibleParent: visibleParent,

    // Must be called after init and update.
    windowData: function (opts, init) {
      opts = opts || me.opts;

      var me = this;
      var offset = opts.offset || 0;
      var mobileFirst = opts.mobileFirst || false;

      if (init) {
        me.init(opts);
      }

      me.ww = me.vp.right - offset;

      return {
        vp: me.vp,
        ww: me.ww,
        up: mobileFirst
      };
    }
  };

})(dBlazy, this, this.document);
