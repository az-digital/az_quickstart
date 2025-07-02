/**
 * @file
 * Provides Intersection Observer API loader for media.
 *
 * @see https://developer.mozilla.org/en-US/docs/Web/API/Intersection_Observer_API
 * @see https://developers.google.com/web/updates/2016/04/intersectionobserver
 */

/* global define, module */
(function (root, factory) {

  'use strict';

  var ns = 'BioMedia';
  var db = root.dBlazy;
  var bio = root.Bio;

  // Inspired by https://github.com/addyosmani/memoize.js/blob/master/memoize.js
  if (typeof define === 'function' && define.amd) {
    // AMD. Register as an anonymous module.
    define([ns, db, bio], factory);
  }
  else if (typeof exports === 'object') {
    // Node. Does not work with strict CommonJS, but only CommonJS-like
    // environments that support module.exports, like Node.
    module.exports = factory(ns, db, bio);
  }
  else {
    // Browser globals (root is window).
    root[ns] = factory(ns, db, bio);
  }
})(this, function (ns, $, _bio) {

  'use strict';

  /**
   * Private variables.
   */
  var DOC = document;
  var DATA = 'data-';
  var SRC = 'src';
  var SRCSET = 'srcset';
  var C_HTML = 'b-html';
  var DATA_SRC = DATA + SRC;
  var DATA_SRCSET = DATA + SRCSET;
  var DATA_HTML = DATA + C_HTML;
  var DATA_TEXT = 'data:text/plain;base64,';
  var IMG_SOURCES = [SRCSET, SRC];
  var ER_COUNTED = 0;
  var IS_DEFERRED_CALLED = false;
  var FN_MULTIMEDIA = $.multimedia || false;
  var FN;
  var SUPER;

  /**
   * Constructor for BioMedia, Blazy IntersectionObserver for media.
   *
   * @param {object} options
   *   The BioMedia options.
   *
   * @return {object}
   *   The BioMedia instance.
   *
   * @namespace
   */
  function BioMedia(options) {
    var me = _bio.apply($.extend({}, SUPER, $.extend({}, FN, this)), arguments);

    me.name = ns;

    return me;
  }

  // Inherits Bio prototype.
  SUPER = Bio.prototype;
  FN = BioMedia.prototype = Object.create(SUPER);
  FN.constructor = BioMedia;

  // Load a HTML content.
  function loadHtml(cn, opts) {
    if ($.isHtml(cn) && $.hasAttr(cn, DATA_HTML)) {
      var html = $.attr(cn, DATA_HTML);
      var status = false;

      if (html) {
        status = true;
        html = html.replace(DATA_TEXT, '');
        html = atob(html);

        $.append(cn, html);
        $.removeAttr(cn, DATA_HTML);
      }
      ER_COUNTED = $.status(cn, status, opts);
    }
  }

  // Load local media (audio/video).
  function loadLocalMedia(el, status, opts) {
    // Native doesn't support video, fix it.
    $.mapSource(el, SRC, true);
    el.load();

    if (FN_MULTIMEDIA) {
      FN_MULTIMEDIA.init(el);
    }
    return $.status(el, status, opts);
  }

  // Extends Bio prototype.
  FN.lazyLoad = function (el, winData) {
    var me = this;
    var opts = me.options;
    var parent = el.parentNode;
    var isBg = $.isBg(el);
    var isPicture = $.equal(parent, 'picture');
    var isImage = $.equal(el, 'img');
    var isAudio = $.equal(el, 'audio');
    var isVideo = $.equal(el, 'video');
    var isDataset = $.hasAttr(el, DATA_SRC);

    // PICTURE elements.
    if (isPicture) {
      if (isDataset) {
        $.mapSource(el, SRCSET, true);

        // Tiny controller image inside picture element won't get preloaded.
        $.mapAttr(el, SRC, true);
      }

      ER_COUNTED = defer(me, el, true, opts);
    }
    // AUDIO/ VIDEO elements.
    else if (isVideo || isAudio) {
      // Multi contents: BG + real elements, just audio since it has no poster.
      if ($.isBg(parent)) {
        me.loadImage(parent, true, winData);
      }

      ER_COUNTED = loadLocalMedia(el, true, opts);
    }
    else {
      // IMG or DIV/ block elements got preloaded for better UX with loading.
      // Native doesn't support DIV, fix it.
      if (isImage || isBg) {
        me.loadImage(el, isBg, winData);

        // Double lazy load elements.
        if (isBg && $.isHtml(el)) {
          loadHtml(el, opts);
        }
      }
      else {
        // IFRAME elements, etc.
        if ($.hasAttr(el, SRC)) {
          if ($.attr(el, DATA_SRC)) {
            $.mapAttr(el, SRC, true);
          }

          ER_COUNTED = defer(me, el, true, opts);
        }
        // HTML elements.
        else {
          loadHtml(el, opts);
        }
      }
    }

    me.erCount = ER_COUNTED;
  };

  // Compatibility between Native and old data-[SRC|SRSET] approaches.
  FN.loadImage = function (el, isBg, winData) {
    var me = this;
    var opts = me.options;
    var img = new Image();
    var isResimage = $.hasAttr(el, SRCSET);
    var isDataset = $.hasAttr(el, DATA_SRC);
    var currSrc = isDataset ? DATA_SRC : SRC;
    var currSrcset = isDataset ? DATA_SRCSET : SRCSET;

    var preload = function () {
      if ('decode' in img) {
        img.decoding = 'async';
      }

      if (isBg && $.isFun($.bgUrl)) {
        img.src = $.bgUrl(el, winData);
      }
      else {
        if (isDataset) {
          $.mapAttr(el, IMG_SOURCES, false);
        }

        img.src = $.attr(el, currSrc);
      }

      if (isResimage) {
        img.srcset = $.attr(el, currSrcset);
      }
    };

    var load = function (el, ok) {
      if (isBg && $.isFun($.bg)) {
        $.bg(el, winData);
        ER_COUNTED = $.status(el, ok, opts);
      }
      else {
        ER_COUNTED = defer(me, el, ok, opts);
      }
    };

    preload();

    // Preload `img` to have correct event handlers.
    $.decode(img)
      .then(function () {
        load(el, true);
      })
      .catch(function () {
        load(el, isResimage);

        // Allows to re-observe.
        if (!isResimage) {
          el.bhit = false;
        }
      });
  };

  FN.resizing = function (el, winData) {
    var me = this;
    var isBg = $.isBg(el, me.options);

    // Fix dynamic multi-breakpoint background to avoid loaders workarounds.
    if (isBg) {
      me.loadImage(el, isBg, winData);
    }
  };

  // Applies the defer loading as per https://drupal.org/node/3120696.
  // This replaces all loading=defer into original loading=lazy once the first
  // row of images is found to solve the hard-coded threshold 8000px problems.
  // Basically telling browsers to delay lazyloading until one is nearly
  // visible, not immediately lazyloaded at 8000px down the viewport which makes
  // expectations useless such as for blurs, loading animation, interactive
  // elements on the exact moment of loading/ visible event, etc. If you hate
  // cool kids or fancy stuffs, do not choose `defer` option, no fuss.
  function defer(me, el, status, opts) {
    if (!IS_DEFERRED_CALLED) {
      var cb = function (elm) {
        $.attr(elm, 'loading', 'lazy');
      };
      natively(me, 'defer', cb);
      IS_DEFERRED_CALLED = true;
    }

    return $.status(el, status, opts);
  }

  // Since bLazy, which has no supports for Native, is a fallback, it is easier
  // now to work with Native. No more need to hook into load event separately,
  // no deferred invocation till one loaded, no hijacking.
  // No more fights under a single source of truth. It is a total swap.
  // As mentioned in the doc, Native at least Chrome starts loading images
  // 8000px, hardcoded, before they are entering the viewport. Meaning harsh,
  // makes fancy stuffs like blur useless. And bad because blur filter
  // is very expensive, and when they are triggered before visible, will block.
  // @see /admin/help/blazy_ui# NATIVE LAZY LOADING
  // With bIO as the main loader, the game changed, quoted from:
  // https://developer.mozilla.org/en-US/docs/Learn/HTML/Howto/Author_fast-loading_HTML_pages
  // "Note that lazily-loaded images may not be available when the load event is
  // fired. You can determine if a given image is loaded by checking to see if
  // the value of its Boolean complete property is true."
  // Old bLazy relies on onload, meaning too early loaded decision for Native,
  // the reason for our previous deferred invocation, not decoding like what bIO
  // did which is more precise as suggested by the quote.
  // Assumed, untested, fine with combo IO + decoding checks before blur spits.
  // Shortly we are in the right direction to cope with Native vs. data-[SRC].
  // @done recheck IF wrong so to put back https://drupal.org/node/3120696.
  // Almost not wrong, no blur nor `b-loaded` were added till intersected, but
  // added a new `loading:defer` to solve 8000px threshold.
  function natively(me, key, cb) {
    var opts = me.options;

    if (!$.isNativeLazy) {
      return [];
    }

    // The `a` keyword found in `auto, eager, lazy`, not `defer`.
    key = key || 'a';
    var dataset = $.selector(opts, '[data-src][loading*="' + key + '"]:not(.b-blur)');
    var els = $.findAll(DOC, dataset);

    // We are here if `No JavaScript` is being disabled.
    if (els.length) {
      $.each(els, function (el) {
        // Reset attributes, and let supportive browsers lazy load natively.
        $.mapAttr(el, ['srcset', 'src'], true);

        // Also supports PICTURE which contains SOURCEs. Excluding VIDEO.
        $.mapSource(el, false, true, false);

        // Executes a function if any.
        if ($.isFun(cb)) {
          cb(el);
        }
      });
    }
    return els;
  }

  // https://caniuse.com/dom-manip-convenience
  // https://developer.mozilla.org/en-US/docs/Web/API/Element/replaceWith
  function webp(me) {
    if ($.webp.isSupported()) {
      return;
    }

    var sel = function (prefix) {
      prefix = prefix || '';
      // IE9 err: :not(picture img)
      return $.selector(me.options, '[' + prefix + 'srcset*=".webp"]');
    };

    var elms = $.findAll(DOC, sel());
    if (!elms.length) {
      elms = $.findAll(DOC, sel('data-'));
    }

    if (elms.length) {
      $.webp.run(elms);
    }
  }

  FN.prepare = function () {
    var me = this;

    // @todo lock it back once AJAX-loaded contents fixed.
    natively(me);

    // Runs after native set to minimize works.
    if ($.webp) {
      webp(me);
    }
  };

  return BioMedia;

});
