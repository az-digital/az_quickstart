/**
 * @file
 * Provides shared drupal-related methods normally driven by Drupal UI options.
 *
 * Old bLazy is now IO fallback to reduce competition and complexity
 * and cross-compat better between Native and old approach (data-[SRC|SRCSET]).
 * The reason old bLazy was not designed to cope with Native, Bio is.
 * Native lazy was born (2019) after bLazy ceased 3 years before (2016).
 */

(function ($, Drupal, drupalSettings, _win, _doc) {

  'use strict';

  var ID = 'blazy';
  var NAME = 'Drupal.' + ID;
  var DATA = 'data';
  var C_BG = 'b-bg';
  var C_ERROR = 'errorClass';
  var C_CHECKED = 'b-checked';
  var DATA_B_BG = DATA + '-' + C_BG;
  var DATA_B_RATIOS = DATA + '-b-ratios';
  var S_BLUR = '.b-blur';
  var S_MEDIA = '.media';
  var C_SUCCESS = 'successClass';
  var E_DONE = ID + ':done';
  var E_ERROR = ID + ':error';
  var NOOP = function () {};
  var EXTENSIONS = {};

  /**
   * Blazy public properties and methods.
   *
   * @namespace
   */
  Drupal.blazy = {
    context: _doc,
    name: NAME,
    init: null,
    instances: [],
    resizeTick: 0,
    resizeTrigger: false,
    blazySettings: drupalSettings.blazy || {},
    ioSettings: drupalSettings.blazyIo || {},
    options: {},
    clearCompat: NOOP,
    clearScript: NOOP,
    checkResize: NOOP,
    resizing: NOOP,
    revalidate: NOOP,

    // Enforced since IO (bio.js) makes bLazy a fallback internally since 2.6.
    // @todo remove, no longer relevant for IO with Blazy fallback.
    isIo: function () {
      return true;
    },

    isBlazy: function () {
      return !$.isIo && 'Blazy' in _win;
    },

    isFluid: function (el, cn) {
      // @todo remove the last at/by 3.x:
      return $.equal(el.parentNode, 'picture') &&
        $.hasAttr(cn, DATA_B_RATIOS);
    },

    isLoaded: function (el) {
      return $.hasClass(el, this.options[C_SUCCESS]);
    },

    globals: function () {
      var me = this;
      var commons = {
        isMedia: true,
        success: me.clearing.bind(me),
        error: me.clearing.bind(me),
        resizing: me.resizing.bind(me),
        selector: '.b-lazy',
        parent: S_MEDIA,
        errorClass: 'b-error',
        successClass: 'b-loaded'
      };

      return $.extend(me.blazySettings, me.ioSettings, commons);
    },

    extend: function (plugins) {
      EXTENSIONS = $.extend({}, EXTENSIONS, plugins);
    },

    merge: function (opts) {
      var me = this;
      me.options = $.extend({}, me.globals(), me.options, opts || {});
      return me.options;
    },

    run: function (opts) {
      // @see https://www.drupal.org/project/blazy/issues/3258851
      // var els = $.findAll(_doc, '.media--ratio--fluid, .' + C_BG);
      // opts.disconnect = opts.disconnect || (!els.length && $.isUnd(Drupal.io));
      return new BioMedia(opts);
    },

    mount: function (exe) {
      var me = this;

      // This may be set by lazyload script, but not when `No JavaScript` off.
      me.merge();

      // Executes all extensions.
      if (exe) {
        $.each(EXTENSIONS, function (fn) {
          if ($.isFun(fn)) {
            fn.call(me);
          }
        });
      }

      return $.extend(me, EXTENSIONS);
    },

    selector: function (suffix) {
      suffix = suffix || '';
      var opts = this.options;
      return opts.selector + suffix + ':not(.' + opts[C_SUCCESS] + ')';
    },

    clearing: function (el) {
      var me = this;
      var ie;

      // Bail out if any error.
      if ($.hasClass(el, me.options[C_ERROR]) && !$.hasClass(el, C_CHECKED)) {
        $.addClass(el, C_CHECKED);
        // Clear loading classes. Also supports future delayed Native loading.
        if ($.isFun($.unloading)) {
          $.unloading(el);
        }

        $.trigger(el, E_ERROR, [me]);
        return;
      }

      // @see https://scottjehl.github.io/picturefill/
      // @todo remove when IE gone from planet Drupal.
      ie = $.hasClass(el, 'b-responsive') && $.hasAttr(el, DATA + '-pfsrc');
      if (_win.picturefill && ie) {
        _win.picturefill({
          reevaluate: true,
          elements: [el]
        });
      }

      // DOM ready fix as usual.
      _win.setTimeout(function () {
        // Instagram, Pinterest, etc. with lazyloaded HTML if configured.
        if ($.isHtml(el)) {
          Drupal.attachBehaviors(el);
        }

        // Clear loading classes. Also supports future delayed Native loading.
        if ($.isFun($.unloading)) {
          $.unloading(el);
        }
      }, 300);

      // With `No JavaScript` on, facilitate both parties: native vs. script.
      // This is to use the same clearing approach for all parties.
      me.clearCompat(el);
      me.clearScript(el);

      // Provides event listeners for easy overrides without full overrides.
      $.trigger(el, E_DONE, {
        options: me.options
      });
    },

    windowData: function () {
      return this.init ? this.init.windowData() : {};
    },

    // Only do this to fix errors, revalidation.
    load: function (cn) {
      var me = this;

      // DOM ready fix.
      _win.setTimeout(function () {
        // Filter out the failing ones.
        var elms = $.findAll(cn || _doc, me.selector());

        if (elms.length) {
          $.each(elms, me.update.bind(me));
        }
      }, 100);
    },

    update: function (el, delayed, winData) {
      var me = this;
      var opts = me.options;
      var sel = opts.selector;
      var _update = function () {
        if ($.hasAttr(el, DATA_B_BG) && $.isFun($.bg)) {
          $.bg(el, winData || me.windowData());
        }
        else {
          if (me.init) {
            if (!$.hasClass(el, sel.substring(1))) {
              el = $.find(el, sel) || el;
            }
            me.init.load(el, true, opts);
          }
        }
      };

      delayed = delayed || false;
      if (delayed) {
        // DOM ready fix.
        _win.setTimeout(_update, 100);
      }
      else {
        _update();
      }
    },

    // Re-calculate image dimensions which may vary per breakpoint such as for
    // Masonry during resizing. When images are loaded, Flexbox or Native Grid
    // as Masonry might need info about the loaded image dimensions to calculate
    // gaps or positions. Hooking into onload event ensures dimensions correct.
    // @todo move it out to grid-related which requires this.
    rebind: function (root, cb, observer) {
      var me = this;
      var elms = $.findAll(root, me.options.selector + ':not(' + S_BLUR + ')');
      var isMe = elms.length;

      if (!isMe) {
        elms = $.findAll(root, 'img:not(' + S_BLUR + ')');
      }

      if (elms.length) {
        $.each(elms, function (el) {
          var type = isMe ? E_DONE : 'load';
          $.one(el, type, cb, isMe);

          if (observer) {
            observer.observe(el);
          }
        });
      }
    },

    pad: function (el, cb, delay) {
      var me = this;
      var cn = $.closest(el, S_MEDIA) || el;

      var check = function () {
        var pad = Math.round(((el.naturalHeight / el.naturalWidth) * 100), 2);

        // Only applies to aspect ratio fluid.
        if (me.isFluid(el, cn)) {
          cn.style.paddingBottom = pad + '%';
        }

        // Any functions which require dimensions setup: blur, bg, ratio, etc.
        if ($.isFun(cb)) {
          cb.call(me, el, cn, pad);
        }
      };

      // Fixed for effect Blur messes up Aspect ratio Fluid calculation.
      setTimeout(check, delay || 0);
    }

  };

}(dBlazy, Drupal, drupalSettings, this, this.document));
