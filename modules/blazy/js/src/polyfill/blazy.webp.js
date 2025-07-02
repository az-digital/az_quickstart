/**
 * @file
 * Provides a few disposable polyfills till IE is gone from planet earth.
 *
 * Supports for webp is landed at D9.2. This file relies on core/picturefill
 * which is always included as core/responsive_image polifyll as per 2022/2.
 * This file is a client-side solution, with advantage clean native image markup
 * since it doesn't change IMG into PICTURE till required by old browsers, as
 * alternative for HTML/ server-side solutions:
 *   - https://www.drupal.org/project/webp
 *   - https://www.drupal.org/project/imageapi_optimize_webp
 *
 * @see https://www.drupal.org/node/3171135
 * @see https://www.drupal.org/project/drupal/issues/3213491
 * @todo remove if picturefill suffices. FWIW, IE9 works fine with picturefill
 * w/o this fallback. Not tested against other oldies, Safari, etc. So included,
 * but can be ditched as usual via Blazy UI if not needed at all.
 */

(function ($, _win, _doc) {

  'use strict';

  var KEY_STORAGE = 'bwebp';
  var DATA_SRCSET = 'data-srcset';
  var PICTURE = 'picture';
  var MIME_WEBP = 'image/webp';
  var SOURCE = 'source';
  var FN_PF = _win.picturefill;

  function isSupported() {
    var support = true;

    // Ensures not locked down when Responsive image is not present, yet.
    // @todo use $.decode for better async.
    if (FN_PF) {
      var check = $.storage(KEY_STORAGE);

      if (!$.isNull(check)) {
        return check === 'true';
      }

      // Undefined means supported, due to !FN_PF.supPicture check.
      support = $.isUnd(FN_PF._.supportsType(MIME_WEBP));
      $.storage(KEY_STORAGE, support);
    }

    return support;
  }

  function markup(img, webps, nowebps, dataset) {
    if (!$.isElm(img)) {
      return false;
    }
    var picture = $.create(PICTURE);
    var source = $.create(SOURCE);
    var sizes = $.attr(img, 'sizes');
    var webpSrc = webps.join(',').trim();
    var nowebpSrc = nowebps.join(',').trim();
    var check = $.find(picture, SOURCE);

    if (!$.isElm(check)) {
      if (dataset) {
        $.attr(source, DATA_SRCSET, webpSrc);
        $.attr(img, DATA_SRCSET, nowebpSrc);
      }
      else {
        source.srcset = webpSrc;
        img.srcset = nowebpSrc;
      }

      if (sizes) {
        source.sizes = sizes;
      }

      source.type = MIME_WEBP;

      $.append(picture, source);
      $.append(picture, img);
    }

    return picture;
  }

  function convert(el) {
    var img = _doc.importNode(el, true);
    var webps = [];
    var nowebps = [];
    var dataset = $.attr(img, DATA_SRCSET);
    var scrset = $.attr(img, 'srcset');

    if (scrset.length || dataset.length) {
      scrset = scrset.length ? scrset : dataset;

      if (scrset.length) {
        $.each(scrset.split(','), function (src) {
          if ($.contains(src, '.webp')) {
            webps.push(src);
          }
          else {
            nowebps.push(src);
          }
        });

        if (webps.length) {
          return markup(img, webps, nowebps, dataset.length);
        }
      }
    }
    return false;
  }

  $.webp = {
    isSupported: isSupported,

    run: function (elms) {
      if (isSupported() || !elms.length) {
        return;
      }

      $.each(elms, function (el) {
        var isImg = $.equal(el, 'img');
        var pic = $.closest(el, PICTURE);

        if (isImg && $.isNull(pic)) {
          var parent = $.closest(el, '.media') || el.parentNode;
          var picture = convert(el, true);

          if (picture) {
            // Cannot use parent.replaceWith because this is for old browsers.
            // Nor parent.replaceChild(picture, el); due to various features.
            $.append(parent, picture);
            $.remove(el);
          }
        }
      });
    }
  };

})(dBlazy, this, this.document);
