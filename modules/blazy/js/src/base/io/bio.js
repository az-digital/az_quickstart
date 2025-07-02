/**
 * @file
 * Provides Intersection Observer API loader.
 *
 * This file is not loaded when `No JavaScript` enabled, unless exceptions met.
 *
 * @see https://developer.mozilla.org/en-US/docs/Web/API/Intersection_Observer_API
 * @see https://developers.google.com/web/updates/2016/04/intersectionobserver
 * @see https://www.npmjs.com/package/intersection-observer
 * @see https://github.com/w3c/IntersectionObserver
 * @see https://caniuse.com/?search=visualViewport
 * @todo https://developer.mozilla.org/en-US/docs/Web/API/Visual_Viewport_API
 * @todo remove traces of fallback to be taken care of by old bLazy fork.
 */

/* global define, module */
(function (root, factory) {

  'use strict';

  var ns = 'Bio';
  var db = root.dBlazy;

  // Inspired by https://github.com/addyosmani/memoize.js/blob/master/memoize.js
  if (db.isAmd) {
    // AMD. Register as an anonymous module.
    define([ns, db, root], factory);
  }
  else if (typeof exports === 'object') {
    // Node. Does not work with strict CommonJS, but only CommonJS-like
    // environments that support module.exports, like Node.
    module.exports = factory(ns, db, root);
  }
  else {
    // Browser globals (root is window).
    root[ns] = factory(ns, db, root);
  }

}((this || module || {}), function (ns, $, _win) {

  'use strict';

  if ($.isAmd) {
    _win = window;
  }

  /**
   * Private variables.
   */
  var DOC = _win.document;
  var ROOT = DOC;
  var NICK = 'bio';
  var WINDATA = {};
  var BIOTICK = 0;
  var REVTICK = 0;
  var HITTICK = 0;
  var OPTS = {};
  var C_BG = 'b-bg';
  var C_IS_VISIBLE = 'is-b-visible';
  // @todo remove the first at 3.x:
  var E_INTERSECTING = NICK + '.intersecting ' + NICK + ':intersecting';
  var S_PARENT = '.media';
  var ADDCLASS = 'addClass';
  var REMOVECLASS = 'removeClass';
  var INITIALIZED = false;
  var IS_RESIZING = false;
  var VALIDATE_DELAY = 25;
  var V_WW = 0;
  var FN_OBSERVER = $.observer;
  var FN_VIEWPORT = $.viewport;
  var FN;

  /**
   * Constructor for Bio, Blazy IntersectionObserver.
   *
   * @param {object} options
   *   The Bio options.
   *
   * @return {Bio}
   *   The Bio instance.
   *
   * @namespace
   */
  function Bio(options) {
    var me = $.extend({}, FN, this);

    me.name = ns;
    me.options = OPTS = $.extend({}, $._defaults, options || {});

    C_BG = OPTS.bgClass || C_BG;
    VALIDATE_DELAY = OPTS.validateDelay || VALIDATE_DELAY;
    S_PARENT = OPTS.parent || S_PARENT;
    ROOT = OPTS.root || ROOT;

    // DOM ready fix. Ain't a culprit.
    setTimeout(function () {
      me.reinit();
    });

    return me;
  }

  function intersecting(el, revalidate) {
    var me = this;
    var opts = me.options;
    var sel = opts.selector;
    var count = me.count;
    var io = me.ioObserver;
    var watching = opts.visibleClass || revalidate || false;

    // Only destroy if no use for is-b-visible class.
    if (BIOTICK === count - 1) {
      $.trigger(_win, NICK + ':done', {
        options: opts
      });

      if (!watching) {
        me.destroyQuietly();
      }
    }

    // Unlike ResizeObserver/ infinite pager, IntersectionObserver is done.
    if (io) {
      // We are here with arbitrary observed elements for hidden children.
      // See https://drupal.org/node/3279316.
      var hidden = FN_OBSERVER.hiddenChild(el, sel);
      if (hidden) {
        el = hidden;
      }

      if (me.isLoaded(el) && !revalidate) {
        // Unless watching.
        if (opts.isMedia && !watching) {
          io.unobserve(el);
        }

        // Count the loaded ones, watching or not.
        BIOTICK++;
      }
    }

    // Image may take time to load after being hit, and it may be intersected
    // several times till marked loaded. Ensures it is hit once regardless
    // of being loaded, or not. No real issue with normal images on the page,
    // until having VIS alike which may spit out new images on AJAX request.
    if (!el.bhit || revalidate) {
      // Makes sure to have media loaded beforehand.
      me.lazyLoad(el, WINDATA);

      // If not extending/ overriding, at least provide the option.
      if ($.isFun(opts.intersecting)) {
        opts.intersecting(el, opts);
      }

      // If not extending/ overriding, also allows to listen to.
      $.trigger(el, E_INTERSECTING, {
        options: opts
      });

      HITTICK++;

      // Marks it hit/ requested, not necessarily loaded.
      el.bhit = true;
    }
  }

  // This function is called by two observers: IO and RO.
  function interact(entries) {
    var me = this;
    var opts = me.options;
    var vp = FN_VIEWPORT.vp || {};
    var ww = FN_VIEWPORT.ww || 0;
    var entry = entries[0];
    var isBlur = $.isBlur(entry);
    var isResizing = FN_VIEWPORT.isResized(me, entry);
    var visibleClass = opts.visibleClass;
    var forAnim = $.isBool(visibleClass) && visibleClass;

    // RO is another abserver.
    if (isResizing) {
      WINDATA = FN_VIEWPORT.update(opts);

      FN_VIEWPORT.onresizing(me, WINDATA);

      if (V_WW > 0) {
        var details = {
          winData: WINDATA,
          entries: me.elms,
          currentWidth: ww,
          oldWidth: V_WW,
          enlarged: ww > V_WW
        };

        // Ensures only before settled, or if any different from previous size.
        if (V_WW !== ww) {
          $.trigger(_win, NICK + ':resizing', details);
        }
        else {
          $.trigger(_win, NICK + ':resized', details);
        }
        me.resizeTick++;
      }
    }
    else {
      // Stop IO watching if destroyed, unless a visibleClass is defined:
      // Animation, BG color on being visible, infinite pager, or lazyloaded
      // blocks. Infinite pager is a valid sample since it has a single link
      // to observe for infinite click events. Unobserve should be left to them.
      if (me.destroyed && !visibleClass) {
        return;
      }
    }

    // Load each on entering viewport.
    $.each(entries, function (e) {
      var target = e.target;
      var el = target || e;
      var resized = FN_VIEWPORT.isResized(me, e);
      var visible = FN_VIEWPORT.isVisible(e, vp);
      var cn = $.closest(el, S_PARENT) || el;

      isBlur = isBlur && !$.hasClass(cn, 'is-b-animated');

      // The element is being intersected.
      if (visible) {
        // Triggers loading indicator animation before being loaded.
        if (!me.isLoaded(el)) {
          $[ADDCLASS](cn, C_IS_VISIBLE);
        }

        intersecting.call(me, el);

        // The intersecting does the loading, the check must be afterwards.
        // To make efficient blur filter via CSS, etc. Blur filter is expensive.
        if (me.isLoaded(el)) {
          if (isBlur || forAnim) {
            $[ADDCLASS](cn, C_IS_VISIBLE);
          }

          if (!forAnim) {
            setTimeout(function () {
              $[REMOVECLASS](cn, C_IS_VISIBLE);
            }, 601);
          }
        }
      }
      else {
        $[REMOVECLASS](cn, C_IS_VISIBLE);
      }

      // For different toggle purposes regardless being loaded, or not.
      // Avoid using the reserved `is-b-visible`, use `is-b-inview`, etc.
      if (visibleClass && $.isStr(visibleClass)) {
        $[visible ? ADDCLASS : REMOVECLASS](cn, visibleClass);
      }

      // The element is being resized.
      IS_RESIZING = resized && V_WW > 0;
      if (IS_RESIZING && !isBlur) {
        // Ensures only before settled, or if any different from previous size.
        if (V_WW !== ww) {
          me.resizing(el, WINDATA);
        }
      }

      // Provides option such as to animate bg or elements regardless position.
      // See gridstack.parallax.js.
      if ($.isFun(opts.observing)) {
        opts.observing(e, visible, opts);
      }
    });

    V_WW = ww;
  }

  // Initializes the IO with fallback to old bLazy.
  function init(me) {
    // Swap data-[SRC|SRCSET] for non-js version once, if not choosing Native.
    // Native lazy markup is triggered by enabling `No JavaScript` lazy option.
    me.prepare();

    var elms = $.findAll(ROOT, $.selector(me.options));
    me.elms = elms;
    me.count = elms.length;
    me._raf = [];
    me._queue = [];
    me.withIo = true;

    // Observe elements. Old blazy as fallback is also initialized here.
    // IO will unobserve, or disconnect. Old bLazy will self destroy.
    me.observe(true);
  }

  // Cache our prototype.
  FN = Bio.prototype;
  FN.constructor = Bio;

  // Prepare prototype to interchange with Blazy as fallback.
  FN.count = 0;
  FN.erCount = 0;
  FN.resizeTick = 0;
  FN.destroyed = false;
  FN.options = {};
  FN.lazyLoad = function (el, winData) {};
  FN.loadImage = function (el, isBg, winData) {};
  FN.resizing = function (el, winData) {};
  FN.prepare = function () {};
  FN.windowData = function () {
    return $.isUnd(WINDATA.vp) ? FN_VIEWPORT.windowData(this.options, true) : WINDATA;
  };

  // BC for interchanging with bLazy.
  // @todo merge with bLazy::load.
  FN.load = function (elms, revalidate, opts) {
    var me = this;

    elms = elms && $.toArray(elms);

    // @todo remove once infinite pager regression fixed properly like before.
    if (!$.isUnd(opts)) {
      me.options = $.extend({}, me.options, opts || {});
    }

    // Re-use old existing loadInvisible to revalidate hidden elements.
    revalidate = revalidate || me.options.loadInvisible;

    // Manually load elements regardless of being disconnected, or not, relevant
    // for Slick slidesToShow > 1 which rebuilds clones of unloaded elements.
    $.each(elms, function (el) {
      if (me.isValid(el) || ($.isElm(el) && revalidate)) {
        intersecting.call(me, el, revalidate);
      }
    });
  };

  FN.isLoaded = function (el) {
    return $.hasClass(el, this.options.successClass);
  };

  FN.isValid = function (el) {
    return $.isElm(el) && !this.isLoaded(el);
  };

  FN.revalidate = function (force) {
    var me = this;

    // Prevents from too many revalidations unless needed.
    if ((force === true || me.count !== HITTICK) && (REVTICK < HITTICK)) {
      var elms = me.elms = $.findAll(ROOT, $.selector(me.options));

      if (elms.length) {
        me.observe(true);

        REVTICK++;
      }
    }
  };

  FN.destroyQuietly = function (force) {
    var me = this;
    var opts = me.options;

    // Infinite pager like IO wants to keep monitoring infinite contents.
    // Multi-breakpoint BG/ ratio may want to update during resizing.
    if (!me.destroyed && (force || $.isUnd(Drupal.io))) {
      var el = $.find(DOC, $.selector(opts, ':not(.' + opts.successClass + ')'));

      if (!$.isElm(el)) {
        me.destroy(force);
      }
    }
  };

  FN.destroy = function (force) {
    var me = this;
    var opts = me.options;
    var io = me.ioObserver;
    var done = (BIOTICK === me.count - 1);
    var disconnect = done && opts.disconnect;

    // Do not disconnect if any error found.
    if (me.destroyed || (me.erCounted > 0 && !force)) {
      return;
    }

    // Disconnect when all entries are loaded, if so configured.
    if (disconnect || force) {
      if (io) {
        io.disconnect();
      }

      FN_OBSERVER.unload();
      me.count = 0;
      me.elms = [];
      me.ioObserver = null;
      me.destroyed = true;
    }
  };

  FN.observe = function (reobserve) {
    var me = this;
    var elms = me.elms;

    reobserve = reobserve || me.destroyed;

    // Observe as IO, or initialize old bLazy as fallback.
    if (!INITIALIZED || reobserve) {
      WINDATA = FN_OBSERVER.init(me, interact, elms, true);

      me.destroyed = false;

      FN_OBSERVER.observe();

      INITIALIZED = true;
    }
  };

  FN.reinit = function () {
    var me = this;
    me.destroyed = true;
    BIOTICK = 0;

    init(me);
  };

  return Bio;

}));
