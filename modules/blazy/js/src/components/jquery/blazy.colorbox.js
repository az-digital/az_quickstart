/**
 * @file
 *
 * A launcher for responsive (remote|local) videos, Responsive|Picture images.
 *
 * Since 2.17, body classes is deprecated for local classes in the #colorbox.
 */

(function ($, _d, Drupal, drupalSettings, _win, _doc) {

  'use strict';

  var ID = 'colorbox';
  var NICK = 'cbox';
  var ID_ONCE = 'b-' + NICK;
  var B_ROOT = 'b-' + ID;
  var S_ROOT = '#' + ID;
  var C_MOUNTED = 'is-' + ID_ONCE;
  var S_ELEMENT = '[data-' + ID + '-trigger]:not(.' + C_MOUNTED + ')';
  var C_MEDIA_BOX = 'media media--box';
  var C_MEDIA_RATIO = C_MEDIA_BOX + ' media--ratio';
  var S_LOADED_CONTENT = '#cboxLoadedContent';
  var FN_SANITIZER = _d.sanitizer;
  var FN_INSTAGRAM = _d.instagram || false;
  var B_PROVIDER = 'b-provider--';
  var CACHED_HTML = {};
  var MD_PROVIDER;
  var CBOX_SETTINGS = drupalSettings.colorbox || {};
  var CBOX_TIMER;

  /**
   * Blazy Colorbox utility functions.
   *
   * @param {HTMLElement} box
   *   The colorbox HTML element.
   */
  function process(box) {
    var $root = $(S_ROOT);
    var $box = $(box);
    var media = $box.data('bMedia') || {};
    var oEmbedUrl = $box.data('oembedUrl');
    var url = box.href || 'x';

    if (oEmbedUrl) {
      url = oEmbedUrl;
    }

    var provider = media.provider;
    var boxType = media.boxType;
    var token = media.token;
    var isIframe = boxType === 'iframe' && !FN_SANITIZER.isDangerous('href', url);
    var isPinterest = provider === 'pinterest';
    var usePaddingHack = media.paddingHack || false;
    var isHtml = 'html' in media;
    var html = CACHED_HTML[token];

    if (isHtml && !html) {
      html = media.html;

      // If encoded, then decode it.
      if (media.encoded) {
        html = atob(html);
      }

      html = FN_SANITIZER.sanitize(html);

      CACHED_HTML[token] = html;
    }

    var runtimeOptions = {
      href: url,
      html: html,
      rel: media.rel || null,
      iframe: isIframe,
      title: function () {
        var $caption = $box.next('.litebox__caption');
        if ($caption.length) {
          return FN_SANITIZER.sanitize($caption[0].innerHTML);
        }
        return '';
      },
      onComplete: function () {
        _win.clearTimeout(CBOX_TIMER);

        // DOM ready fix.
        CBOX_TIMER = _win.setTimeout(function () {
          removeClasses();

          if ($('#cboxOverlay').is(':visible')) {
            $root.addClass(B_ROOT + '--' + boxType);

            if (provider) {
              $root.addClass(B_PROVIDER + provider);
            }

            if (isIframe || isHtml) {
              resizeBox();
            }
          }

          MD_PROVIDER = provider;
        });
      },
      onCleanup: function () {
        // Re-check might be empty for some reasons.
        $root = $(S_ROOT);
        var $media = $root.find('.media');

        if ($media.length) {
          Drupal.detachBehaviors($media[0]);
        }
      },
      onClosed: function () {
        removeClasses();
      }
    };

    /**
     * Remove the custom colorbox classes.
     */
    function removeClasses() {
      // Re-check might be empty for some reasons.
      $root = $(S_ROOT);

      $root.removeClass(B_PROVIDER + MD_PROVIDER);
      $root.removeClass(function (index, css) {
        return (css.match(/(^|\s)b-colorbox-\S+/g) || []).join(' ');
      });
    }

    // Resize.
    function resize(o) {
      $.colorbox.resize({
        innerWidth: o.width,
        innerHeight: o.height
      });
    }

    // Dimensions.
    function dimension(w, h) {
      return _d.image.dimension(w, h);
    }

    // Padding hack.
    function hack(a, b) {
      return _d.image.hack(a, b);
    }

    // Responsive image|Picture.
    function responsiveImage($picture, $resimage) {
      var img;

      var callback = function () {
        img = $picture.length ? $picture[0] : $resimage[0];
        if (img) {
          if (img.complete) {
            resizeNow.call(img);
          }
          else {
            $(img).one('load', resizeNow);
          }
        }
      };
      withDelay(callback, 101);
    }

    /**
     * Resize the responsive|picture image since the library doesn't get it.
     */
    function resizeNow() {
      var t = $(this);
      var w = t.width();
      var h = t.height();
      var p = t.closest(S_LOADED_CONTENT);
      var pw = p.width();
      var ph = p.height();
      var o;

      if (h > ph) {
        t.css('top', -(h - ph) / 2);
      }
      else if (h < ph) {
        t.css({
          height: ph,
          width: 'auto'
        });
        t.css('left', -(t.width() - pw) / 2);
      }
      else if (pw > w) {
        o = dimension(w, h);
        resize(o);
      }
    }

    function withDelay(cb, delay) {
      _win.setTimeout(cb, delay || 0);
    }

    // Instagram oEmbed takes time to make iframes, deferred to onload.
    function instagram($iframe, o) {
      var callback = function () {
        var cb = function (obj) {
          if (obj.width > 180) {
            o = dimension(obj.width + 'px', obj.height + 'px');
          }

          resize(o);
        };

        FN_INSTAGRAM.show(cb, $iframe[0]);
      };
      withDelay(callback, 101);
    }

    // Padding hack container to make it responsive.
    function hackContainer($container, $iframe, o) {
      $iframe.attr('width', o.width)
        .attr('height', o.height);

      var pad = _d.image.ratio(o) + '%';

      $container.css(hack(pad, 0))
        .addClass(C_MEDIA_RATIO);
    }

    /**
     * Resize the colorbox if any of media types (video, picture, etc.) kick in.
     */
    function resizeBox() {
      var mw = CBOX_SETTINGS.maxWidth;
      var mh = CBOX_SETTINGS.maxHeight;
      var w = (usePaddingHack ? media.width : media.owidth) || mw;
      var h = usePaddingHack ? media.height : mh;
      var o = dimension(w, h);
      var shouldResize = true;
      var $container = $(S_LOADED_CONTENT);
      var container = $container[0];
      var $iframe = $('iframe', container);
      var $media = $('.media', container);
      var $picture = $container.find('picture img');
      var $resimage = $container.find('img[srcset]');
      var isResimage = $resimage.length || $picture.length;
      var isInstagramApi = $media.hasClass('b-instagram') && FN_INSTAGRAM;

      if ($media.length) {
        Drupal.attachBehaviors($media[0]);
      }

      if (isResimage) {
        responsiveImage($picture, $resimage);

        w = mw || media.width;
        h = mh || media.height;
        o = dimension(w, h);
      }
      else if (isPinterest) {
        w = '320px';
        h = mh;
        o = dimension(w, h);
      }

      // @todo consider to not use colorbox iframe for consistent .media,
      // and avoid complication given Instagram oEmbed vs. VEF.
      // Instagram dynamic iframe only available after being attached.
      $iframe = $('iframe', container);
      if ($iframe.length) {
        $iframe.addClass('media__element');

        if (isInstagramApi) {
          shouldResize = false;

          instagram($iframe, o);
        }
        else {
          if (!usePaddingHack) {
            $iframe.on('load', function () {
              var $ifrm = $(this);
              var callback = function () {
                w = $ifrm.width() + 'px';
                h = $ifrm.height() + 'px';
                o = dimension(w, h);
                resize(o);
              };
              withDelay(callback);
            });
          }
        }

        // Padding hack to make responsive iframe, unless disabled.
        if (!$media.length) {
          $container.addClass(C_MEDIA_BOX + ' media--' + provider);

          if (usePaddingHack) {
            hackContainer($container, $iframe, o);
          }
        }
      }
      else {
        $container.css(hack('', o.height))
          .removeClass(C_MEDIA_RATIO + ' media--' + provider);
      }

      if (shouldResize) {
        resize(o);
      }
    }

    $box.colorbox($.extend({}, CBOX_SETTINGS, runtimeOptions));
    $box.addClass(C_MOUNTED);
  }

  /**
   * Attaches blazy colorbox behavior to HTML element.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.blazyColorbox = {
    attach: function (context) {

      // Disable Colorbox for small screens.
      if (_d.isUnd(CBOX_SETTINGS) ||
        CBOX_SETTINGS.mobiledetect &&
        _d.matchMedia(CBOX_SETTINGS.mobiledevicewidth)) {
        return;
      }

      var elms = _d.once(process, ID_ONCE, S_ELEMENT, context);
      if (elms.length) {
        $(S_ROOT).attr('aria-label', 'color box')
          .addClass(B_ROOT);
      }
    },
    detach: function (context, setting, trigger) {
      if (trigger === 'unload') {
        _d.once.removeSafely(ID_ONCE, S_ELEMENT, context);
      }
    }
  };

})(jQuery, dBlazy, Drupal, drupalSettings, this, this.document);
