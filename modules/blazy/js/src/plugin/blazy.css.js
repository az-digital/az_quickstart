/**
 * @file
 * Provides CSS methods, wholeshale copy from Cash, not currently used/ tested.
 *
 * @internal
 *   This is an internal part of the Blazy system and should only be used by
 *   blazy-related code in Blazy module, or its sub-modules.
 *   This file is an experiment, and subject to removal when Cash lands, or
 *   similar vanilla alternative is available at core. The rule is don't load
 *   anything unless required by the page. Another reason for components.
 *   It is extending dBlazy as a separate plugin to mimick jQuery CSS method.
 */

(function ($) {

  'use strict';

  var div = $.create('div');
  var prefixedProps = {};
  var style = div.style;
  var vendorsPrefixes = ['webkit', 'moz', 'ms'];
  var numericProps = {
    animationIterationCount: true,
    columnCount: true,
    flexGrow: true,
    flexShrink: true,
    fontWeight: true,
    gridArea: true,
    gridColumn: true,
    gridColumnEnd: true,
    gridColumnStart: true,
    gridRow: true,
    gridRowEnd: true,
    gridRowStart: true,
    lineHeight: true,
    opacity: true,
    order: true,
    orphans: true,
    widows: true,
    zIndex: true
  };

  var propMap = {
    // GENERAL.
    /* eslint-disable quote-props */
    'class': 'className',
    contenteditable: 'contentEditable',

    // LABEL.
    'for': 'htmlFor',
    /* eslint-disable quote-props */

    // INPUT.
    readonly: 'readOnly',
    maxlength: 'maxLength',
    tabindex: 'tabIndex',

    // TABLE.
    colspan: 'colSpan',
    rowspan: 'rowSpan',

    // IMAGE.
    usemap: 'useMap'
  };

  /* eslint-disable no-unused-vars */
  function computeStyleInt(el, prop) {
    return $.toInt($.computeStyle(el, prop), 0) || 0;
  }
  /* eslint-disable no-unused-vars */

  function getPrefixedProp(prop, isVariable) {
    if (isVariable === void 0) {
      isVariable = $.isVar(prop);
    }

    if (isVariable) {
      return prop;
    }

    if (!prefixedProps[prop]) {
      var propCC = $.camelCase(prop);
      var propUC = '' + propCC[0].toUpperCase() + propCC.slice(1);
      var props = (propCC + ' ' + vendorsPrefixes.join(propUC + ' ') + propUC).split(' ');

      $.each(props, function (p) {
        if (p in style) {
          prefixedProps[prop] = p;
          return false;
        }
      });
    }

    return prefixedProps[prop];
  }

  function getSuffixedValue(prop, value, isVariable) {
    if (isVariable === void 0) {
      isVariable = $.isVar(prop);
    }

    return !isVariable && !numericProps[prop] && $.isNum(value) ? value + 'px' : value;
  }

  function css(prop, value) {
    if ($.isStr(prop)) {
      var isVariable_1 = $.isVar(prop);
      prop = getPrefixedProp(prop, isVariable_1);

      if (arguments.length < 2) {
        return this[0] && $.computeStyle(this[0], prop, isVariable_1);
      }
      if (!prop) {
        return this;
      }

      value = getSuffixedValue(prop, value, isVariable_1);

      return this.each(function (el) {
        if (!$.isElm(el)) {
          return;
        }

        if (isVariable_1) {
          el.style.setProperty(prop, value);
        }
        else {
          el.style[prop] = value;
        }
      });
    }

    for (var key in prop) {
      if ($.hasProp(prop, key)) {
        this.css(key, prop[key]);
      }
    }

    return this;
  }

  $.fn.css = css;

  $.fn.prop = function (prop, value) {
    if (!prop) {
      return;
    }

    if ($.isStr(prop)) {
      prop = propMap[prop] || prop;

      if (arguments.length < 2) {
        return this[0] && this[0][prop];
      }

      return this.each(function (el) {
        el[prop] = value;
      });
    }

    for (var key in prop) {
      if ($.hasProp(prop, key)) {
        this.prop(key, prop[key]);
      }
    }

    return this;
  };

  $.fn.removeProp = function (prop) {
    return this.each(function (el) {
      delete el[propMap[prop] || prop];
    });
  };

})(dBlazy);
