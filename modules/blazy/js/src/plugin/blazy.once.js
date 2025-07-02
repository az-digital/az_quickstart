/**
 * @file
 * Provides once compat for D9+.
 *
 * @internal
 *   This is an internal part of the Blazy system and should only be used by
 *   blazy-related code in Blazy module, or its sub-modules.
 *
 * @see https://www.drupal.org/project/drupal/issues/1461322
 * @see https://www.drupal.org/project/slick/issues/3340509
 * @see https://www.drupal.org/project/slick/issues/3211873
 */

(function ($, Drupal, _win) {

  'use strict';

  // See https://www.drupal.org/project/drupal/issues/3254840
  var coreOnce = Drupal.once || _win.once;

  /**
   * A wrapper for core/once with some BC.
   *
   * @param {Function|string} cb
   *   The executed function, or string for regular core/once.
   * @param {string} id
   *   The id of the once call.
   * @param {NodeList|Array.<Element>|Element|string} selector
   *   A NodeList, array of elements, single Element, or a string.
   * @param {Document|Element|null} ctx
   *   An element to use as context for querySelectorAll, or empty.
   * @param {Object|undefined} scope
   *   A value to use as `this` when executing cb, default to `undefined`.
   *
   * @return {Array.<Element>}
   *   An array of elements to process, or empty for old behavior.
   */
  function onceCompat(cb, id, selector, ctx, scope) {
    var els = [];

    // Prevents from BigPipe problematic multiple invocations.
    // Mostly relevant for [DOM|AJAX]-related mutation environment (LB, infinite
    // scroll, etc), hardly static pages without DOM modification. What this
    // does is waiting for BigPipe to do its job, and only when it is done, once
    // is called. The drawback, it will slightly delay DOM changes, yet better
    // than problematic multiple invocations.
    if (!$.wwoBigPipeDone()) {
      return els;
    }

    // If cb is a string, allow empty selector/ context for document.
    // Assumes once(id, selector, context), by shifting one argument.
    // This is the common implementation of core/once, but hardly used by Blazy.
    if ($.isStr(cb) && $.isUnd(ctx)) {
      return initOnce(cb, id, selector);
    }

    // Original once for BC.
    if ($.isUnd(selector)) {
      _once(cb);
    }
    // If extra arguments are provided, assumes regular loop over elements.
    else {
      els = initOnce(id, selector, ctx);
      if (els.length) {
        // Already avoids loop for a single item.
        $.each(els, cb, scope);
      }
    }

    return els;
  }

  /**
   * Executes the function once.
   *
   * @private
   *
   * @author Daniel Lamb <dlamb.open.source@gmail.com>
   * @link https://github.com/daniellmb/once.js
   *
   * @param {Function} cb
   *   The executed function.
   *
   * @return {Object}
   *   The function result.
   */
  function _once(cb) {
    var result;
    var ran = false;
    return function proxy() {
      if (ran) {
        return result;
      }
      ran = true;
      result = cb.apply(this, arguments);
      // For garbage collection.
      cb = null;
      return result;
    };
  }

  function _filter(selector, elements, apply) {
    return elements.filter(function (el) {
      var selected = $.is(el, selector);
      if (selected && apply) {
        apply(el);
      }
      return selected;
    });
  }

  // Since 3.0.6, uses core/once.
  function initOnce(id, selector, ctx) {
    var root = $.context(ctx, selector);
    return coreOnce(id, selector, root);
  }

  $.once = $.extend(onceCompat, coreOnce);
  $.once.counter = 0;
  $.filter = _filter;

  // Tested at D10.3, a workaround, not a final fix, till BigPipe issues fixed.
  // This is likely the root cause of BigPipe issues, unmatched detachments.
  // Normally called in Drupal.behaviors.detach() with trigger `unload`.
  // This check basically makes BigPipe behaves like without it as otherwise
  // `unload` trigger is called many times on BigPipe replacement jobs.
  // @todo update this if blazy ajax-related is broken later, that is when
  // BigPipe fixes this issue. See the above BigPipe issues.
  // @fixme, not really crucial, AJAX (IO/ VIS) requires 2, the rest 1.
  // Without BigPipe, always 0.
  $.once.unload = $.once.counter >= ($.isBigPipe() ? 1 : 0);

  // See https://developer.mozilla.org/en-US/docs/Web/CSS/:not
  function extractNot(selector) {
    var mounted = [];
    if ($.contains(selector, ':not')) {
      var notsels = selector.split(':not');

      $.each(notsels, function (notsel) {
        if ($.contains(notsel, '(')) {
          var cls = notsel.split('(').pop().split(')')[0];

          // Selector list/ compound argument, with commas.
          if ($.contains(cls, ',')) {
            var vals = cls.split(',');
            $.each(vals, function (val) {
              val = val.replace('.', '');
              mounted.push(val);
            });
          }
          else {
            if (cls) {
              cls = cls.replace('.', '');
              mounted.push(cls);
            }
          }
        }
      });
    }
    return mounted;
  }

  $.once.removeSafely = function (id, selector, ctx) {
    var me = this;
    var els = [];
    var root;
    var unload;
    var mounted;

    if ($.wwoBigPipeDone()) {
      unload = $.once.unload;
      root = $.context(ctx, selector);

      if (unload && me.find(id, root).length) {
        els = me.remove(id, selector, root);

        // @todo remove, might be no longer relevant for ::wwoBigPipeDone(),
        // only remove after :not() classes are removed to avoid blocking.
        mounted = extractNot(selector);
        if (els.length && mounted.length) {
          $.removeClass(els, mounted);
        }
      }
    }

    return els;
  };

  /**
   * Attaches Blazy behavior to nothing for BigPipe compat.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.blazyOnce = {
    attach: function (context) {

      $.wwoBigPipe(function () {
        if ($.once.counter > 1) {
          $.once.counter--;
        }
      });

    },
    detach: function (context, setting, trigger) {
      if (trigger === 'unload') {
        $.wwoBigPipe(function () {
          $.once.counter++;
        });
      }
    }
  };

})(dBlazy, Drupal, this);
