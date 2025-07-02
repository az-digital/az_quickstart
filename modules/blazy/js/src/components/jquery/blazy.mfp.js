/**
 * @file
 * Provides MagnificPopup integration for Image and Media fields.
 *
 * Zoom only works for plain old image, not responsive ones.
 */

(function ($, Drupal, _doc) {

  'use strict';

  var JQ = jQuery;
  var ID = 'mfp';
  var ID_ONCE = 'b-' + ID;
  var DATA_ID = 'data-' + ID;
  var C_MOUNTED = 'is-' + ID_ONCE;
  var S_ELEMENT = '[' + DATA_ID + '-gallery]:not(.' + C_MOUNTED + ')';
  var S_TRIGGER = '[' + DATA_ID + '-trigger]';
  var D_BLAZY = Drupal.blazy || {};
  var FN_SANITIZER = $.sanitizer;
  var CAN_ZOOM = true;

  function build(elms) {
    var items = [];
    var total = elms.length;

    $.each(elms, function (el, i) {
      var media = $.parse($.attr(el, 'data-b-media'));
      var caption = el.nextElementSibling;
      var validCaption = caption && $.hasClass(caption, 'litebox__caption');
      var url = $.attr(el, 'href');
      var item = {
        el: JQ(el),
        index: i
      };
      var boxType = item.boxType = media.boxType;
      var src;
      var style = '';
      var width = media.width;
      var useWidth = false;

      if (boxType === 'image') {
        src = url;
        item.type = 'image';

        if (validCaption) {
          item.title = FN_SANITIZER.sanitize(caption.innerHTML);
        }
      }
      else {
        // (Responsive|Picture) image, local video.
        if ('html' in media) {
          useWidth = boxType === 'video';
          var html = media.html;

          // If encoded, then decode it.
          if (media.encoded) {
            html = atob(html);
          }

          src = FN_SANITIZER.sanitize(html);
          item.type = 'inline';
        }
        else if (boxType === 'iframe') {
          useWidth = true;
          src = Drupal.theme('blazyMedia', {
            el: el
          });
          item.type = 'inline';
        }

        if (src) {
          if (width && useWidth) {
            style = ' style="width:' + width + 'px;"';
          }

          src = '<div class="mfp-html mfp-html--' + boxType + '"' + style + '><div class="mfp-inner">' + src;
          if (validCaption) {
            src += '<div class="mfp-bottom-bar"><div class="mfp-title">' + FN_SANITIZER.sanitize(caption.innerHTML) + '</div>' + counter((i + 1) + '/' + total) + '</div>';
          }
          src += '</div></div>';
        }
      }

      if (src) {
        item.src = src;
      }

      items.push(item);
    });
    return items;
  }

  function counter(text) {
    return '<div class="mfp-counter">' + text + '</div>';
  }

  // Required by zoom.
  function checkImage(mp, add, link) {
    var $img;
    var content = mp.content;
    var $fallback;

    if (content && content.length) {
      var el = content[0];
      var img = $.find(el, 'img');
      var exists = $.isElm(img);

      if (!exists) {
        var vid = $.find(el, 'video');
        if ($.isElm(vid)) {
          var poster = $.attr(vid, 'poster');
          if (poster) {
            img = _doc.createElement('img');
            img.decoding = 'async';
            img.src = poster;
          }
        }
      }

      exists = $.isElm(img);
      if (exists) {
        $img = mp.currItem.img = JQ(img);
        // mp.currItem.type = 'image';
        mp.currItem.hasSize = exists;
      }

      if (add) {
        if ($.hasClass(el, 'media media-wrapper mfp-html')) {
          attach(el, true);
        }
      }
    }

    if (link && link.img) {
      $fallback = JQ(link.img);
    }
    return $img || $fallback;
  }

  function attach(el, op) {
    var $media = $.hasClass(el, 'media') ? el : $.find(el, '.media');
    if ($.isElm($media)) {
      Drupal.detachBehaviors($media);

      if (op) {
        setTimeout(function () {
          Drupal.attachBehaviors($media);

          if (D_BLAZY) {
            D_BLAZY.load($media);
          }
        });
      }
    }
  }

  /**
   * Blazy MagnificPopup utility functions.
   *
   * @param {HTMLElement} box
   *   The [data-mfp-gallery] container HTML element.
   */
  function process(box) {
    var elms = $.findAll(box, S_TRIGGER);
    var items = build(elms);
    var $box = $(box);
    var FN_INSTANCE;

    function prepare() {
      $box.magnificPopup({
        delegate: S_TRIGGER,
        type: 'image',
        closeBtnInside: false,
        gallery: {
          enabled: elms.length > 1,
          navigateByImgClick: true,
          tCounter: '%curr%/%total%'
        },
        preloader: true,
        callbacks: {
          beforeClose: function () {
            var currItem = this.currItem;
            if (currItem && currItem.inlineElement) {
              attach(currItem.inlineElement[0]);
            }
          },
          change: function () {
            FN_INSTANCE = this;
            checkImage(this, true);
          },
          open: function () {
            FN_INSTANCE = this;
            var $wrap = this.wrap;
            if ($wrap && $wrap.length) {

              // FOUC fix.
              setTimeout(function () {
                $.addClass($wrap[0], 'mfp-on');
                if (D_BLAZY.load) {
                  D_BLAZY.load($wrap[0]);
                }
              }, 100);
            }
          },
          elementParse: function (item) {
            var delta = item.index;
            var content = items[delta];
            var type = content.type;

            if (content) {
              if (type) {
                item.type = type;
                if (type === 'inline') {
                  item.img = null;
                }
              }
              if (content.src) {
                item.src = content.src;
              }
            }
          }
        },

        // This class is for CSS animation below.
        // Class to remove default margin from left and right side.
        mainClass: 'mfp-img-mobile mfp-with-zoom',
        // If you enable allowHTMLInTemplate -
        // make sure your HTML attributes are sanitized if they can be created
        // by a non-admin user.
        allowHTMLInTemplate: true,
        image: {
          verticalFit: true,
          titleSrc: function (item) {
            var delta = item.index;
            var content = items[delta];

            if (content && content.title) {
              return content.title;
            }
            return '';
          }
        },

        // Zoom requires anything which has image: (local|remote) video, etc.
        // @todo figure out to disable zoom when having plain HTML or AJAX.
        zoom: {
          enabled: CAN_ZOOM,
          duration: 300,
          easing: 'ease-in-out',

          // The "opener" function should return the element from which popup
          // will be zoomed in and to which popup will be scaled down
          // By default it looks for an image tag:
          opener: function (openerElement) {
            var img = checkImage(FN_INSTANCE, false, openerElement);
            if (img && img.length) {
              return img;
            }

            // openerElement is the element on which popup was initialized, in
            // this case its <a> tag you don't need to add "opener" option if
            // this code matches your needs, it's default one.
            // @fixme only works at first launch, not when zoom-close repeated.
            return JQ(openerElement.data.el);
          }
        }
      });
    }

    prepare();

    $.addClass(box, C_MOUNTED);
  }

  /**
   * Attaches blazy magnific popup behavior to HTML element.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.blazyMagnificPopup = {
    attach: function (context) {

      // Converts jQuery.magnificPopup into dBlazy for consistent vanilla JS.
      $.wwoBigPipe(function () {
        if (JQ && $.isFun(JQ.fn.magnificPopup) && !$.isFun($.fn.magnificPopup)) {
          var _mfp = JQ.fn.magnificPopup;

          $.fn.magnificPopup = function (options) {
            var me = $(_mfp.apply(this, arguments));

            if ($.isUnd($.magnificPopup)) {
              $.magnificPopup = JQ.magnificPopup;
            }

            return me;
          };
        }

        $.once(process, ID_ONCE, S_ELEMENT, context);
      });

    },
    detach: function (context, setting, trigger) {
      if (trigger === 'unload') {
        $.once.removeSafely(ID_ONCE, S_ELEMENT, context);
      }
    }

  };

}(dBlazy, Drupal, this.document));
