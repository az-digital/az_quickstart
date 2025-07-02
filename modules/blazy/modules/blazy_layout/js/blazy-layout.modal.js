/**
 * @file
 * Provides Blazy layout utilities.
 */

(function ($, Drupal, _doc) {

  'use strict';

  var BASE = 'b-layout';
  var ID = BASE + '-form';
  var ID_ONCE = ID;
  var C_MOUNTED = 'is-' + ID_ONCE;
  var S_BASE = '.form-wrapper--' + BASE;
  var S_ELEMENT = S_BASE + ':not(.' + C_MOUNTED + ')';
  var S_ACTIVE_LAYOUT = '.' + BASE + '.is-layout-builder-highlighted';
  var PREFIX_BACKGROUND = 'background_';
  var PREFIX_OVERLAY = 'overlay_';
  var PREFIX_TEXT = 'text_';
  var PREFIX_HEADING = 'heading_';
  var PREFIX_LINK = 'link_';
  var PREFIX_LINK_HOVER = 'link_hover_';
  var TRANSPARENT = 'transparent';

  // See https://developer.mozilla.org/en-US/docs/Web/API/CSSStyleSheet/addRule
  // See https://developer.mozilla.org/en-US/docs/Web/API/CSSStyleSheet/insertRule
  function addRule(stylesheet, selector, rule) {
    // This feature is no longer recommended.
    if (stylesheet.addRule) {
      stylesheet.addRule(selector, rule);
    }
    else if (stylesheet.insertRule) {
      stylesheet.insertRule(selector + ' { ' + rule + ' }', stylesheet.cssRules.length);
    }
  }

  function getSelector(id, selectors, key) {
    key = key.replace(/_+$/, '');

    if (selectors) {
      var selector = selectors[key];

      if (selector) {
        id = '.blazy.' + id;
        selector = id + ' ' + selector;

        if ($.contains(selector, ',')) {
          selector = selector.replaceAll(',', ', ' + id);
        }
      }

      return selector;
    }
    return '';
  }

  function toRgba(color, alpha) {
    if (!$.isUnd(alpha)) {
      if (Math.abs(alpha) === 0) {
        return TRANSPARENT;
      }
      else if (Math.abs(alpha) === 1) {
        return color === '#000000' || color === '#000' ? TRANSPARENT : color;
      }
      return 'rgba(' + parseInt(color.slice(-6, -4), 16) + ',' + parseInt(color.slice(-4, -2), 16) + ',' + parseInt(color.slice(-2), 16) + ',' + alpha + ')';
    }
    return color;
  }

  /**
   * Processes a blazy layout modal form.
   *
   * @param {HTMLElement} elm
   *   The container HTML element.
   */
  function process(elm) {
    var colors = $.findAll(elm, 'input[type="color"]');
    var ranges = $.findAll(elm, 'input[type="range"]');

    var is = function (el, prefix) {
      return $.contains(el.name, prefix);
    };

    var updateValue = function (el) {
      if (el.nextElementSibling) {
        el.nextElementSibling.textContent = el.value;
      }
    };

    var makeRgba = function (el) {
      var cn = el.parentNode;
      var sibling;
      var input;
      var hex;

      if (el.type === 'range') {
        sibling = cn.previousElementSibling;
        input = $.find(sibling, 'input[type="color"]');

        if (input) {
          hex = input.value;
          return {
            prop: input.dataset.bProp,
            value: toRgba(hex, el.value)
          };
        }
      }
      else if (el.type === 'color') {
        sibling = cn.nextElementSibling;
        input = $.find(sibling, 'input[type="range"]');

        if (input) {
          hex = el.value;
          return {
            prop: el.dataset.bProp,
            value: toRgba(hex, input.value)
          };
        }
      }
      return {};
    };

    var updateStyle = function (id, el, region) {
      var styleId = id + '-style';
      var elSheet = $.find(_doc, '#' + styleId);

      if (!elSheet) {
        return;
      }

      var sheet = elSheet.sheet;
      // var rules = sheet.cssRules || sheet.rules;
      var selectors;
      var selector;
      var prop = el.dataset.bProp;
      var value = el.value;
      var color = makeRgba(el);
      var rule;

      if (region.dataset && region.dataset.bSelector) {
        selectors = $.parse(region.dataset.bSelector);
      }

      if (is(el, PREFIX_BACKGROUND)) {
        selector = getSelector(id, selectors, PREFIX_BACKGROUND);
      }
      else if (is(el, PREFIX_OVERLAY)) {
        selector = getSelector(id, selectors, PREFIX_OVERLAY);
      }
      else if (is(el, PREFIX_TEXT)) {
        selector = getSelector(id, selectors, PREFIX_TEXT);
      }
      else if (is(el, PREFIX_HEADING)) {
        selector = getSelector(id, selectors, PREFIX_HEADING);
      }
      else if (is(el, PREFIX_LINK_HOVER)) {
        selector = getSelector(id, selectors, PREFIX_LINK_HOVER);
      }
      else if (is(el, PREFIX_LINK)) {
        selector = getSelector(id, selectors, PREFIX_LINK);
      }

      if (sheet && selector) {
        if (color.value) {
          prop = color.prop;
          value = color.value;
        }

        rule = prop + ':' + value;
        addRule(sheet, selector, rule);
      }
    };

    var onChange = function () {
      var el = this;
      var region;
      var rid;
      var formRegion;
      var layout;
      var id;

      updateValue(el);

      setTimeout(function () {
        formRegion = $.closest(el, '[data-b-region]');
        layout = $.find(_doc, S_ACTIVE_LAYOUT);

        if (!layout) {
          return;
        }

        id = layout.id;
        if (formRegion) {
          rid = formRegion.dataset.bRegion;
          region = $.find(layout, '[data-region="' + rid + '"]');

          if (region) {
            updateStyle(id, el, region);
          }
        }
      });
    };

    var subprocess = function (elms) {
      $.each(elms, function (el) {
        updateValue(el);

        $.on(el, 'change.' + ID, onChange);
      });
    };

    subprocess(colors);
    subprocess(ranges);

    $.addClass(elm, C_MOUNTED);
  }

  /**
   * Attaches Blazy behavior to HTML element.
   *
   * @type {Drupal~behavior}
   */
  Drupal.behaviors.blazyLayoutModal = {
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
