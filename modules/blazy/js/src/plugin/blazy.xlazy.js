/**
 * @file
 * Provides reusable methods across lazyloaders: Bio and bLazy.
 *
 * @internal
 *   This is an internal part of the Blazy system and should only be used by
 *   blazy-related code in Blazy sub-modules.
 *   It is extending dBlazy as a separate plugin depending on $.viewport.
 *
 * @todo move into js/base folder.
 */

(function ($, _win, _doc) {

  'use strict';

  var DATA = 'data-';
  var SRC = 'src';
  var SRCSET = 'srcset';
  var DATA_SRC = DATA + SRC;
  var DATA_SRCSET = DATA + SRCSET;
  var IMG_SOURCES = [SRCSET, SRC];
  var ER_COUNTED = 0;
  var C_BG = 'b-bg';
  var C_ERROR = 'b-error';
  var C_SUCCESS = 'b-loaded';
  var S_LAZY = '.b-lazy';
  var S_PARENT = '.media';

  $._defaults = {
    error: false,
    offset: 100,
    root: _doc,
    success: false,
    selector: S_LAZY,
    separator: '|',
    container: false,
    containerClass: false,
    errorClass: C_ERROR,
    loadInvisible: false,
    successClass: C_SUCCESS,
    visibleClass: false,
    validateDelay: 25,
    saveViewportOffsetDelay: 50,

    // @todo recheck IO.module. Slick has data-lazy, and irrelevant for Blazy.
    srcset: DATA_SRCSET,
    src: DATA_SRC,
    bgClass: C_BG,

    // IO specifics.
    isMedia: false,
    parent: S_PARENT,
    disconnect: false,
    intersecting: false,
    observing: false,
    resizing: false,
    mobileFirst: false,
    rootMargin: '0px',
    threshold: [0]
  };

  // Returns a success.
  function success(el, status, parent, opts) {
    // Who knows Safari has different interpretation on Function:
    // See https://www.drupal.org/project/blazy/issues/3279316.
    if ($.isFun(opts.success) || $.isObj(opts.success)) {
      opts.success(el, status, parent, opts);
    }

    if (ER_COUNTED > 0) {
      ER_COUNTED--;
    }
    return ER_COUNTED;
  }

  // Returns an error.
  function error(el, status, parent, opts) {
    // Who knows Safari has different interpretation on Function:
    // See https://www.drupal.org/project/blazy/issues/3279316.
    if ($.isFun(opts.error) || $.isObj(opts.error)) {
      opts.error(el, status, parent, opts);
    }

    ER_COUNTED++;
    return ER_COUNTED;
  }

  // Make it private to avoid confusion.
  function loaded(el, status, opts) {
    var cn = $.closest(el, opts.parent) || el;
    var ok = status === $._ok || status === true;
    var successClass = opts.successClass;
    var errorClass = opts.errorClass;
    var isSuccess = 'is-' + successClass;
    var isError = 'is-' + errorClass;

    $.addClass(el, ok ? successClass : errorClass);

    // Adds context for effects: blur, etc. considering BG, or just media.
    $.addClass(cn, ok ? isSuccess : isError);

    if (ok) {
      ER_COUNTED = success(el, status, cn, opts);
      // Native may already remove `data-[SRC|SRCSET]` early, except BG/Video.
      if ($.hasAttr(el, DATA_SRC)) {
        $.removeAttr(el, IMG_SOURCES, DATA);
      }
    }
    else {
      ER_COUNTED = error(el, status, cn, opts);
    }

    return ER_COUNTED;
  }

  /**
   * Checks if image or iframe is decoded/ completely loaded.
   *
   * @private
   *
   * @param {Image|Iframe} el
   *   The Image or Iframe element.
   *
   * @return {bool}
   *   True if the image or iframe is loaded.
   */
  $.isCompleted = function (el) {
    if ($.isElm(el)) {
      if ($.equal(el, 'img')) {
        return $.isDecoded(el);
      }
      if ($.equal(el, 'iframe')) {
        var doc = el.contentDocument || el.contentWindow.document;
        return doc.readyState === 'complete';
      }
    }
    return false;
  };

  $.selector = function (opts, suffix) {
    var selector = opts.selector;
    // @todo recheck, troubled for onresize: + ':not(.' + opts.successClass + ')'.
    if (suffix && $.isBool(suffix)) {
      suffix = ':not(.' + opts.successClass + ')';
    }

    suffix = suffix || '';
    return selector + suffix;
  };

  $.status = function (el, status, opts) {
    // Image decode fails with Responsive image, assumes ok, no side effects.
    return loaded(el, status, opts);
  };

})(dBlazy, this, this.document);
