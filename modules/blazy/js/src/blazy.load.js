/**
 * @file
 * Provides native, Intersection Observer API, or bLazy lazy loader.
 *
 * This file is not loaded when `No JavaScript` lazy loader is enabled.
 * It uses data-[SRC|SCRSET] containing fixes for this particular approach:
 *  - Views rewrite stripping out data URI causing 404.
 *  - Reduce abrupt ratio changes specific for Picture when Fluid is ON.
 *  - Scrolling CSS selector like Modal library, parallax, etc.
 *  - Revalidation for the failing ones.
 *
 * It is for those who still support IE9+, and similar oldies. The bLazy
 * library supports IE7+, but the module only tested it at IE9+ years ago.
 * There might new IE issues due to latest devs, but could be fixed by polyfill.
 * Obvious change since Blazy 2.6+, it removed old IE7s codes from dBlazy.js.
 * Works absurdly fine at IE9 at 2.6. Older versions/browsers might not.
 */

(function ($, Drupal, drupalSettings, _win, _doc) {

  'use strict';

  var ID = 'blazy';
  var ID_ONCE = ID;
  var C_MOUNTED = 'is-' + ID;
  var S_ELEMENT = '.' + ID + ':not(.' + C_MOUNTED + ')';
  var S_GLOBAL = 'body';
  var ID_ONCE_GLOBAL = 'b-root';
  var V_DATA = 'data';
  var V_IMAGE = 'image';
  var V_SRC = 'src';
  var S_SCROLL_ELEMENTS = '#drupal-modal, .is-b-scroll';

  /**
   * Blazy public methods.
   *
   * @namespace
   */
  Drupal.blazy = $.extend(Drupal.blazy || {}, {

    clearScript: function (el) {
      var me = this;

      // Update picture aspect ratio on being resized.
      me.pad(el, updatePicture);
    },

    /**
     * Attempts to fix for Views rewrite stripping out data URI causing 404.
     *
     * This is not needed by `No JavaScript` version due to no placeholders.
     *
     * E.g.: src="image/jpg;base64 should be src="data:image/jpg;base64.
     * The browsers load it as https://mysite.com/image/jpg... which causes 404.
     * The "Placeholder" 1px.gif via Blazy UI costs extra HTTP requests. This is
     * a less costly solution, but not bulletproof due to being client-side
     * which means too late to the party. Yet not bad for 404s below the fold.
     * This must be run before any lazy (native, bLazy or IO) kicks in.
     *
     * @todo Remove if a permanent non-client available other than Placeholder.
     */
    fixDataUri: function () {
      var me = this;
      var els = $.findAll(_doc, me.selector('[src^="' + V_IMAGE + '"]'));
      var fix = function (img) {
        var src = $.attr(img, V_SRC);
        if ($.contains(src, ['base64', 'svg+xml'])) {
          $.attr(img, V_SRC, src.replace(V_IMAGE, V_DATA + ':' + V_IMAGE));
        }
      };

      if (els.length) {
        $.each(els, fix);
      }
    }
  });

  function updatePicture(el, cn, pad) {
    var me = this;
    var isResized = me.resizeTick > 1;
    var elms = me.instances;

    // Swap all aspect ratio once to reduce abrupt ratio changes for the rest.
    // This triggers a one time event to apply fixes at each .blazy container
    // once after the first resizeTick is emitted.
    if (elms.length && isResized) {
      var picture = function (root) {
        if (root.dblazy && root.dbuniform) {
          if ((root.dblazy === cn.dblazy) && !root.dbpicture) {
            // @todo rename it to blazy:done to allow namespacing.
            $.trigger(root, ID + ':uniform' + root.dblazy, {
              pad: pad
            });
            root.dbpicture = true;
          }
        }
      };

      // Uniform sizes must apply to each instance, not globally.
      $.each(elms, function (elm) {
        $.debounce(picture, elm, me);
      }, me);
    }
  }

  /**
   * Initialize the blazy instance, either basic, advanced, or native.
   *
   * This is not needed by `No JavaScript` version due to no libraries.
   *
   * @param {HTMLElement} context
   *   The documentElement.
   */
  var init = function (context) {
    var me = this;
    var opts = {
      mobileFirst: false
    };

    // Set docroot in case we are in an iframe.
    if (!_doc.documentElement.isSameNode(context)) {
      opts.root = context;
    }

    opts = me.merge(opts);

    // Old bLazy, not IO, might need scrolling CSS selector like Modal library.
    // A scrolling modal with an iframe like Entity Browser has no issue since
    // the scrolling container is the entire DOM. Another use case is parallax.
    var container = opts.container;
    if (container && !$.contains(S_SCROLL_ELEMENTS, container)) {
      S_SCROLL_ELEMENTS += ', ' + container.trim();
    }

    opts.container = S_SCROLL_ELEMENTS;
    me.merge(opts);

    // Attempts to fix for Views rewrite stripping out data URI causing 404.
    me.fixDataUri();

    // Put the blazy/IO instance into a public object for references/ overrides.
    me.init = me.run(me.options);
  };

  /**
   * Blazy utility functions.
   *
   * @param {HTMLElement} elm
   *   The .blazy/[data-blazy] container, not the lazyloaded .b-lazy element.
   */
  function process(elm) {
    var me = this;
    var opts = $.parse($.attr(elm, 'data-' + ID));
    var isUniform = $.hasClass(elm, ID + '--field b-grid ' + ID + '--uniform');
    var instance = (Math.random() * 10000).toFixed(0);
    var eventId = ID + ':uniform' + instance;
    var localItems = $.findAll(elm, '.media--ratio');

    me.merge(opts);
    me.revalidate = me.revalidate || $.hasClass(elm, ID + '--revalidate');

    // Each cointainer may have different image styles and aspect ratio.
    // Provides marker to call event once, since adding classes make no sense.
    // @todo this can be removed when we figure out a better solution.
    elm.dblazy = instance;
    elm.dbuniform = isUniform;

    me.instances.push(elm);

    // @todo re-check if `No JavaScript` version needs help with reflows.
    // @todo move it to bio.js if also needed there.
    var swapRatio = function (e) {
      var pad = e.detail.pad || 0;

      if (pad > 10) {
        $.each(localItems, function (cn) {
          cn.style.paddingBottom = pad + '%';
        });
      }
    };

    // Triggered per .blazy container, not .b-lazy item on resizing to reduce
    // abrupt ratio changes for the rest after the first loaded.
    // Basically setting up the fixed frame specific for dynamic Picture as
    // otherwise they apperar collapsed due to slow loaded images.
    // To support resizing, use debounce. To disable use $.one().
    // @todo remove to not support resizing to minimize complication.
    // @todo move it into ResizeObserver if doable otherwise.
    if (isUniform && localItems.length) {
      $.on(elm, eventId, swapRatio);
    }
    $.addClass(elm, C_MOUNTED);
  }

  /**
   * Attaches blazy behavior to HTML element identified by .blazy/[data-blazy].
   *
   * The .blazy/[data-blazy] is the .b-lazy container, might be .field, etc.
   * The .b-lazy is the individual IMG, IFRAME, PICTURE, VIDEO, DIV, BODY, etc.
   * The lazy-loaded element is .b-lazy, not its container. Note the hypen (b-)!
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.blazy = {
    attach: function (context) {

      var me = Drupal.blazy;

      me.context = $.context(context);

      // Processes .blazy, if available, without initialization.
      // Initialization is not per container to also support IO with root.
      $.once(process.bind(me), ID_ONCE, S_ELEMENT, context);

      // Initializes blazy once as a global observer, not per container.
      $.once(init.bind(me), ID_ONCE_GLOBAL, S_GLOBAL, context);
    },
    detach: function (context, setting, trigger) {
      if (trigger === 'unload') {
        $.once.removeSafely(ID_ONCE, S_ELEMENT, context);
        $.once.removeSafely(ID_ONCE_GLOBAL, S_GLOBAL, context);
      }
    }
  };

}(dBlazy, Drupal, drupalSettings, this, this.document));
