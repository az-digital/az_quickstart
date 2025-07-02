/**
 * @file
 * Provides CSS DOM methods which can replaced by Cash, or alike when available.
 *
 * Warning! Do not call or use any of the internal methods except for internal
 * usages. This file is separated to be removed when Cash is available, and
 * adoptable, or when core has one.
 *
 * @internal
 *   This is an internal part of the Blazy system and should only be used by
 *   blazy-related code in Blazy module, or its sub-modules.
 *   This file is an experiment, and subject to removal when Cash lands, or
 *   similar vanilla alternative is available at core. The rule is don't load
 *   anything unless required by the page. Another reason for components.
 *   It is extending dBlazy as a separate plugin to mimick jQuery CSS method.
 *
 * @todo https://caniuse.com/dom-manip-convenience
 * Includes: ChildNode.before, ChildNode.after, ChildNode.replaceWith,
 * ParentNode.prepend, and ParentNode.append.
 */

(function ($, _win, _doc) {

  'use strict';

  var PROTO_SOME = Array.prototype.some;
  var ADD = 'add';
  var REMOVE = 'remove';
  var CLASS = 'class';
  var WIDTH = 'width';
  var HEIGHT = 'height';
  var AFTER = 'after';
  var BEFORE = 'before';
  var BEGIN = 'begin';
  var END = 'end';
  var U_TOP = 'Top';
  var U_LEFT = 'Left';
  var U_HEIGHT = 'Height';
  var U_WIDTH = 'Width';
  var SCROLL = 'scroll';

  function css(els, props, vals) {
    var me = this;
    var _undefined = $.isUnd(vals);
    var _obj = $.isObj(props);
    var _getter = !_obj && _undefined;

    // Getter.
    if (_getter && $.isStr(props)) {
      // @todo figure out multi-element getters. Ok for now, as hardly multiple.
      var el = $.toElm(els);
      // @todo re-check common integer.
      var arr = [WIDTH, HEIGHT, 'top', 'right', 'bottom', 'left'];
      var result = $.computeStyle(el, props);
      var num = $.toInt(result, 0);
      return arr.indexOf(props) === -1 ? result : num;
    }

    var chainCallback = function (el) {
      if (!$.isElm(el)) {
        return _getter ? '' : me;
      }

      var setVal = function (val, prop) {
        // Setter.
        if ($.isFun(val)) {
          val = val();
        }

        if ($.contains(prop, '-') || $.isVar(prop)) {
          prop = $.camelCase(prop);
        }

        el.style[prop] = $.isStr(val) ? val : val + 'px';
      };

      // Passing a key-value pair object means setting multiple attributes once.
      if (_obj) {
        $.each(props, setVal);
      }
      // Since a css value null makes no sense, assumes nullify.
      else if ($.isNull(vals)) {
        $.each($.toArray(props), function (prop) {
          el.style.removeProperty(prop);
        });
      }
      else {
        // Else a setter.
        if ($.isStr(props)) {
          setVal(vals, props);
        }
      }
    };

    return $.chain(els, chainCallback);
  }

  function offset(el) {
    var rect = $.rect(el);

    return {
      top: (rect.top || 0) + _doc.body[SCROLL + U_TOP],
      left: (rect.left || 0) + _doc.body[SCROLL + U_LEFT]
    };
  }

  function width(el, val) {
    return css(el, WIDTH, val);
  }

  function height(el, val) {
    return css(el, HEIGHT, val);
  }

  function outerDim(el, withMargin, prop) {
    var result = 0;

    if ($.isElm(el)) {
      result = el['offset' + prop];
      if (withMargin) {
        var style = $.computeStyle(el);
        var margin = function (pos) {
          return $.toInt(style['margin' + pos], 0);
        };
        if (prop === U_HEIGHT) {
          result += margin(U_TOP) + margin('Bottom');
        }
        else {
          result += margin(U_LEFT) + margin('Right');
        }
      }
    }
    return result;
  }

  function outerWidth(el, withMargin) {
    return outerDim(el, withMargin, U_WIDTH);
  }

  function outerHeight(el, withMargin) {
    return outerDim(el, withMargin, U_HEIGHT);
  }

  /**
   * Insert Element or string into a position relative to a target element.
   *
   * To minimize confusions with native insertAdjacent[Element|HTML].
   *
   * <!-- beforebegin -->
   * <p>
   *   <!-- afterbegin -->
   *   foo
   *   <!-- beforeend -->
   * </p>
   * <!-- afterend -->
   *
   * @param {Element} target
   *   The target Element.
   * @param {Element|string} el
   *   The element or string to insert.
   * @param {string} position
   *   The position or placement.
   *
   * @see https://developer.mozilla.org/en-US/docs/Web/API/Element/insertAdjacentElement
   * @see https://developer.mozilla.org/en-US/docs/Web/API/Element/insertAdjacentHTML
   */
  function insert(target, el, position) {
    // @todo recheck DocumentFragment if needed.
    if ($.isElm(target)) {
      var suffix = $.isElm(el) ? 'Element' : 'HTML';
      target['insertAdjacent' + suffix](position, el);
    }
  }

  function after(target, el) {
    insert(target, el, AFTER + END);
  }

  // Node.insertBefore(), similar to beforebegin, with different arguments.
  function before(target, el) {
    insert(target, el, BEFORE + BEGIN);
  }

  // Node.appendChild(), same effect as beforeend.
  function append(target, el) {
    if ($.isElm(target)) {
      if ($.isElm(el)) {
        target.appendChild(el);
      }
      else {
        insert(target, el, BEFORE + END);
      }
    }
  }

  function prepend(target, el) {
    insert(target, el, AFTER + BEGIN);
  }

  function clone(els, deep) {
    if ($.isUnd(deep)) {
      deep = true;
    }

    var chainCallback = function (el) {
      return $.isElm(el) && el.cloneNode(deep);
    };
    return $.chain(els, chainCallback);
  }

  // @todo refactor and remove after migration:
  $.css = css;
  $.offset = offset;
  $.clone = clone;
  $.after = after;
  $.before = before;
  $.append = append;
  $.prepend = prepend;
  $.width = width;
  $.height = height;
  $.outerWidth = outerWidth;
  $.outerHeight = outerHeight;

  var objs = {
    // @todo multiple css values once.
    css: function (prop, val) {
      return css(this, prop, val);
    },
    hasAttr: function (name) {
      var me = this;
      return PROTO_SOME.call(me, function (el) {
        return $.hasAttr(el, name);
      });
    },

    attr: function (attr, defValue, withDefault) {
      var me = this;
      if ($.isNull(defValue)) {
        return me.removeAttr(attr, withDefault);
      }
      return $.attr(me, attr, defValue, withDefault);
    },
    removeAttr: function (attr, prefix) {
      return $.removeAttr(this, attr, prefix);
    },
    hasClass: function (name) {
      var me = this;
      return PROTO_SOME.call(me, function (el) {
        return $.hasClass(el, name);
      });
    },
    toggleClass: function (name, op) {
      return $.toggleClass(this, name, op);
    },
    addClass: function (name) {
      return this.toggleClass(name, ADD);
    },
    removeClass: function (name) {
      var me = this;
      return arguments.length ? me.toggleClass(name, REMOVE) : me.attr(CLASS, '');
    },
    empty: function () {
      return $.empty(this);
    },
    first: function (el) {
      return $.isUnd(el) ? this[0] : el;
    },
    after: function (el) {
      return after(this[0], el);
    },
    before: function (el) {
      return before(this[0], el);
    },
    append: function (el) {
      return append(this[0], el);
    },
    prepend: function (el) {
      return prepend(this[0], el);
    },
    remove: function () {
      this.each($.remove);
    },
    closest: function (selector) {
      return $.closest(this[0], selector);
    },
    equal: function (selector) {
      return $.equal(this[0], selector);
    },
    find: function (selector, asArray) {
      return $.find(this[0], selector, asArray);
    },
    findAll: function (selector) {
      return $.findAll(this[0], selector);
      // @todo multiple sources for multiple targets.
      // return this.each(function (el) {
      // els.push(findAll(el, selector));
      // });
    },
    clone: function (deep) {
      return clone(this, deep);
    },
    computeStyle: function (prop) {
      return $.computeStyle(this[0], prop);
    },
    offset: function () {
      return offset(this[0]);
    },
    parent: function (selector) {
      return $.parent(this[0], selector);
    },
    prev: function (selector) {
      return $.prev(this[0], selector);
    },
    next: function (selector) {
      return $.next(this[0], selector);
    },
    index: function (parents) {
      return $.index(this[0], parents);
    },
    width: function (val) {
      return width(this[0], val);
    },
    height: function (val) {
      return height(this[0], val);
    },
    outerWidth: function (withMargin) {
      return outerWidth(this[0], withMargin);
    },
    outerHeight: function (withMargin) {
      return outerHeight(this[0], withMargin);
    },
    on: function (eventName, selector, cb, params, isCustom) {
      return $.on(this, eventName, selector, cb, params, isCustom, ADD);
    },
    off: function (eventName, selector, cb, params, isCustom) {
      return $.off(this, eventName, selector, cb, params, isCustom, REMOVE);
    },
    one: function (eventName, cb, isCustom) {
      return $.one(this, eventName, cb, isCustom);
    },
    trigger: function (eventName, details, param) {
      return $.trigger(this, eventName, details, param);
    }
  };

  // Merge prototypes.
  $.fn.extend(objs);

})(dBlazy, this, this.document);
