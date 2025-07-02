/**
 * @file
 * Provides [Intersection|Resize]Observer extensions.
 *
 * @internal
 *   This is an internal part of the Blazy system and should only be used by
 *   blazy-related code in Blazy module, or its sub-modules.
 *
 * @todo remove fallback for bLazy fork when min D10.
 */

(function ($, _win) {

  'use strict';

  var FN_VIEWPORT = $.viewport;

  // Enqueue operations.
  function enqueue(queue, cb, scope) {
    $.each(queue, cb.bind(scope));
    queue.length = 0;
  }

  $.observer = {
    elms: [],
    scope: {},
    withIo: false,
    // @todo remove elms and withIo for scope properties at blazy: 4.x, or soon.
    init: function (scope, cb, elms, withIo) {
      var me = this;
      var opts = scope.options || {};
      var queue = scope._queue || [];
      var resizeTrigger;
      var data = 'windowData' in scope ? scope.windowData() : {};
      var viewport = $.viewport;

      // In case called outside the workflow.
      if (!scope._raf) {
        scope._raf = [];
      }

      // Do not fill in the root, else broken. Leave it to browsers.
      var config = {
        rootMargin: opts.rootMargin || '0px',
        threshold: opts.threshold || 0
      };

      // To remove old extra params from self::observe().
      me.elms = elms = $.toArray(scope.elms || elms);
      me.scope = scope;
      me.withIo = scope.withIo || withIo;

      function _cb(entries) {
        if (!queue.length) {
          var raf = requestAnimationFrame(_enqueue);
          scope._raf.push(raf);
        }

        queue.push(entries);

        // Default to old browsers.
        return false;
      }

      function _enqueue() {
        enqueue(queue, cb, scope);
      }

      // IntersectionObserver for modern browsers, else degrades for IE11, etc.
      // @see https://caniuse.com/IntersectionObserver
      if (withIo) {
        var _ioObserve = function () {
          return $.isIo ? new IntersectionObserver(_cb, config) : cb.call(scope, elms);
        };

        scope.ioObserver = _ioObserve();
      }

      // ResizeObserver for modern browsers, else degrades for IE11, etc.
      // @see https://caniuse.com/ResizeObserver
      // @see https://developer.mozilla.org/en-US/docs/Web/API/ResizeObserver
      var _roObserve = function () {
        resizeTrigger = this;

        // Called once during page load, not called during resizing.
        data = $.isUnd(data.ww) ? viewport.windowData(opts, true) : scope.windowData();
        return $.isRo ? new ResizeObserver(_cb) : cb.call(scope, elms);
      };

      scope.roObserver = _roObserve();
      scope.resizeTrigger = resizeTrigger;

      return data;
    },

    visibleParent: function (entry) {
      var vp = FN_VIEWPORT;

      if (vp && vp.isHidden(entry)) {
        return vp.visibleParent(entry);
      }
      return null;
    },

    hiddenChild: function (el, sel) {
      var me = this;
      var io = me.scope.ioObserver;

      // We are here with arbitrary observed elements for hidden children.
      // See https://drupal.org/node/3279316.
      if (io && !$.is(el, sel)) {
        var check = $.find(el, sel);
        if ($.isElm(check)) {
          // The job is done, unobserve.
          io.unobserve(el);
          // Pass back bounding rects to the unbound hidden element here on.
          return check;
        }
      }
      return null;
    },

    observe: function () {
      var me = this;
      var scope = me.scope;
      // @todo also call directly scope.elms, scope.withIo
      var elms = me.elms;
      var withIo = me.withIo;
      var opts = scope.options || {};
      var ioObserver = scope.ioObserver;
      var roObserver = scope.roObserver;
      var watch = function (watcher) {
        if (watcher && elms && elms.length) {
          $.each(elms, function (entry) {
            // IO cannot watch hidden elements, watch the closest visible one.
            // Once intersected, the parent must delegate back to the hidden.
            if (watcher === ioObserver) {
              var cn = me.visibleParent(entry);
              if (cn) {
                watcher.observe(cn);
              }
            }

            watcher.observe(entry);
          });
        }
      };

      if ($.isIo && (ioObserver || roObserver)) {
        // Allows observing resize only.
        if (withIo) {
          watch(ioObserver);
        }

        watch(roObserver);
      }
      else {
        // Blazy was not designed with Native lazy, can be removed via Blazy UI.
        if ('Blazy' in _win) {
          scope.bLazy = new Blazy(opts);
        }
      }
      return scope;
    },

    // @todo remove scope after another usage check.
    unload: function (scope) {
      scope = scope || this.scope;
      var rafs = scope._raf || [];

      if (rafs.length) {
        $.each(rafs, function (raf) {
          cancelAnimationFrame(raf);
        });
      }
    }
  };

})(dBlazy, this);
