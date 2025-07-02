/**
 * @file
 * Provides Filter module integration.
 */

(function ($, Drupal, _doc) {

  'use strict';

  var ID = 'blazy';
  var ID_ONCE = 'b-filter';
  var C_WRAPPER = 'media-wrapper--' + ID;
  var C_MOUNTED = 'is-' + ID_ONCE;
  var S_ELEMENT = '.' + C_WRAPPER + ':not(.grid .' + C_WRAPPER + '):not(.' + C_MOUNTED + ')';
  var DATA = 'data-';

  /**
   * Adds blazy container attributes required for grouping, or by lightboxes.
   *
   * @param {HTMLElement} elm
   *   The .media-wrapper--blazy HTML element.
   */
  function process(elm) {
    var cn = $.closest(elm, '.text-formatted') || $.closest(elm, '.field');
    if (!$.isElm(cn) || $.hasClass(cn, ID)) {
      return;
    }

    var $cn = $(cn);
    var $nonShortcode = $cn.find('.media-wrapper--blazy');

    // Only enable for non-shotcode due to not having a container. With
    // shortcodes, the required .blazy container is there.
    if (!$.isElm($nonShortcode)) {
      return;
    }

    $cn.addClass(ID)
      .attr(DATA + ID, '');

    // Not using elm is fine since this should be executed once.
    // Basicallly this makes the lightbox gallery available at inline images
    // by taking the first found `data-media` to determine the lightbox id.
    // Originally using PHP loop over filters, but more efficient with client.
    var box = $cn.find('.litebox');
    if ($.isElm(box)) {
      var media = $.parse($.attr(box, DATA + 'media'));
      if ('id' in media) {
        var mid = media.id;
        $cn.addClass(ID + '--' + mid)
          .attr(DATA + mid + '-gallery', '');
      }
    }

    $.addClass(elm, C_MOUNTED);
  }

  /**
   * Attaches Blazy filter behavior to HTML element.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.blazyFilter = {
    attach: function (context) {
      $.once(process, ID_ONCE, S_ELEMENT, context);
    },
    detach: function (context, setting, trigger) {
      if (trigger === 'unload') {
        $.once.removeSafely(ID_ONCE, S_ELEMENT, context);
      }
    }
  };

})(dBlazy, Drupal, this.document);
