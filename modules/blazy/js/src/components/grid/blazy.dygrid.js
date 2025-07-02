/**
 * @file
 * Provides dynamic multi-breakpoint grids for Native Grid and Flexbox.
 *
 * @see https://developer.mozilla.org/en-US/docs/Web/CSS/CSS_Grid_Layout
 * @see https://developer.mozilla.org/en-US/docs/Web/CSS/flex
 */

(function ($, Drupal, _win, _doc, _html) {

  'use strict';

  var ID = 'dygrd';
  var C_GRID = 'grid';
  var DATA_W = 'data-b-w';
  var DATA_H = 'data-b-h';
  var MIN_WIDTH = 'min-device-width';
  var MAX_WIDTH = 'max-device-width';
  var IS_LOADING = ['is-b-loading', 'is-b-visible'];

  $.dyGrid = {
    options: {},

    resized: false,

    breakpoints: function () {
      var me = this;
      var opts = me.options;

      return {
        md: $.computeStyle(_html, opts.md, true),
        lg: $.computeStyle(_html, opts.lg, true)
      };
    },

    mediaQuery: function () {
      var me = this;
      var data = me.breakpoints();
      var min = MIN_WIDTH + ': ' + data.md;
      var max = MAX_WIDTH + ': ' + data.lg;

      return _win.matchMedia('only screen and (' + min + ') and (' + max + ')');
    },

    toObject: function (elms, e) {
      var me = this;
      var opts = me.options;

      return elms.map(function (root) {
        var children = $.slice(root.childNodes);
        var dataset = $.parse(atob($.attr(root, opts.dataId)));

        return {
          _el: root,
          event: e || {},
          md: dataset.md,
          lg: dataset.lg,
          items: children.filter(function (c) {
            return $.hasClass(c, C_GRID) && !$.hasClass(c, 'region--bg');
          })
        };
      });
    },

    /**
     * Processes a grid object.
     *
     * @param {Object} grid
     *   The grid object.
     */
    subprocess: function (grid) {
      var me = this;
      var opts = me.options;
      var e = grid.event;

      var update = function (obj, data) {
        if (data) {
          $.each(obj.items, function (item, i) {
            var dim = data[i];
            if (dim) {
              if (dim[0]) {
                $.attr(item, DATA_W, dim[0]);
              }
              if (dim[1]) {
                $.attr(item, DATA_H, dim[1]);
              }
            }
          });
        }
      };

      // If any event, or modification.
      if (e === opts.unload || e.matches || grid.mod) {
        $.addClass(grid._el, IS_LOADING);
        if (e.matches) {
          if (!grid.matches) {
            update(grid, grid.md);
            grid.matches = true;
          }
        }
        else {
          update(grid, grid.lg);
          grid.matches = false;
        }

        grid.mod = false;

        setTimeout(function () {
          $.removeClass(grid._el, IS_LOADING);
        }, 300);
      }
    },

    /**
     * Initialize the grid elements.
     *
     * @param {HTMLElement} elms
     *   The container HTML elements.
     * @param {Object} opts
     *   The options.
     */
    init: function (elms, opts) {
      var me = this;

      me.options = opts;

      var objs = me.toObject(elms);

      var onResize = function (entry) {
        if (me.resized) {
          objs.find(function (grid) {
            if (grid._el === entry.target.parentElement) {
              if (me.resized) {
                me.subprocess(grid);
              }
              return true;
            }
            return false;
          }).mod = true;
        }
      };

      var o = new ResizeObserver(function (entries) {
        $.each(entries, onResize);

        if (!me.resized) {
          me.resized = true;
        }
      });

      function observe() {
        $.each(objs, function (grid) {
          $.each(grid.items, function (c) {
            o.observe(c);
          });
        });
      }

      function layout(e) {
        // Only change if needs changing.
        if (e) {
          // If AJAX/ infinite scroll, ref-fetch newly added DOM elements.
          if (e === me.options.unload) {
            elms = $.toElms(opts.selector);

            if (elms.length) {
              objs = me.toObject(elms, e);

              var check = objs.find(function (grid) {
                return $.hasClass(grid._el, opts.cName);
              });

              if (check) {
                check.mod = true;
              }
            }

            me.options.unload = false;
          }
          else {
            // If matching the contraint for MD, or need resizing.
            objs = me.toObject(elms, e);
          }
        }
        $.each(objs, me.subprocess, me);
      }

      var watch = function (e) {
        setTimeout(function () {

          observe();
          layout(e);

          // AJAX package may be late to populate DOM.
        }, e === me.options.unload ? 101 : 1);
      };

      // Fix for LB, infinite scroll, or AJAX in general integration.
      $.on('blazy:ajaxSuccess.' + ID, function (e, ctx, response, status) {
        if (response && response.length) {
          watch(true);
        }
      });

      var query = me.mediaQuery();

      watch(query);
      query.addEventListener('change', layout);
    }
  };

}(dBlazy, Drupal, this, this.document, this.document.documentElement));
