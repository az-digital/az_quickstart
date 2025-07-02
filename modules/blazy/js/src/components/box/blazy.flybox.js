/**
 * @file
 * Provides a Flybox, a non-disruptive lightbox.
 *
 * @todo provide Native Fullscreen API toggler with an optional polyfill.
 */

(function ($, Drupal, _doc) {

  'use strict';

  var ID = 'flybox';
  var ID_ITEM = 'flybx';
  var ID_ONCE = ID;
  var IS_ID = 'is-' + ID;
  var SELF_CLASS = 'b-' + ID;
  var C_BODY = IS_ID + '--open';
  var C_BODY_CLOSING = IS_ID + '--closing';
  var C_MOUNTED = IS_ID;
  var DATA_ID = 'data-' + ID;
  var S_GALLERY = '[' + DATA_ID + '-gallery]:not(.' + C_MOUNTED + ')';
  var S_TRIGGER = '[' + DATA_ID + '-trigger]';

  // Public methods.
  $.flybox = {
    open: function (link) {
      if ($.isElm(link)) {
        Drupal.blazyBox.open(link,
          {
            bodyClass: C_BODY,
            bodyClosingClass: C_BODY_CLOSING,
            class: SELF_CLASS,
            fs: false
          });
      }
    }
  };

  /**
   * Launch a flybox.
   *
   * @param {Event} e
   *   The click event.
   */
  function launch(e) {
    e.preventDefault();
    e.stopPropagation();

    var target = e.target;
    var link = target.href ? target : $.closest(target, S_TRIGGER);
    $.flybox.open(link);
  }

  /**
   * Flybox utility functions.
   *
   * @param {HTMLElement} el
   *   The flybox gallery HTML element.
   */
  function process(el) {
    $.on(el, 'click.' + ID, S_TRIGGER, launch);
    $.addClass(el, C_MOUNTED);
  }

  /**
   * Trigger click on a flybox link.
   *
   * @param {HTMLElement} el
   *   The triggering element of flybox.
   */
  function subprocess(el) {
    $.on(el, 'click.' + ID, launch);
  }

  /**
   * Attaches flybox behavior to HTML element.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.flyBox = {
    attach: function (context) {

      $.ready(function () {
        // @todo remove to decouple from containing gallery.
        var items = $.once(process, ID_ONCE, S_GALLERY, context);

        // Allows flybox embedded inside another gallery for mixed galleries.
        if (!items.length) {
          items = $.findAll(_doc, S_TRIGGER);
          if (items.length) {
            $.once(subprocess, ID_ITEM, items, context);
          }
        }
      });

    },
    detach: function (context, setting, trigger) {
      if (trigger === 'unload') {
        $.once.removeSafely(ID_ONCE, S_GALLERY, context);
      }
    }
  };

})(dBlazy, Drupal, this.document);
