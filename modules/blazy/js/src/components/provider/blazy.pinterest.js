/**
 * @file
 * Provides pinterest initializer.
 */

(function ($, Drupal, _win) {

  'use strict';

  var ID = 'b-pinterest';
  var NICK = ID;
  var ID_ONCE = NICK;
  var DATA_PIN_DO = '[data-pin-do]';
  var DATA_PIN_HREF = '[data-pin-href]';
  var DATA_TOKEN = 'data-b-token';
  var C_MOUNTED = 'is-' + NICK;
  var S_BASE = '.' + ID;
  var S_ELEMENT = S_BASE + ':not(.' + C_MOUNTED + ')';
  var SCRIPT = 'https://assets.pinterest.com/js/pinit.js';

  function load(cb) {
    _win.setTimeout(function () {
      if (_win.PinUtils) {
        _win.PinUtils.build();

        if (cb) {
          cb();
        }
      }
    });
  }

  function _init(cb, token) {
    var fun = function () {
      load(cb);
    };

    if (_win.PinUtils) {
      fun();
    }
    else {
      $.getScript(SCRIPT, fun, token);
    }
  }

  $.pinterest = {
    root: null,
    token: null,
    init: function (root) {
      var me = this;
      me.root = root;
      me.token = $.attr(root, DATA_TOKEN) || ID;
    },

    show: function (cb) {
      var me = this;

      var loadMedia = function () {
        if (cb) {
          cb(me);
        }
      };

      _init(loadMedia, me.token);
    }
  };

  /**
   * Pinterest utility functions.
   *
   * @param {HTMLElement} el
   *   The [data-pin-do] HTML element.
   */
  function process(el) {
    var provider = $.pinterest;
    var pin = $.find(el, DATA_PIN_DO);

    provider.init(el);

    var isRendered = function (root) {
      var check = $.find(root, DATA_PIN_HREF);
      return $.isElm(check);
    };

    var show = function (pindo) {
      _win.setTimeout(function () {
        if (!isRendered(el)) {
          provider.show();
        }
      }, $.isElm(pindo) ? 3 : 301);
    };

    if ($.isElm(pin)) {
      show(pin);
    }
    else {
      if ($.isHtml(el)) {
        $.on(el, 'blazy.done', function (e) {
          el = e.target;
          if (!isRendered(el)) {
            pin = $.find(el, DATA_PIN_DO);
            show(pin);
          }
        });
      }
    }

    $.addClass(el, C_MOUNTED);
  }

  /**
   * Attaches Pinterest behavior to HTML element.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.blazyPinterest = {
    attach: function (context) {
      $.once(process, ID_ONCE, S_ELEMENT, context);
    },
    detach: function (context, setting, trigger) {
      if (trigger === 'unload') {
        $.once.removeSafely(ID_ONCE, S_ELEMENT, context);
      }
    }
  };

}(dBlazy, Drupal, this));
