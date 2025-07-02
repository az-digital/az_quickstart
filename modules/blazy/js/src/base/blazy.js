/**
 * @file
 * hey, [be]Lazy.js - v1.8.2 - 2016.10.25
 * A fast, small and dependency free lazy load script (https://github.com/dinbror/blazy)
 * (c) Bjoern Klinggaard - @bklinggaard - https://dinbror.dk/blazy
 *
 * A fork of blazy by @gausarts - https://drupal.org/u/gausarts
 * Thanks and kudos to @dinbror for the great work, gotta move forward :)
 * List of Changes:
 *   - Made as a fallback for IO and Native lazy, taken care of by Bio.js.
 *   - Removed IE7 hacks for a minimum of IE9, while core minimum is IE11.
 *   - Simplified event (un)registrations.
 *   - Fixed for multi-breakpoint BG and aspect ratio.
 *   - Moved private stuff to be re-usable for all Blazy codebase into dBlazy.
 *   - Dependent on dBlazy for shared methods at entire Blazy ecosystem.
 *
 * @todo move more re-usable functions out for re-use, or remove more dups.
 */

/* global define, module */
(function (root, blazy) {

  'use strict';

  var ns = 'Blazy';
  var db = root.dBlazy;

  if (db.isAmd) {
    // AMD. Register bLazy as an anonymous module.
    define([ns, db, root], blazy);
  }
  else if (typeof exports === 'object') {
    // Node. Does not work with strict CommonJS, but
    // only CommonJS-like environments that support module.exports, like Node.
    module.exports = blazy(ns, db, root);
  }
  else {
    // Browser globals. Register bLazy on window.
    root.Blazy = blazy(ns, db, root);
  }
})(this, function (ns, $, _win) {

  'use strict';

  // Private vars.
  var DOC = _win.document;
  var SOURCE;
  var IS_RETINA;
  var ATTR_SRC = 'src';
  var ATTR_SRCSET = 'srcset';
  var OPTS = {};
  var VP = {};
  var WINDATA = {};
  var FN_VIEWPORT = $.viewport;

  /**
   * Constructor for Blazy.
   *
   * @param {object} options
   *   The Blazy options.
   *
   * @return {Blazy}
   *   The Blazy instance.
   *
   * @namespace
   */
  return function Blazy(options) {
    var me = this;

    me.name = ns;
    me.options = OPTS = $.extend($._defaults, options || {});
    me.options.container = OPTS.containerClass ? $.findAll(DOC, OPTS.containerClass) : false;
    me.destroyed = true;
    var util = me._util = {};

    OPTS = me.options;
    SOURCE = OPTS.src || 'data-src';
    IS_RETINA = $.pixelRatio() > 1;

    FN_VIEWPORT.init(OPTS);

    // Public functions.
    me.windowData = function () {
      return $.isUnd(WINDATA.vp) ? FN_VIEWPORT.windowData(OPTS, true) : WINDATA;
    };

    me.revalidate = function () {
      init(me);
    };

    // @todo merge with Bio::load.
    me.load = function (elements, force) {
      var opts = me.options;
      if (elements && $.isUnd(elements.length)) {
        loadElement(elements, force, opts);
      }
      else {
        $.each(elements, function (element) {
          loadElement(element, force, opts);
        });
      }
    };

    me.destroy = function () {
      var util = me._util;
      if (OPTS.container) {
        $.each(OPTS.container, function (object) {
          $.off(object, 'scroll.' + ns, util.validateT);
        });
      }

      $.off(_win, 'scroll.' + ns, util.validateT);
      $.off(_win, 'resize.' + ns, util.validateT);
      $.off(_win, 'resize.' + ns, util.saveViewportOffsetT);

      me.count = 0;
      me.elms.length = 0;
      me.destroyed = true;
    };

    // Throttle, ensures that we don't call the functions too often.
    util.validateT = $.throttle(function () {
      validate(me);
    }, OPTS.validateDelay, me);

    util.saveViewportOffsetT = $.throttle(function () {
      saveViewportOffset(OPTS);

      FN_VIEWPORT.onresizing(me, WINDATA);
    }, OPTS.saveViewportOffsetDelay, me);

    saveViewportOffset(OPTS);

    // "dom ready" fix, start lazy load.
    setTimeout(function () {
      init(me);
    });

    return me;
  };

  // Private helper functions
  function init(me) {
    var util = me._util;

    // First we create an array of elements to lazy load.
    me.elms = $.findAll(OPTS.root || DOC, $.selector(OPTS));
    me.count = me.elms.length;

    // Then we bind resize and scroll events if not already binded.
    if (me.destroyed) {
      me.destroyed = false;
      if (OPTS.container) {
        $.each(OPTS.container, function (object) {
          $.on(object, 'scroll.' + ns, util.validateT);
        });
      }
      $.on(_win, 'resize.' + ns, util.saveViewportOffsetT);
      $.on(_win, 'resize.' + ns, util.validateT);
      $.on(_win, 'scroll.' + ns, util.validateT);
    }

    // And finally, we start to lazy load.
    validate(me);
  }

  function validate(me) {
    for (var i = 0; i < me.count; i++) {
      var element = me.elms[i];

      if (elementInView(element, me.options) ||
        $.hasClass(element, me.options.successClass)) {

        me.load(element);
        me.elms.splice(i, 1);
        me.count--;
        i--;
      }
    }
    if (me.count === 0) {
      me.destroy();
    }
  }

  function elementInView(ele, options) {
    var rect = $.rect(ele);

    if (options.container) {
      // Is element inside a container?
      var elementContainer = $.closest(ele, options.containerClass);
      if (elementContainer) {
        var containerRect = $.rect(elementContainer);
        // Is container in view?
        if (FN_VIEWPORT.isVisible(containerRect, VP)) {
          var top = containerRect.top - options.offset;
          var right = containerRect.right + options.offset;
          var bottom = containerRect.bottom + options.offset;
          var left = containerRect.left - options.offset;

          var containerRectWithOffset = {
            top: top > VP.top ? top : VP.top,
            right: right < VP.right ? right : VP.right,
            bottom: bottom < VP.bottom ? bottom : VP.bottom,
            left: left > VP.left ? left : VP.left
          };

          // Is element in view of container?
          return FN_VIEWPORT.isVisible(rect, containerRectWithOffset);
        }
        else {
          return false;
        }
      }
    }
    return FN_VIEWPORT.isVisible(rect, VP);
  }

  // @todo merge with Bio.js.
  function loadElement(ele, force, options) {
    // If element is visible, not loaded, hidden or forced.
    if (!$.hasClass(ele, options.successClass) &&
      (force || options.loadInvisible ||
        (ele.offsetWidth > 0 && ele.offsetHeight > 0))) {

      // Fallback to default 'data-src'.
      var dataSrc = $.attr(ele, SOURCE) || $.attr(ele, options.src);
      if (dataSrc) {
        // @todo remove IS_RETINA, not implemented for Responsive image instead.
        var dataSrcSplitted = dataSrc.split(options.separator);
        var src = dataSrcSplitted[IS_RETINA && dataSrcSplitted.length > 1 ? 1 : 0];
        var srcset = $.attr(ele, options.srcset);
        var isBg = $.isBg(ele, options);
        var isImage = $.equal(ele, 'img');
        var parent = ele.parentNode;
        var isPicture = $.equal(parent, 'picture');
        var ie = $.ie(ele);
        var fixRatio = ie && ele.currentStyle['object-fit'];

        // Image or background image.
        if (isImage || isBg) {
          var img = new Image();
          // Using EventListener instead of onerror and onload
          // due to bug introduced in chrome v50.
          // @see https://productforums.google.com/forum/#!topic/chrome/p51Lk7vnP2o
          var onErrorHandler = function () {
            $.status(ele, false, options);
          };
          var onLoadHandler = function () {
            // Is element an image
            if (isImage) {
              if (!isPicture) {
                handleSources(ele, src, srcset, fixRatio);

                if (fixRatio) {
                  ele.style.backgroundImage = 'url("' + src + '")';
                }
              }
            }
            else {
              // Or background-image.
              fixRatio = ie;
              if ($.isFun($.bgUrl)) {
                src = $.bgUrl(ele, WINDATA);

                $.bg(ele, WINDATA);
              }
              else {
                ele.style.backgroundImage = 'url("' + src + '")';
              }
            }

            itemLoaded(ele, options);
          };

          // Picture element.
          if (isPicture) {
            img = ele;
            // Image tag inside picture element wont get preloaded.
            $.each(parent.getElementsByTagName('source'), function (source) {
              handleSource(source, ATTR_SRCSET, options.srcset);
            });
          }

          // Uses one method ala jQuery for auto-unregistration.
          $.one(img, 'error.' + ns, onErrorHandler);
          $.one(img, 'load.' + ns, onLoadHandler);

          // Preload.
          handleSources(img, src, srcset, fixRatio);
        }
        else {
          // An item with src like iframe, unity games, simpel video etc.
          ele.src = src;
          itemLoaded(ele, options);
        }
      }
      else {
        // video with child source
        if ($.equal(ele, 'video')) {
          $.each(ele.getElementsByTagName('source'), function (source) {
            handleSource(source, ATTR_SRC, options.src);
          });

          ele.load();
          itemLoaded(ele, options);
        }
        else {
          if (options.error) {
            options.error(ele, 'missing');
          }
          $.addClass(ele, options.errorClass);
        }
      }
    }
  }

  function itemLoaded(ele, options) {
    $.status(ele, true, options);
  }

  // @todo merge with Bio.js.
  function handleSource(ele, attr, dataAttr) {
    var dataSrc = $.attr(ele, dataAttr);
    if (dataSrc) {
      $.attr(ele, attr, dataSrc);
      $.removeAttr(ele, dataAttr);
    }
  }

  function handleSources(ele, src, srcset, fixRatio) {
    if (srcset) {
      $.attr(ele, ATTR_SRCSET, srcset);
    }

    // Tricking IE + other oldies to fix aspect ratio due to no CSS object-fit.
    // Credits: @stevefenton, @primozcigler. Modified as needed as usual.
    // "IE9-11 & Edge don't properly scale SVG files. Adding height, width,
    // viewBox, and CSS rules seems to be the best workaround."
    // @see https://caniuse.com/svg.
    if (fixRatio) {
      $.addClass(ele, 'is-b-ie');
      ele.src = 'data:image/svg+xml,%3Csvg%20xmlns%3D\'http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg\'%20viewBox%3D\'0%200%20' + (ele.width || 1) + '%20' + (ele.height || 1) + '\'%2F%3E';
    }
    else {
      ele.src = src;
    }
  }

  function saveViewportOffset(opts) {
    WINDATA = FN_VIEWPORT.update(opts);
    VP = FN_VIEWPORT.vp;
  }

});
