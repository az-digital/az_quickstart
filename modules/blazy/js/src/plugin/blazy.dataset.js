/**
 * @file
 * Provides non-reusable methods due to being too specific for Blazy.
 *
 * Required only by old data-[SRC|SCRSET] approach, bio and blazy.load.
 * Not required by pure Native without data-[SRC|SCRSET].
 *
 * @internal
 *   This is an internal part of the Blazy system and should only be used by
 *   blazy-related code in Blazy sub-modules.
 *   It is extending dBlazy as a separate plugin.
 */

(function ($) {

  'use strict';

  /**
   * Map attributes from data-BLAH to BLAH, and remove data-BLAH if so required.
   *
   * @private
   *
   * @param {dBlazy|Array.<Element>|Element} els
   *   The element(s), or dBlazy instance.
   * @param {String|Array} attr
   *   The attr name, or string array.
   * @param {Bool} remove
   *   True if should remove the original/ temporary holder.
   *
   * @return {Object}
   *   This dBlazy object.
   */
  function mapAttr(els, attr, remove) {
    var chainCallback = function (el) {
      if ($.isElm(el)) {
        var _mapAttr = function (name) {
          var dataAttr = 'data-' + name;

          if ($.hasAttr(el, dataAttr)) {
            var value = $.attr(el, dataAttr);
            $.attr(el, name, value);

            if (remove) {
              $.removeAttr(el, dataAttr);
            }
          }
        };

        $.each($.toArray(attr), _mapAttr);
      }
    };

    return $.chain(els, chainCallback);
  }

  /**
   * A simple attributes wrapper, looping based on sources (picture/ video).
   *
   * @private
   *
   * @param {dBlazy|Array.<Element>|Element} els
   *   The element(s), or dBlazy instance.
   * @param {String} attr
   *   The attr name, can be SRC or SRCSET.
   * @param {Bool} remove
   *   True if should remove.
   * @param {Bool} withVideo
   *   Native lazy doesn't support VIDEO as per 2022/1, exclude till required.
   *
   * @return {Object}
   *   This dBlazy object.
   */
  function mapSource(els, attr, remove, withVideo) {
    if ($.isUnd(withVideo)) {
      withVideo = true;
    }
    var chainCallback = function (el) {
      if ($.isElm(el)) {
        var parent = el.parentNode;
        var isPicture = $.equal(parent, 'picture');
        var cn = null;

        if (withVideo) {
          cn = isPicture ? parent : el;
        }
        else {
          if (isPicture) {
            cn = parent;
          }
        }

        if ($.isElm(cn)) {
          var elms = cn.getElementsByTagName('source');

          attr = attr || (isPicture ? 'srcset' : 'src');
          if (elms.length) {
            mapAttr(elms, attr, remove);
          }
        }
      }
    };

    return $.chain(els, chainCallback);
  }

  $.mapAttr = mapAttr;
  $.fn.mapAttr = function (attr, remove) {
    return mapAttr(this, attr, remove);
  };

  $.mapSource = mapSource;
  $.fn.mapSource = function (attr, remove, withVideo) {
    return mapSource(this, attr, remove, withVideo);
  };

})(dBlazy);
