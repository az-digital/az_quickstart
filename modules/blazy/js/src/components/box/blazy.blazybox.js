/**
 * @file
 * Provides a fullscreen video view for Intense, ElevateZoomPlus, etc.
 *
 * @todo provide Native Fullscreen API toggler with an optional polyfill.
 */

(function ($, Drupal, _win, _doc) {

  'use strict';

  var ID = 'blazybox';
  var ID_ITEM = 'blzybx';
  var NICK = 'bbox';
  var ID_ONCE = ID;
  var IFRAME = 'iframe';
  var C_MOUNTED = 'is-' + NICK;
  var S_BASE = '.' + ID;
  var S_ELEMENT = S_BASE + ':not(.' + C_MOUNTED + ')';
  var S_CONTENT = S_BASE + '__content';
  var C_MD_ELEMENT = 'media__element';
  var S_BTN_CLOSE = S_BASE + '__close';
  var C_IS_OPEN = 'is-' + ID + '--open';
  var FIT_HEIGHT = C_MOUNTED + '--fh';
  var IS_FULLSCREEN = C_MOUNTED + '--fs';
  var B_PROVIDER = 'b-provider--';
  var C_HIDDEN = 'visually-hidden';
  var ARIA_HIDDEN = 'aria-hidden';
  var DATA_ID = 'data-' + ID;
  var S_TRIGGER = '[' + DATA_ID + '-trigger]';
  var FN_SANITIZER = $.sanitizer;
  var FN_MULTIMEDIA = $.multimedia || false;
  var CACHED_HTML = {};
  var PROVIDER;
  var OC;
  var OC_BODY;
  var OC_BODY_CLOSING;

  /**
   * Blazybox public methods.
   *
   * @namespace
   */
  Drupal.blazyBox = {
    btnClose: null,
    el: null,
    $el: null,
    options: {
      hideCloseBtn: false
    },

    /**
     * Open the blazyBox.
     *
     * @param {HTMLElement} trigger
     *   The link HTMLElement to extract video/ media data.
     * @param {Object} options
     *   The optional options containing: classes.
     */
    open: function (trigger, options) {
      var me = Drupal.blazyBox;
      var body = _doc.body;
      var $el = me.$el;
      var link = toElm(trigger);
      var dataset = $.isElm(link) ? $.parse($.attr(link, 'data-b-media data-media')) : {};
      var provider = dataset.provider;
      var irrational = dataset.irrational;
      var token = dataset.token;
      var elContent = $el.find(S_CONTENT);
      var elIframe;
      var elMedia;
      var winSize = $.windowSize();
      var opts = options || {};
      var content = CACHED_HTML[token];

      // Separate theme options from lighbox options.
      if ($.isUnd(opts.fs)) {
        opts.fs = true;
        opts.width = winSize.width;
        opts.height = winSize.height;
      }

      if (!content) {
        content = Drupal.theme('blazyBoxMedia', {
          el: link,
          dataset: dataset,
          options: opts
        });

        var config = {
          ADD_TAGS: [IFRAME],
          ADD_ATTR: [
            'allow',
            'allowfullscreen'
          ]
        };

        content = FN_SANITIZER.sanitize(content, config);
        CACHED_HTML[token] = content;
      }

      // Drupal.attachBehaviors($el[0]);
      $el.removeClass(C_HIDDEN)
        .attr(ARIA_HIDDEN, false);

      if (opts.fs) {
        $el.addClass(IS_FULLSCREEN);
      }

      $el.removeClass(B_PROVIDER + PROVIDER);
      if (provider) {
        $el.addClass(B_PROVIDER + provider);
      }

      elContent.innerHTML = content;

      if (options) {
        me.options = $.extend({}, me.options, options);
        var o = me.options;

        OC = o.class || '';
        OC_BODY = o.bodyClass || '';
        OC_BODY_CLOSING = o.bodyClosingClass || '';

        if (OC) {
          $el.addClass(OC);
        }

        if (OC_BODY) {
          $.removeClass(body, OC_BODY);
        }

        setTimeout(function () {
          if (OC_BODY) {
            $.addClass(body, OC_BODY);
          }
        }, 301);
      }
      else {
        $.addClass(body, C_IS_OPEN);
      }

      // Reset any (local) video/ audio to avoid multiple elements from playing.
      if (FN_MULTIMEDIA) {
        FN_MULTIMEDIA.pause();
      }

      $el.removeClass(FIT_HEIGHT);
      if (irrational) {
        $el.addClass(FIT_HEIGHT);
      }

      // Attach any dynamic media.
      elMedia = $.find(elContent, '.media');
      if ($.isElm(elMedia)) {
        Drupal.attachBehaviors(elMedia);
      }

      setTimeout(function () {
        elIframe = $.find(elContent, IFRAME);

        if ($.isElm(elIframe)) {
          $.addClass(elIframe, C_MD_ELEMENT);
        }
      }, 101);

      me.check();
      opts.provider = provider;

      $.trigger(ID + ':opened', [me, link, opts]);
      PROVIDER = provider;
    },

    /**
     * Close the blazyBox.
     *
     * @param {Event} e
     *   The mouse event triggering the close.
     */
    close: function (e) {
      var me = Drupal.blazyBox;
      var body = _doc.body;
      var $el = me.$el;

      // Allows calling this directly.
      if (!$.isUnd(e)) {
        e.preventDefault();
      }

      var closing = function () {
        $el.addClass(C_HIDDEN)
          .attr(ARIA_HIDDEN, true)
          .find(S_CONTENT).innerHTML = '';
      };

      var transitioning = function () {
        if (OC_BODY_CLOSING) {
          $.removeClass(body, OC_BODY_CLOSING);
        }
        if (OC) {
          $el.removeClass(OC);
          closing();
        }
      };

      $.removeClass(body, C_IS_OPEN);
      $el.removeClass(IS_FULLSCREEN);

      // var classes = $el.attr('class');
      // var check = (classes.match(/(^|\s)b-provider-\S+/g) || []).join(' ');
      // if (check) {
      // $el.removeClass(check);
      // }
      if (OC_BODY) {
        $.removeClass(body, OC_BODY);
      }
      if (OC_BODY_CLOSING) {
        $.addClass(body, OC_BODY_CLOSING);
      }
      else {
        closing();
      }

      $el.one('transitionend', transitioning);

      Drupal.detachBehaviors($el[0]);

      $.trigger(ID + ':closed', [me]);
    },

    check: function () {
      var me = this;

      if (me.options.hideCloseBtn) {
        var close = me.btnClose || me.$el.find(S_BTN_CLOSE);
        $.addClass(close, C_HIDDEN);
      }
    },

    /**
     * Attach the blazyBox.
     */
    attach: function () {
      var check = $.find(_doc.body, S_BASE);
      if (!$.isElm(check)) {
        $.append(_doc.body, Drupal.theme('blazyBox'));
      }
    },

    isOpened: function () {
      var me = Drupal.blazyBox;
      return !me.$el.hasClass(C_HIDDEN);
    }
  };

  // For future betterment, allows more complex data object than just url.
  function toElm(data) {
    var el = data;
    if ($.isObj(data)) {
      el = data.el || data.element;
    }
    return $.isElm(el) ? el : null;
  }

  /**
   * Theme function for a fullscreen lightbox video container.
   *
   * @return {String}
   *   Returns a html string.
   */
  Drupal.theme.blazyBox = function () {
    var html;

    html = '<div class="$id visually-hidden" tabindex="-1" role="dialog" aria-hidden="true" aria-label="$id">';
    html += '<div class="$id__content"></div>';
    html += '<button class="$id__close" data-role="none">&times;</button>';
    html += '</div>';

    return $.template(html, {
      id: ID
    });
  };

  /**
   * Theme function for a standalone fullscreen video.
   *
   * @param {Object} data
   *   An object containing:
   *   - el: The lightbox link element, normally [data-LIGHTBOX-trigger].
   *   - dataset: the [data-b-media] object, extracted from link element.
   *   - options: extra options not contained within dataset.
   *
   * @return {String}
   *   Returns a html string.
   */
  Drupal.theme.blazyBoxMedia = function (data) {
    var el = data.el;
    var dataset = data.dataset || {};
    var options = data.options || {};
    var fs = options.fs;
    var oembedUrl = $.attr(el, 'data-oembed-url');
    var alt;
    var href;
    var url;
    var pad;
    var content = dataset.html;
    var isMedia = true;
    var html = '';

    // Video|Audio|Responsive|Picture elements.
    if (content) {
      isMedia = false;
      if (dataset.encoded) {
        content = atob(content);
      }

      html += content;
    }
    else if (dataset.boxType === 'image') {
      fs = true;
      options.width = dataset.width;
      options.height = dataset.height;
      alt = $.image.alt(el, '');
      href = el.href;
      url = $.attr(el, 'data-box-url', href, true);
      html += '<img class="' + C_MD_ELEMENT + '" src="' + url + '" decoding="async" loading="eager" alt="' + alt + '" />';
    }

    // Iframe element.
    if (oembedUrl && !FN_SANITIZER.isDangerous('src', oembedUrl)) {
      html += '<iframe class="' + C_MD_ELEMENT + '" src="' + oembedUrl + '" width="100%" height="100%" allowfullscreen></iframe>';
    }

    if (fs && options.width && isMedia) {
      pad = $.image.ratio(options);
      var mdClass = 'media media--ratio media--ratio--fluid';
      var mdStyle = 'padding-bottom: ' + pad + '%; width:' + options.width + 'px;';
      html = '<div class="' + mdClass + '" style="' + mdStyle + '">' + html + '</div>';
    }

    return '<div class="' + ID + '__media">' + html + '</div>';
  };

  /**
   * Launch a blazybox.
   *
   * @param {Event} e
   *   The click event.
   */
  function launch(e) {
    var me = Drupal.blazyBox;

    e.preventDefault();
    e.stopPropagation();

    var target = e.target;
    var link = target.href ? target : $.closest(target, S_TRIGGER);
    me.open(link);
  }

  /**
   * BlazyBox utility functions.
   *
   * @param {HTMLElement} el
   *   The blazybox HTML element.
   */
  function process(el) {
    var me = Drupal.blazyBox;
    var $el = $(el);

    me.el = el;
    me.$el = $el;
    me.btnClose = $el.find(S_BTN_CLOSE);

    $el.on('click.' + ID, S_BTN_CLOSE, me.close, true);
    $el.addClass(C_MOUNTED);
  }

  /**
   * Trigger click on a blazybox link.
   *
   * @param {HTMLElement} el
   *   The triggering element of blazybox HTML element.
   */
  function subprocess(el) {
    $.on(el, 'click.' + ID, launch);
  }

  /**
   * Attaches Blazybox behavior to HTML element.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.blazyBox = {
    attach: function (context) {

      $.ready(function () {
        Drupal.blazyBox.attach();

        $.once(process, ID_ONCE, S_ELEMENT, context);
        $.once(subprocess, ID_ITEM, S_TRIGGER, context);
      });

    },
    detach: function (context, setting, trigger) {
      if (trigger === 'unload') {
        $.once.removeSafely(ID_ONCE, S_ELEMENT, context);
      }
    }
  };

})(dBlazy, Drupal, this, this.document);
