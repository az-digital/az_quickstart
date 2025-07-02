/**
 * @file
 * Provides blur utility.
 */

(function ($, Drupal, _win) {

  'use strict';

  var BLUR = 'blur';
  var B_BLUR = 'b-' + BLUR;
  var K_BLUR = 'b' + BLUR;
  var ID = B_BLUR;
  var ID_ONCE = ID;
  var S_MOUNTED = 'is-' + ID;
  var S_ELEMENT = '.' + ID + ':not(.' + S_MOUNTED + ')';
  var P_DATA = 'data-';
  var DATA_BLUR = P_DATA + B_BLUR;
  var BLUR_STORAGES = [];
  var IS_STORAGE = _win.localStorage;
  var PLACEHOLDER = 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7';

  // https://developer.mozilla.org/en-US/docs/Web/API/HTMLCanvasElement.
  // https://caniuse.com/canvas
  function toDataUri(url, mime, cb) {
    var img = new Image();
    var load = function () {
      var me = this;

      var canvas = $.create('canvas');
      canvas.width = me.naturalWidth;
      canvas.height = me.naturalHeight;

      canvas.getContext('2d')
        .drawImage(me, 0, 0);

      cb(canvas.toDataURL(mime));
    };

    img.src = url;

    $.decode(img)
      .then(function () {
        load.call(img);
      })
      .catch(function () {
        cb(url);
      });
  }

  /**
   * Processes blur element.
   *
   * @param {Element} target
   *   The .b-lazy element, not the .b-blur one.
   */
  function blur(target) {
    var cn = $.aniElement(target);
    if (!$.isElm(cn)) {
      return;
    }

    var el = $.find(cn, 'img.' + B_BLUR);
    if (!$.isElm(el)) {
      return;
    }

    var data = $.attr(el, DATA_BLUR);
    if (!data) {
      return;
    }

    data = data.split('::');

    var shouldStore = IS_STORAGE && data[0] === '1';
    var isDisabled = data[0] === '-1';
    var bid = data[1];
    var mime = data[2];
    var url = data[3];
    var existing = null;
    var valid = false;
    var stored = $.storage(K_BLUR);
    var dbt = 'data-b-thumb';
    var dtValue = $.attr(cn, dbt);
    var found;

    if (dtValue) {
      if ($.is(url, dbt)) {
        url = dtValue;
      }
    }

    // If the browser is capable, and the client option enabled.
    if (shouldStore) {
      found = stored && $.contains(stored, bid);

      valid = !stored || !found;

      BLUR_STORAGES = stored ? $.parse(stored) : [];

      if (found) {
        $.each(BLUR_STORAGES, function (img) {
          var key = $.keys(img)[0];
          if (key === bid) {
            existing = img[bid];
            return false;
          }
        });
      }
    }
    else {
      // Clear, if disabled (-1), or switching to server from client-side (0).
      if (stored) {
        $.storage(K_BLUR, null);
      }
    }

    // If client is disabled (-1), use server-side data URI. Clear done above.
    // Run it late, to ensure storages are cleared above as configured.
    if (isDisabled) {
      $.removeAttr(el, DATA_BLUR);
      return;
    }

    // We are here when client is being enabled.
    if (existing) {
      el.src = existing;
    }
    else {
      toDataUri(url, mime, function (uri) {
        el.src = uri;

        if (shouldStore && valid) {
          var tmp = {};
          tmp[bid] = uri;

          BLUR_STORAGES.push(tmp);

          $.storage(K_BLUR, JSON.stringify(BLUR_STORAGES));
        }
      });
    }
  }

  // @todo remove at/ by 3.x, no longer relevant:
  $.blur = blur.bind($);

  /**
   * Blur utility functions.
   *
   * @param {HTMLElement} el
   *   The blur HTML element.
   */
  function process(el) {
    var cn = $.closest(el, '.media');
    blur(el);

    $.on(cn, 'blazy:animated.' + BLUR, function (e) {
      el.src = PLACEHOLDER;
      $.removeAttr(el, DATA_BLUR);
      // $.removeClass('is-' + BLUR + '-client');
    });
    $.addClass(el, S_MOUNTED);
  }

  /**
   * Attaches Blazy blur behavior to HTML element.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.blazyBlur = {
    attach: function (context) {
      $.once(process, ID_ONCE, S_ELEMENT, context);
    },
    detach: function (context, setting, trigger) {
      if (trigger === 'unload') {
        $.once.removeSafely(ID_ONCE, S_ELEMENT, context);
      }
    }
  };

})(dBlazy, Drupal, this);
