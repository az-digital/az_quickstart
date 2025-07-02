/**
 * @file
 * Provides compat methods between Native and lazyload script.
 *
 * This file is not loaded if all below are not enabled.
 *
 * Mostly to fix for lost module features due to lazyload script being ditched:
 *   - Blur or animation in general with animate.css.
 *   - Multiple-breakpoint CSS background (DIV).
 *   - Multiple-breakpoint dynamic, or named Fluid, aspect ratio.
 *   - Local video.
 *   - Extra features: sub-module requirements. If using Slick/ Splide, etc., be
 *     sure to disable loading their loaders globally at their UIs as needed.
 */

(function ($, Drupal, _win) {

  'use strict';

  var ID = 'blazy';
  var COMPAT = 'compat';
  var C_IS_ANIMATED = 'is-b-animated';
  var DATA = 'data-';
  var DATA_RATIOS = DATA + 'b-ratios';
  var DATA_RATIO = DATA + 'b-ratio';
  var E_RESIZING = 'bio:resizing.' + COMPAT;
  var S_PICTURE = 'picture';
  var S_RATIO = '.media--ratio';
  var OPTS = {};
  var V_WINDATA = {};

  /**
   * Blazy public compat methods.
   *
   * @namespace
   */
  Drupal.blazy = $.extend(Drupal.blazy || {}, {

    clearCompat: function (el) {
      var me = this;
      var old = $.isBg(el) && (me.isBlazy() || $.ie);

      // Only animate when the image is fully loaded, else nonsense.
      me.pad(el, animate, old ? 50 : 0);
    },

    checkResize: function (items, cb, root, onDone) {
      var me = this;
      var bio = me.init;
      var check = function (e) {
        var details = e && e.detail ? e.detail : {};

        V_WINDATA = details.winData || me.windowData();

        me.resizeTick = bio && bio.resizeTick || 0;

        if ($.isFun(cb)) {
          $.each(items, function (entry, i) {
            var el = entry.target || entry;

            return cb.call(me, el, i, true);
          });
        }
      };

      // Already throttled for oldies, or RO/RAF for modern browsers.
      $.on(E_RESIZING, check);

      // When images are loaded, Flexbox or Native Grid as Masonry might need
      // info about the loaded image dimensions to calculate gaps or positions.
      if (onDone && $.isFun(onDone)) {
        me.rebind(root, onDone, me.roObserver);
      }

      me.destroyed = false;
      return V_WINDATA;
    }
  });

  // Private non-reusable functions.
  /**
   * Callback function to animate blur, or any animated, element, if any.
   *
   * @param {Element} el
   *   The DIV or image element.
   */
  function animate(el) {
    // Blur, animate.css, for CSS background, picture, image, media.
    var an = $.aniElement && $.aniElement(el);

    // Animate if any.
    if ($.animate && $.isElm(an) && !$.hasClass(an, C_IS_ANIMATED)) {
      $.animate(an);
    }
  }

  /**
   * Updates the dynamic multi-breakpoint aspect ratio: bg, picture or image.
   *
   * Even Native needs help since browsers do not auto-update dynamic ratio.
   *
   * This only applies to Responsive images with aspect ratio fluid.
   * Static ratio (media--ratio--169, etc.) is ignored and uses CSS instead.
   * The dimensions here are pre-determined server-side per image styles.
   * Called during window.resize and window.onload to have a frame (setup
   * dimensions) to minimize reflows. The real frame will be set after the
   * image.onload/ decoded moment at blazy.drupal.js ::pad() method for more
   * precise dimensions based on image natural dimensions, not server-side ones.
   *
   * @param {Element} cn
   *   The .media--ratio[--fluid] container HTML element.
   * @param {int} i
   *   The element index.
   * @param {bool} isResized
   *   If the resize event is triggered.
   *
   * @todo this should be at bio.js, but bLazy has no support which prevents it.
   * Unless made generic for a ping-pong.
   */
  function updateRatio(cn, i, isResized) {
    var data;
    var isPicture;
    var pad;
    var ratios;
    var root;
    cn = cn.target || cn;

    // The actual third argument is object collections, unless being resized.
    isResized = $.isBool(isResized) ? isResized : false;

    if (!$.isElm(cn)) {
      return;
    }

    ratios = $.parse($.attr(cn, DATA_RATIOS));

    // Bail out if a static/ non-fluid aspect ratio.
    if ($.isEmpty(ratios)) {
      fallbackRatio(cn);
      return;
    }

    // For picture, this is more a dummy space till the image is downloaded.
    isPicture = $.isElm($.find(cn, S_PICTURE)) && isResized;
    data = $.extend(V_WINDATA, {
      up: isPicture
    });

    // Provides marker for grouping between multiple instances.
    // Blazy container (via formatter or Views style) is not always there.
    root = $.closest(cn, '.' + ID);
    cn.dblazy = $.isElm(root) && root.dblazy;

    pad = $.activeWidth(ratios, data);
    if (pad && !$.isUnd(pad)) {
      cn.style.paddingBottom = pad + '%';
    }
  }

  // Only rewrites if the style is indeed stripped out, and not set.
  // View rewrite result stripped out style attribute required by fluid ratio.
  function fallbackRatio(cn) {
    var value = $.attr(cn, DATA_RATIO);

    if (!$.hasAttr(cn, 'style') && value) {
      cn.style.paddingBottom = value + '%';
    }
  }

  /**
   * Resize Fluid aspect ratio.
   *
   * @todo this should be at bio.js, but bLazy has no support which prevents it.
   */
  function resize() {
    var me = this;
    var doc = me.context;
    var els = $.findAll(doc, S_RATIO);

    // Update multi-breakpoint fluid aspect ratio, if any.
    if (els.length) {
      $.each(els, updateRatio.bind(me));
      me.checkResize(els, updateRatio, doc);
    }
  }

  /**
   * Processes DOM observations.
   */
  function process() {
    var me = this;

    // Mount extensions.
    me.mount(true);
    OPTS = me.options;

    // ::init will/not be overridden by blazy/load, no problem since 2.6.
    if ($.isNull(me.init)) {
      me.init = me.run(OPTS);
    }

    resize.call(me);
  }

  /**
   * Attaches blazy behavior to HTML elements.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.blazyCompat = {
    attach: function (context) {

      var me = Drupal.blazy;
      me.context = $.context(context);

      // No bind without extra arguments, call me.
      $.once(process.call(me));

    }
  };

}(dBlazy, Drupal, this));
