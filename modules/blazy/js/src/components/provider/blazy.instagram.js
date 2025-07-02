/**
 * @file
 * Provides instagram initializer.
 */

(function ($, Drupal, _win, _doc) {

  'use strict';

  var ID = 'b-instagram';
  var NICK = ID;
  var ID_ONCE = NICK;
  var C_MOUNTED = 'is-' + NICK;
  var C_LOADED = C_MOUNTED + '-loaded';
  var S_BASE = '.' + ID;
  var S_ELEMENT = S_BASE + ':not(.' + C_MOUNTED + ')';
  var DATA_TOKEN = 'data-b-token';
  var IFRAMES = {};
  var IFRAME = 'iframe';
  var SCRIPT = '//platform.instagram.com/en_US/embeds.js';
  // var SCRIPT = 'https://www.instagram.com/embed.js';

  function load(cb) {
    if (_win.instgrm) {
      _win.instgrm.Embeds.process();
      if (cb) {
        cb();
      }
    }
  }

  function _init(cb, token) {
    var fun = function () {
      load(cb);
    };

    if (_win.instgrm) {
      fun();
    }
    else {
      $.getScript(SCRIPT, fun, token);
    }
  }

  function update(root, w) {
    if (!root) {
      return;
    }

    var pw = root.parentElement;

    root.style.minWidth = w + 'px';
    if ($.hasClass(pw, 'media-wrapper')) {
      pw.style.minWidth = w + 'px';
    }

    // @todo remove if no issues with aspect ratio.
    root.style.paddingBottom = '';
    $.removeClass(root, 'media--ratio media--ratio--fluid');
    $.addClass(root, C_LOADED);
  }

  function onLoad(iframe, cb) {
    var me = this;
    var root = me.root;
    var token = me.token;
    var ws = me.ws;
    var w;
    var h;

    $.on(iframe, 'load', function () {
      var ifrm = this;
      w = $.toInt($.css(ifrm, 'min-width'), 326);
      h = $.toInt($.height(ifrm), 520);

      if (h < 180 || ws.height < 620) {
        h = ws.height - 60;
      }

      update(root, w);

      me.width = w;
      me.height = h;

      if (!IFRAMES[token]) {
        iframe.innerHTML = '';

        IFRAMES[token] = {
          iframe: iframe,
          width: w,
          height: h
        };
      }

      if (cb) {
        cb(me);
      }
    });
  }

  $.instagram = {
    root: null,
    token: null,
    width: null,
    height: null,
    ws: null,
    init: function (root, obj) {
      var me = this;
      me.root = root;
      me.token = obj.token;
      me.width = obj.width;
      me.height = obj.height;
      me.ws = $.windowSize();
    },

    show: function (cb, iframe) {
      var me = this;
      var root = me.root;
      var token = me.token || $.attr(root, DATA_TOKEN);

      if (!token) {
        return;
      }

      var fromCache = function () {
        var cache = IFRAMES[token];
        if (cache) {
          me.width = cache.width || me.width;
          me.height = cache.height || me.height;

          update(root, me.width);

          if (cb) {
            cb(me);
          }
        }
      };

      var fromDisk = function () {
        iframe = iframe || $.find(root, IFRAME);
        if ($.isElm(iframe)) {
          onLoad.call(me, iframe, cb);
        }
      };

      var loadIframe = function () {
        if (IFRAMES[token]) {
          fromCache();
        }
        else {
          fromDisk();
        }
      };

      _init(loadIframe, token);
    },

    destroy: function () {
      // IFRAMES = {};
    },

    exists: function () {
      var token = this.token;
      return !$.isUnd(IFRAMES[token]) && !$.isUnd(IFRAMES[token].iframe);
    }
  };

  /**
   * Instagram utility functions.
   *
   * @param {HTMLElement} el
   *   The instagram HTML element.
   */
  function process(el) {
    var iframe;
    var token = $.attr(el, DATA_TOKEN);
    var instagram = $.instagram;
    var data = {
      token: token
    };

    $.ready(function () {
      instagram.init(el, data);

      iframe = $.find(el, 'iframe');

      if ($.isElm(iframe)) {
        instagram.show();
      }
      else {
        _init(null, token);
      }
    });

    $.addClass(el, C_MOUNTED);
  }

  /**
   * Attaches Instagram behavior to HTML element.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.blazyInstagram = {
    attach: function (context) {
      $.once(process, ID_ONCE, S_ELEMENT, context);
    },
    detach: function (context, setting, trigger) {
      if (trigger === 'unload') {
        $.once.removeSafely(ID_ONCE, S_ELEMENT, context);
      }
    }
  };

}(dBlazy, Drupal, this, this.document));
