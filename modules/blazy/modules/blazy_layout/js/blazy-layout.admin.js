/**
 * @file
 * Provides Blazy layout utilities.
 */

(function ($, Drupal, _doc) {

  'use strict';

  var ID = 'b-layout';
  var ID_ONCE = ID;
  var C_MOUNTED = 'is-' + ID_ONCE;
  var S_BASE = '.' + ID;
  var S_ELEMENT = S_BASE + ':not(.' + C_MOUNTED + ')';

  /**
   * Processes a blazy layout form.
   *
   * @param {HTMLElement} elm
   *   The container HTML element.
   */
  function process(elm) {
    var dataset = elm.dataset.bLayout;
    var data;

    var subprocess = function (obj) {
      var css = obj.style;
      var styleId = obj.id + '-style';
      var el = $.find(_doc, '#' + styleId);

      if (!el) {
        el = _doc.createElement('style');
        el.id = styleId;
        el.textContent = css;
        _doc.head.appendChild(el);
      }
      else {
        el.textContent = css;
      }
    };

    if (dataset) {
      data = $.parse(atob(dataset));
      if (data.style) {
        subprocess(data);
      }
    }

    $.addClass(elm, C_MOUNTED);
  }

  /**
   * Attaches Blazy behavior to HTML element.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.blazyLayoutAdmin = {
    attach: function (context) {

      $.once(process, ID_ONCE, S_ELEMENT, context);

    },
    detach: function (context, setting, trigger) {
      if (trigger === 'unload') {
        $.once.removeSafely(ID_ONCE, S_BASE, context);
      }
    }
  };

}(dBlazy, Drupal, this.document));
