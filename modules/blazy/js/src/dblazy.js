/**
 * @file
 * This file contains common jQuery replacement methods for vanilla ones to DRY.
 *
 * Cherries by @toddmotto, @cferdinandi, @adamfschwartz, @daniellmb, Cash,
 * underscore.
 *
 * Some dup wrappers are meant to DRY with null checks aka poorman null safety.
 * The rest are convenient to avoid object instantiation ($()) and to preserve
 * old behaviors pre Blazy 2.6 till all codebase are migrated as needed.
 * A few dups are still valid for single vs. chained element loop or queries.
 *
 * @todo use Cash for better DOM queries, or any core libraries when available.
 * @todo remove unneeded dup methods once all codebase migrated.
 * @todo move more DOM methods into blazy.dom.js to make it ditchable for Cash.
 * @todo when IE gone, https://caniuse.com/dom-manip-convenience
 * @todo remove all min files at D10, see https://www.drupal.org/node/3305725
 */

/* global define */
(function (_win, _doc, _ds) {

  'use strict';

  var NAME = 'dblazy';
  var EXTEND = Object.assign;
  var PROTO_A = Array.prototype;
  var PROTO_O = Object.prototype;
  var PROTO_TOSTRING = PROTO_O.toString;
  var PROTO_SPLICE = PROTO_A.splice;
  var PROTO_SOME = PROTO_A.some;
  var V_SYMBOL = typeof Symbol !== 'undefined' && Symbol;
  var C_TOUCH = 'touchevents';
  var IS_JQ = 'jQuery' in _win;
  var IS_CASH = 'cash' in _win;
  var V_CLASS = 'class';
  var V_ADD = 'add';
  var V_REMOVE = 'remove';
  var V_HAS = 'has';
  var V_GET = 'get';
  var V_SET = 'set';
  var V_WIDTH = 'width';
  var U_WIDTH = 'Width';
  var V_CLIENTWIDTH = 'client' + U_WIDTH;
  var E_SCROLL = 'scroll';
  var V_ITERATOR = 'iterator';
  var S_OBSERVER = 'Observer';
  var E_LISTENER = 'EventListener';
  var S_BODY = 'body';
  var S_HTML = 'html';
  var RE_DASH_ALPHA = /-([a-z])/g;
  var RE_CSS_VARIABLE = /^--/;
  var STORAGE = _win.localStorage;
  var EVENTS = {};
  // The largest integer that can be represented exactly.
  var MAX_ARRAY_INDEX = Math.pow(2, 53) - 1;
  var DB;
  var FN;

  /**
   * Object for public APIs where dBlazy stands for drupalBlazy.
   *
   * @namespace
   *
   * @return {dBlazy}
   *   Returns this instance.
   */
  var dBlazy = function () {
    function dBlazy(selector, ctx) {
      var me = this;

      me.name = NAME;

      if (!selector) {
        return;
      }

      if (isMe(selector)) {
        return selector;
      }

      var els = selector;
      if (isStr(selector)) {
        els = findAll(context(ctx, selector), selector);
        if (!els.length) {
          return;
        }
      }
      else if (isFun(selector)) {
        return me.ready(selector);
      }

      if (els.nodeType || els === _win) {
        els = [els];
      }

      var len = me.length = els.length;
      for (var i = 0; i < len; i++) {
        me[i] = els[i];
      }
    }

    dBlazy.prototype.init = function (selector, ctx) {
      var instance = new dBlazy(selector, ctx);

      if (isElm(selector)) {
        if (!selector.idblazy) {
          selector.idblazy = instance;
        }
        return selector.idblazy;
      }

      return instance;
    };

    return dBlazy;
  }();

  // Cache our prototype.
  FN = dBlazy.prototype;
  // Alias instantiation for a shortcut like jQuery $(selector, context).
  DB = FN.init;
  DB.fn = DB.prototype = FN;

  FN.length = 0;

  // Ensuring a DB collection gets printed as array-like in Chrome's devtools.
  FN.splice = PROTO_SPLICE;

  // IE9 knows not this.
  if (V_SYMBOL) {
    // Ensuring a DB collection is iterable.
    // @see https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Symbol/iterator
    FN[V_SYMBOL[V_ITERATOR]] = PROTO_A[V_SYMBOL[V_ITERATOR]];
  }

  /**
   * Excecutes chainable callback to avoid unnecessary loop unless required.
   *
   * @private
   *
   * @param {!Function} cb
   *   The calback function.
   *
   * @return {Object}
   *   The current dBlazy collection object.
   *
   * @see https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Operators/Optional_chaining
   */
  function chain(cb) {
    var me = this;
    // Ok, this is insanely me.
    me = isMe(me) ? me : DB(me);
    var ln = me.length;

    if (isFun(cb)) {
      if (!ln || ln === 1) {
        cb(me[0], 0);
      }
      else {
        me.each(cb);
      }
    }

    return me;
  }

  // Similar to core domReady, only public and generic.
  function ready(callback, delay) {
    var cb = function () {
      return setTimeout(callback, delay || 0, DB);
    };

    wwoBigPipe(function () {
      if (_doc.readyState !== 'loading') {
        cb();
      }
      else {
        _doc.addEventListener('DOMContentLoaded', cb);
      }
    });

    return this;
  }

  /**
   * Returns a `toString`-based type tester, based on underscore.js.
   *
   * @private
   *
   * @param {string} name
   *   The name to test for its type.
   *
   * @return {bool}
   *   True if name matches the PROTO_TOSTRING result.
   */
  function isTag(name) {
    var tag = '[object ' + name + ']';
    return function (obj) {
      return PROTO_TOSTRING.call(obj) === tag;
    };
  }

  /**
   * Generate a function to obtain property `key` from `obj`.
   *
   * @private
   *
   * @param {string} key
   *   The key to test in an object.
   *
   * @return {mixed}
   *   String, object, undefined.
   */
  function shallowProperty(key) {
    return function (obj) {
      return isNull(obj) ? void 0 : obj[key];
    };
  }

  /**
   * Returns true if the checked property is number.
   *
   * @private
   *
   * @param {function} cb
   *   The callback to test length property.
   *
   * @return {bool}
   *   True if argument is property is number.
   */
  function checkLength(cb) {
    return function (collection) {
      var size = cb(collection);
      return typeof size === 'number' && size >= 0 && size <= MAX_ARRAY_INDEX;
    };
  }

  // Internal helper to obtain the `length` property of an object.
  var getLength = shallowProperty('length');

  /**
   * Returns true if the argument is an array-like object, NodeList, etc.
   *
   * @private
   *
   * @return {bool}
   *   True if argument is an array-like object.
   */
  var isArrayLike = checkLength(getLength);

  /**
   * Retrieve the names of an object's own properties.
   *
   * Delegates to ECMAScript 5's native `Object.keys`.
   *
   * @private
   *
   * @param {mixed} x
   *   The x to test for its properties.
   *
   * @return {array}
   *   The object keys, or empty array.
   */
  function keys(x) {
    return !isObj(x) ? [] : Object.keys(x);
  }

  /**
   * Returns true if the x is a dBlazy.
   *
   * @private
   *
   * @param {Mixed} x
   *   The x to check for its type.
   *
   * @return {bool}
   *   True if x is an instanceof dBlazy.
   */
  function isMe(x) {
    return x instanceof dBlazy;
  }

  /**
   * True if the supplied argument is an array.
   *
   * @private
   *
   * One of the weird behaviors in JavaScript is the typeof Array is Object.
   *
   * @param {Mixed} x
   *   The x to check for its type.
   *
   * @return {bool}
   *   True if the argument is an instanceof Array.
   *
   * @todo refine, like everything else.
   */
  function isArr(x) {
    // String has length.
    if (isStr(x)) {
      return false;
    }
    return x && (Array.isArray(x) || isArrayLike(x));
  }

  /**
   * Returns true if the x is a boolean.
   *
   * @private
   *
   * @param {Mixed} x
   *   The x to check for its type truthy.
   *
   * @return {bool}
   *   True if x is an instanceof bool.
   */
  function isBool(x) {
    return x === true || x === false || PROTO_TOSTRING.call(x) === '[object Boolean]';
  }

  /**
   * Returns true if the x is an Element.
   *
   * @private
   *
   * @param {Mixed} x
   *   The x to check for its type truthy.
   *
   * @return {bool}
   *   True if x is an instanceof Element.
   */
  function isElm(x) {
    return x && (x instanceof Element || x.querySelector);
  }

  /**
   * Returns true if the x is an integer.
   *
   * @private
   *
   * @param {Mixed} x
   *   The x to check for its type truthy.
   *
   * @return {bool}
   *   True if x is an integer.
   *
   * @see https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/parseInt
   * @see https://stackoverflow.com/questions/175739
   */
  function isInt(x) {
    return !isNaN(x) &&
      parseInt(Number(x)) === x &&
      !isNaN(parseInt(x, 10));
  }

  // Normally expecting 640px converted into just 640, etc.
  function toInt(x, fallback) {
    if (!isInt(x)) {
      x = parseInt(x);
    }
    return x || fallback || 0;
  }

  /**
   * Returns true if the argument is a function.
   *
   * @private
   *
   * @return {bool}
   *   True if argument is an instanceof Function.
   */
  var isFun = isTag('Function');

  /**
   * Returns true if the x is anything falsy.
   *
   * All values are truthy unless they are defined as falsy (i.e., except for
   * false, 0, -0, 0n, "", null, undefined, and NaN).
   *
   * @private
   *
   * @param {Mixed} x
   *   The x to check for its type truthy.
   *
   * @return {bool}
   *   True if null, undefined, false or empty string or array.
   *
   * @see https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Operators/Nullish_coalescing_operator
   * @see https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Operators/Logical_NOT
   */
  function isEmpty(x) {
    if (isNull(x) || isUnd(x) || x === false) {
      return true;
    }

    // Skip expensive `toString`-based checks if `obj` has no `.length`.
    var length = getLength(x);
    if (typeof length === 'number' && (isArr(x) || isStr(x))) {
      return length === 0;
    }

    return getLength(keys(x)) === 0;
  }

  /**
   * Returns true if the x is a null.
   *
   * To those curious why this very simple comparasion has a method, check
   * out the minified one. It is called 7 times here, but called once at the
   * minifid one to just 1 character + 7 (`=== null`) = 14, saving many byte
   * codes. Otherwise `=== null` x 7 chracters = 49.
   *
   * @private
   *
   * @param {Mixed} x
   *   The x to check for its type truthy.
   *
   * @return {bool}
   *   True if null.
   */
  function isNull(x) {
    return x === null;
  }

  /**
   * Returns true if the x is a number.
   *
   * @private
   *
   * @param {Mixed} x
   *   The x to check for its type truthy.
   *
   * @return {bool}
   *   True if number.
   */
  function isNum(x) {
    return !isNaN(parseFloat(x)) && isFinite(x);
  }

  /**
   * Returns true if the x is an object.
   *
   * @private
   *
   * One of the weird behaviors in JavaScript is the typeof Array is Object.
   *
   * @param {Mixed} x
   *   The x to check for its type truthy.
   *
   * @return {bool}
   *   True if x is an instanceof Object.
   */
  function isObj(x) {
    if (!x || typeof x !== 'object') {
      return false;
    }
    // var type = typeof x;
    // return type === 'function' || type === 'object' && !!x;
    var proto = Object.getPrototypeOf(x);
    return isNull(proto) || proto === PROTO_O;
  }

  /**
   * Returns true if the argument is a string, also non empty.
   *
   * @private
   *
   * @param {Mixed} x
   *   The x to check for its type string.
   *
   * @return {bool}
   *   True if argument is a string.
   */
  function isStr(x) {
    return x && typeof x === 'string';
  }

  /**
   * Returns true if the x is undefined.
   *
   * @private
   *
   * @param {Mixed} x
   *   The x to check for its type truthy.
   *
   * @return {bool}
   *   True if x is undefined.
   */
  function isUnd(x) {
    return typeof x === 'undefined';
  }

  /**
   * Returns true if the x is window.
   *
   * @private
   *
   * @param {Mixed} x
   *   The x to check for its type truthy.
   *
   * @return {bool}
   *   True if x is window.
   */
  function isWin(x) {
    return !!x && x === x.window;
  }

  /**
   * Returns true if the x is a document.
   *
   * @private
   *
   * @param {Mixed} x
   *   The x to check for its type truthy.
   *
   * @return {bool}
   *   True if x is a document.
   *
   * 1: Node.ELEMENT_NODE
   * 9: Node.DOCUMENT_NODE
   * 11: Node.DOCUMENT_FRAGMENT_NODE
   * @see https://developer.mozilla.org/en-US/docs/Web/API/Node/nodeType
   */
  function isDoc(x) {
    return [9, 11].indexOf(!!x && x.nodeType) !== -1;
  }

  /**
   * Returns true if the x is valid for querySelector.
   *
   * @private
   *
   * @param {Mixed} x
   *   The x to check for its type truthy.
   *
   * @return {bool}
   *   True if x is valid for querySelector.
   *
   * 1: Node.ELEMENT_NODE
   * 9: Node.DOCUMENT_NODE
   * 11: Node.DOCUMENT_FRAGMENT_NODE
   * @see https://developer.mozilla.org/en-US/docs/Web/API/Node/nodeType
   */
  function isQsa(x) {
    return x && (x.querySelector || [1, 9, 11].indexOf(!!x && x.nodeType) !== -1);
  }

  /**
   * Returns true if the x is valid for event listener.
   *
   * @private
   *
   * @param {Mixed} x
   *   The x to check for its type truthy.
   *
   * @return {bool}
   *   True if x is valid for event listener.
   */
  function isEvt(x) {
    return isQsa(x) || isWin(x);
  }

  /**
   * Returns true if the x is valid for attribute operations.
   *
   * Ambiguous as if expecting an attribute check, but no biggies for internals.
   * Consider it a short name for isAttributable().
   * Similar to isElm(), just a re-assuring for attributes work.
   *
   * @private
   *
   * @param {Mixed} x
   *   The x to check for its type truthy.
   *
   * @return {bool}
   *   True if x is valid for for attribute operations.
   */
  function isAttr(x) {
    return x && 'getAttribute' in x;
  }

  function isBigPipe() {
    return 'bigPipePlaceholderIds' in _ds;
  }

  // Checks if BigPipe replacement jobs are done.
  function wwoBigPipeDone() {
    if (isBigPipe()) {
      return isEmpty(_ds.bigPipePlaceholderIds);
    }
    // If BigPipe is not installed, always done.
    return true;
  }

  // Wait for BigPipe to be done before calling a function, not really once.
  // This should also avoid multiple invocations of the callback function.
  function wwoBigPipe(cb, t) {
    if (wwoBigPipeDone()) {
      // DOM ready fix.
      setTimeout(cb, t || 101);
    }
  }

  /**
   * Returns true if a touch device.
   *
   * @private
   *
   * @param {Function} cb
   *   The callback function called on matchMedia change.
   *
   * @return {bool}
   *   True if a touch device.
   */
  function isTouch(cb) {
    var query = {};

    // @todo remove check when min D.10.
    if ('matchMedia' in _win) {
      query = _win.matchMedia('(hover: none), (pointer: coarse)');
      if (cb) {
        query.addEventListener('change', cb);
      }
    }

    return (
      ('ontouchstart' in _win) ||
      (_win.DocumentTouch && _doc instanceof _win.DocumentTouch) ||
      query.matches ||
      (navigator.maxTouchPoints > 0) ||
      (navigator.msMaxTouchPoints > 0)
    );
  }

  /**
   * Dynamically add [no-]touchevents class to html.
   *
   * Basically similar to core/drupal.touchevents-test, only with change.
   */
  function touchOrNot() {
    var html = _doc.documentElement;
    var matches = isTouch(touchOrNot);

    removeClass(html, [C_TOUCH, 'no-' + C_TOUCH]);
    addClass(html, matches ? C_TOUCH : 'no-' + C_TOUCH);
  }

  /**
   * Returns an object from a NamedNodeMap.
   *
   * @private
   *
   * @param {NamedNodeMap} obj
   *   The NamedNodeMap object.
   * @param {object} scope
   *   The optional current scope.
   *
   * @return {object}
   *   The simplified iterable object.
   */
  function nodeMapAttr(obj, scope) {
    var info = {};
    if (obj && obj.length) {
      var arr = slice(obj);
      arr.forEach(function (a) {
        info[a.name] = a.value;
      }, scope || this);
    }
    return info;
  }

  /**
   * A not simple forEach() implementation for Arrays, Objects and NodeLists.
   *
   * @private
   *
   * @param {Array|Object|NodeList} obj
   *   Collection of items to iterate.
   * @param {Function} cb
   *   A function to execute for each element in the array. Its return value is
   *   discarded. The function is called with the following arguments:
   *   - element: The current element being processed in the array.
   *   - index: The index of the current element being processed in the array.
   *   - array: The array forEach() was called upon.
   *   The element and hardly used index are normally reversed by jQuery.
   * @param {Object|undefined} scope
   *   A value to use as `this` when executing cb, default to `undefined`.
   *
   * @return {Array}
   *   Returns this collection, originally `undefined`.
   *
   * @see https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Array/forEach
   * @see https://developer.mozilla.org/en-US/docs/Web/API/NodeList/forEach
   * @todo refactor, unreliable given unexpected properties.
   */
  function each(obj, cb, scope) {
    if (isFun(obj) || isStr(obj) || isBool(obj) || isNum(obj)) {
      return [];
    }

    // Filter out useless empty array.
    if (isArr(obj) && !isUnd(obj.length)) {
      var length = obj.length;
      if (!length || (length === 1 && obj[0] === ' ')) {
        return [];
      }
    }

    // Filter out useless empty object.
    if (isObj(obj) && isEmpty(obj)) {
      return [];
    }

    if (PROTO_TOSTRING.call(obj) === '[object Object]') {
      for (var prop in obj) {
        if (hasProp(obj, prop)) {
          if (prop === 'length' || prop === 'name') {
            continue;
          }
          // return false means a break, return true continue.
          if (cb.call(scope, obj[prop], prop, obj) === false) {
            break;
          }
        }
      }
    }
    else if (obj) {
      if (obj instanceof HTMLCollection) {
        obj = slice(obj);
      }

      if (obj instanceof NamedNodeMap) {
        var info = nodeMapAttr(obj, scope);
        cb.call(scope, info, 0, obj);
      }
      else {
        var len = obj.length;
        if (len && len === 1 && !isUnd(obj[0])) {
          cb.call(scope, obj[0], 0, obj);
        }
        else {
          // Assumes array, at least non-expected objs were blacklisted above.
          // [].forEach is unforgiving, that is why we filter out stupidity.
          obj.forEach(cb, scope);
        }
      }
    }

    return obj;
  }

  /**
   * A hasOwnProperty wrapper.
   *
   * @private
   *
   * @param {Array|Object|NodeList} obj
   *   Collection of items to iterate.
   * @param {string} prop
   *   The property nane.
   *
   * @return {bool}
   *   Returns true if the property found.
   */
  function hasProp(obj, prop) {
    return PROTO_O.hasOwnProperty.call(obj, prop);
  }

  /**
   * A simple wrapper for JSON.parse() for string within data-* attributes.
   *
   * @private
   *
   * @param {string} str
   *   The string to convert into JSON object.
   *
   * @return {Object}
   *   The JSON object, or empty in case invalid.
   */
  function parse(str) {
    try {
      return str.length === 0 || str === '1' ? {} : JSON.parse(str);
    }
    catch (e) {
      return {};
    }
  }

  /**
   * Converts string/ element to array.
   *
   * @private
   *
   * @param {Element|string} x
   *   The object to make array.
   *
   * @return {Array}
   *   The resulting array.
   */
  function toArray(x) {
    if (isStr(x)) {
      x = x.trim();

      // Classlist comma separated array-like, but hardly used: aaa, bbb, ccc.
      if (x.indexOf(',') !== -1) {
        return x.split(',').map(function (item) {
          return item.trim();
        });
      }

      // Regular space delimited multi-value like classes: aaa bbb ccc.
      if (/\s/.test(x)) {
        return x.split(' ').map(function (item) {
          return item.trim();
        });
      }
      return [x];
    }
    return isArr(x) ? x : [x];
  }

  function _op(el, op, name, value) {
    if (isAttr(el)) {
      return el[op + 'Attribute'](name, value);
    }
    return '';
  }

  /**
   * A forgiving attribute wrapper with fallback mimicking jQuery.attr method.
   *
   * @private
   *
   * @param {dBlazy|Array.<Element>|Element} els
   *   The HTML element(s), or dBlazy instance.
   * @param {string|Object|Array.<String>} attr
   *   The attr name, can be a string, object, or string array.
   * @param {string} defValue
   *   The default value, can be null or undefined for different intentions.
   * @param {string|bool} withDefault
   *   True if should get with defValue. Or a prefix such as data- for removal.
   *
   * @return {Object|string}
   *   The attribute value, or fallback, for getters, or this for setters.
   */
  function _attr(els, attr, defValue, withDefault) {
    var me = this;
    var _undefined = isUnd(defValue);
    var _obj = isObj(attr);
    var _getter = !_obj && (_undefined || isBool(withDefault));
    var prefix = isStr(withDefault) ? withDefault : '';

    // Ensures a single element. Some element with length is actually element.
    var elm = toElm(els);

    // Returns all available attributes, if any.
    if (isUnd(attr) && isElm(elm)) {
      return nodeMapAttr(elm.attributes);
    }

    // No defValue defined, or withDefault set, means a getter.
    if (_getter && isStr(attr)) {
      attr = attr.trim();
      if (_undefined) {
        defValue = '';
      }

      var value = defValue;
      // Ambiguous space delimited attributes: 'data-src data-lazy', etc.
      // $.attr(el, 'data-src data-lazy'); returns the first found with values.
      // $.attr(el, 'data-src', defaultValue, true); returns with default.
      // See https://caniuse.com/?search=every.
      toArray(attr).every(function (key) {
        if (_op(elm, V_HAS, key)) {
          value = _op(elm, V_GET, key);

          // Since it expects values, skip empty ones for ambigous attributes.
          if (value) {
            // return false is equivalent to a break.
            return false;
          }
        }
        // return true is equivalent to a continue.
        return true;
      });

      return value;
    }

    var chainCallback = function (el) {
      if (!isAttr(el)) {
        return _getter ? '' : me;
      }

      // Passing a key-value pair object means setting multiple attributes once.
      if (isObj(attr)) {
        each(attr, function (value, key) {
          _op(el, V_SET, prefix + key, value);
        });
      }
      // Since an attribute value null makes no sense, assumes nullify.
      else if (isNull(defValue)) {
        each(toArray(attr), function (value) {
          var name = prefix + value;
          if (_op(el, V_HAS, name)) {
            _op(el, V_REMOVE, name);
          }
        });
      }
      else {
        // Else a setter.
        if (attr === 'src') {
          // To minimize unnecessary mutations.
          el.src = defValue;
        }
        else if (attr === 'href') {
          el.href = defValue;
        }
        else {
          _op(el, V_SET, attr, defValue);
        }
      }
    };

    return chain.call(els, chainCallback);
  }

  /**
   * Checks if the element has attribute.
   *
   * @private
   *
   * @param {Element} el
   *   The HTML element.
   * @param {string} names
   *   The attribute name(s), space delimited if many.
   *
   * @return {bool}
   *   True if it has the attribute(s).
   */
  function hasAttr(el, names) {
    var found = 0;

    if (isAttr(el) && isStr(names)) {
      var verify = function (name) {
        if (_op(el, V_HAS, name)) {
          found++;
        }
      };

      each(toArray(names), verify);
    }
    return found > 0;
  }

  /**
   * A removeAttribute wrapper.
   *
   * @private
   *
   * @param {dBlazy|Array.<Element>|Element} els
   *   The HTML element(s), or dBlazy instance.
   * @param {string|Array} attr
   *   The attr name, or string array.
   * @param {string} prefix
   *   The attribute prefix if any, normally `data-`.
   *
   * @return {Object}
   *   This dBlazy object.
   */
  function removeAttr(els, attr, prefix) {
    return _attr(els, attr, null, prefix || '');
  }

  /**
   * Checks if the element has a class name.
   *
   * @private
   *
   * @param {Element} el
   *   The HTML element.
   * @param {string} names
   *   The class name, can be space-delimited for multiple names.
   *
   * @return {bool}
   *   True if it has the class name.
   */
  function hasClass(el, names) {
    var found = 0;

    if (isAttr(el) && isStr(names)) {
      // var _list = el.classList;
      var checks = _attr(el, V_CLASS);

      var verify = function (name) {
        // if (_list) {
        // if (_list.contains(name)) {
        // found++;
        // }
        // }
        // SVG may fail classList here.
        // classList.contains fails distiguishing splide from splide-wrapper.
        // You'll never know.
        each(toArray(checks), function (check) {
          if (check && check === name) {
            found++;
          }
        });
      };

      each(toArray(names), verify);
    }
    return found > 0;
  }

  /**
   * Toggles a class, or multiple from an element.
   *
   * @private
   *
   * @param {dBlazy|Array.<Element>|Element} els
   *   The HTML element(s), or dBlazy instance.
   * @param {string|Array.<String>|Function} name
   *   The class name(s), function, space-delimited or array of class names.
   * @param {string} op
   *   Whether to add or remove the class, or undefined to toggle.
   *
   * @return {Object}
   *   This dBlazy object.
   */
  function toggleClass(els, name, op) {
    var chainCallback = function (el, i) {
      if (isAttr(el)) {
        var _list = el.classList;

        if (isFun(name)) {
          name = name(_op(el, V_GET, 'class'), i);
        }

        var names = toArray(name);
        if (_list) {
          if (isUnd(op)) {
            names.map(function (value) {
              _list.toggle(value);
            });
          }
          else {
            _list[op].apply(_list, names);
          }
        }
      }
    };
    return chain.call(els, chainCallback);
  }

  /**
   * Adds a class, or space-delimited class names to an element.
   *
   * @private
   *
   * @param {dBlazy|Array.<Element>|Element} els
   *   The HTML element(s), or dBlazy instance.
   * @param {string} name
   *   The class name, or space-delimited class names.
   *
   * @return {Object}
   *   This dBlazy object.
   */
  function addClass(els, name) {
    return toggleClass(els, name, V_ADD);
  }

  /**
   * Removes a class, or multiple from an element.
   *
   * @private
   *
   * @param {dBlazy|Array.<Element>|Element} els
   *   The HTML element(s), or dBlazy instance.
   * @param {string} name
   *   The class name, or space-delimited class names.
   *
   * @return {Object}
   *   This dBlazy object.
   */
  function removeClass(els, name) {
    return toggleClass(els, name, V_REMOVE);
  }

  /**
   * Checks if a string or element contains substring(s) or children.
   *
   * @private
   *
   * Similar to ES6 ::includes, only for oldies.
   * Cannot use [].every() since it is not about all or nothing.
   *
   * @param {Array|Element|string} str
   *   The source string to test for.
   * @param {Array.<Element>|Array.<string>} substr
   *   The target element(s) or sub-string to check for, can be a string array.
   *
   * @return {bool}
   *   True if it has the needle.
   */
  function contains(str, substr) {
    var found = 0;

    if (isElm(str) && isElm(substr)) {
      return str !== substr && str.contains(substr);
    }

    if (isArr(str)) {
      // @todo use when IE11 gone: str.includes(substr);
      return str.indexOf(substr) !== -1;
    }

    if (isStr(str) && isStr(substr)) {
      str = str.toLowerCase();
      substr = substr.toLowerCase();
      // @todo use when IE11 gone: str.includes(substr);
      each(toArray(substr), function (value) {
        if (str.indexOf(value) !== -1) {
          found++;
        }
      });
    }

    return found > 0;
  }

  /**
   * Escapes special (meta) characters.
   *
   * @private
   *
   * @link https://stackoverflow.com/questions/1144783
   *
   * @param {string} string
   *   The original source string.
   *
   * @return {string}
   *   The modified string.
   */
  function escape(string) {
    // $& means the whole matched string.
    return string.replace(/[.*+\-?^${}()|[\]\\]/g, '\\$&');
  }

  /**
   * Checks whether or not a string begins with another string, case-sensitive.
   *
   * @private
   *
   * @param {string} str
   *   The source string to test for.
   * @param {Array.<string>} substr
   *   The target sub-string to check for, can be a string array.
   *
   * @return {bool}
   *   True if it starts with the needle.
   */
  function startsWith(str, substr) {
    var found = 0;

    if (isStr(str)) {
      each(toArray(substr), function (value) {
        if (str.startsWith(value)) {
          found++;
        }
      });
    }
    return found > 0;
  }

  /**
   * Removes extra spaces so to keep readable template.
   *
   * @private
   *
   * @param {string} string
   *   The original source string.
   *
   * @return {string}
   *   The modified string.
   */
  function trimSpaces(string) {
    // v return string.replace(/\s\s+/g, ' ').trim();
    return string.replace(/\s+/g, ' ').trim();
  }

  /**
   * A forgiving closest for the lazy.
   *
   * @private
   *
   * @param {Element} el
   *   Starting element.
   * @param {string} selector
   *   Selector to match against (class, ID, data attribute, or tag).
   *
   * @return {Element|Null}
   *   Returns null if no match found, else the element.
   */
  function closest(el, selector) {
    return (isElm(el) && isStr(selector)) ? el.closest(selector) : null;
  }

  /**
   * A forgiving matches for the lazy ala jQuery.
   *
   * @private
   *
   * @param {Element|string} el
   *   The current element or string.
   * @param {string} selector
   *   Selector to match against (class, ID, data attribute, or tag).
   *
   * @return {bool}
   *   Returns true if found, else false.
   *
   * @see https://caniuse.com/#feat=matchesselector
   * @see https://developer.mozilla.org/en-US/docs/Web/API/Element/matches
   */
  function is(el, selector) {
    if (isElm(el)) {
      if (isStr(selector)) {
        selector = toScope(selector);
        return el.matches ? el.matches(selector) : false;
      }

      if (isElm(selector)) {
        return el === selector;
      }
    }
    return el === selector;
  }

  /**
   * Check if an element matches the specified HTML tag.
   *
   * @private
   *
   * @param {Element} el
   *   The element to compare.
   * @param {string|Array.<string>} tags
   *   HTML tag(s) to match against.
   *
   * @return {bool}
   *   Returns true if matches, else false.
   */
  function equal(el, tags) {
    if (!el || !el.nodeName) {
      return false;
    }

    return PROTO_SOME.call(toArray(tags), function (tag) {
      return el.nodeName.toLowerCase() === tag.toLowerCase();
    });
  }

  /**
   * A simple querySelector wrapper.
   *
   * @private
   *
   * The only different from jQuery is if a single element found, it returns
   * the element so to avoid ugly repeats like elms[0], also to preserve
   * common vanilla practice which normally operates on the element directly.
   * Alternatively flag the asArray to any value if an array is expected, or
   * use the shortcut ::findAll() to be clear.
   *
   * To check if the returned element is found:
   *   - use $.isElm(el) which returns a bool, or !$.isNull(el).
   *   - or use it directly as condition if not using asArray argument.
   * To check if the returned elements are found:
   *   - use regular els.length check.
   *
   * @param {Element|string} el
   *   The parent HTML element or common selector strings.
   * @param {string} selector
   *   The CSS selector or HTML tag to query.
   * @param {bool|int} asArray
   *   Force returning an array if expected to operate on.
   *
   * @return {Element|null|?Array.<Element>}
   *   Empty array or null if not found, else the expected element(s).
   */
  function find(el, selector, asArray) {
    var single = isUnd(asArray) && isStr(selector);
    el = el || _doc;

    if (isStr(el)) {
      el = toElm(el, true);
    }

    if (isQsa(el)) {
      selector = toScope(selector);
      el = context(el, selector);
      return single ? el.querySelector(selector) : toElms(selector, el);
    }
    return single ? null : [];
  }

  /**
   * A simple direct descendant wrapper.
   *
   * @private
   *
   * @param {string} selector
   *   The CSS selector or HTML tag to query.
   *
   * @return {string}
   *   The corrected selector with :scope.
   */
  function toScope(selector) {
    var sel = selector;
    // Direct descendant.
    var scope = ':scope';

    // Only needed the first found to be valid, not the rest.
    if (isStr(selector) && startsWith(selector, '>')) {
      sel = scope + ' ' + selector;
    }
    return sel;
  }

  /**
   * A simple querySelectorAll wrapper.
   *
   * To check if the expected elements are found:
   *   - use regular `els.length`. The length 0 means not found.
   *
   * @private
   *
   * @param {Element} el
   *   The parent HTML element.
   * @param {string} selector
   *   The CSS selector or HTML tag to query.
   *
   * @return {?Array.<Element>}
   *   Empty array if not found, else the expected elements.
   */
  function findAll(el, selector) {
    return find(el, selector, 1);
  }

  /**
   * A simple removeChild wrapper.
   *
   * @private
   *
   * @param {Element} el
   *   The HTML element to remove.
   */
  function remove(el) {
    if (isElm(el)) {
      var cn = parent(el);
      if (cn) {
        cn.removeChild(el);
      }
    }
  }

  /**
   * Returns true if an IE browser.
   *
   * @private
   *
   * @param {Element} el
   *   The element to check for more contextual property/ feature detection.
   *
   * @return {bool}
   *   True if an IE browser.
   */
  function ie(el) {
    return (isElm(el) && el.currentStyle) || !isUnd(_doc.documentMode);
  }

  /**
   * Returns device pixel ratio.
   *
   * @private
   *
   * @return {number}
   *   Returns the device pixel ratio.
   */
  function pixelRatio() {
    return _win.devicePixelRatio || 1;
  }

  /**
   * Returns cross-browser window width.
   *
   * @private
   *
   * @return {number}
   *   Returns the window width.
   */
  function windowWidth() {
    return _win.innerWidth || _doc.documentElement[V_CLIENTWIDTH] || _win.screen[V_WIDTH];
  }

  /**
   * Returns cross-browser window width and height.
   *
   * @private
   *
   * @return {Object}
   *   Returns the window width and height.
   */
  function windowSize() {
    return {
      width: windowWidth(),
      height: _win.innerHeight || _doc.documentElement.clientHeight
    };
  }

  /**
   * Returns data from the current active window.
   *
   * @private
   *
   * When being resized, the browser gave no data about pixel ratio from desktop
   * to mobile, not vice versa. Unless delayed for 4s+, not less, which is of
   * course unacceptable. Hence why Blazy never claims to support resizing. The
   * best efforts were provided using ResizeObserver since 2.2. including this.
   *
   * @param {Object.<int, Object>} dataset
   *   The dataset object must be keyed by window width.
   * @param {Object.<string, int|bool>} winData
   *   Containing ww: windowWidth, and up: to determine min-width or max-width.
   *
   * @return {Mixed}
   *   Returns data from the current active window.
   */
  function activeWidth(dataset, winData) {
    var mobileFirst = winData.up || false;
    var _k = keys(dataset);
    var xs = _k[0];
    var xl = _k[_k.length - 1];
    var ww = winData.ww || windowWidth();
    var pr = (ww * pixelRatio());
    var rw = mobileFirst ? ww : pr;
    var mw = function (w) {
      // The picture wants <= (approximate), non-picture wants >=, wtf.
      return mobileFirst ? toInt(w, 0) <= rw : toInt(w, 0) >= rw;
    };

    var data = _k.filter(mw).map(function (v) {
      return dataset[v];
    })[mobileFirst ? 'pop' : 'shift']();

    return isUnd(data) ? dataset[rw >= xl ? xl : xs] : data;
  }

  /**
   * A simple wrapper for event delegation like jQuery.on().
   *
   * @private
   *
   * @param {dBlazy|Array.<Element>|Element} els
   *   The HTML element(s), or dBlazy instance.
   * @param {string} eventName
   *   The event name to trigger.
   * @param {string} selector
   *   Child selector to match against (class, ID, data attribute, or tag).
   * @param {Function} cb
   *   The callback function.
   * @param {Object|bool} params
   *   The optional param passed into a custom event.
   * @param {bool} isCustom
   *   True, if a custom event, a namespaced like (blazy.done), but considered
   *   as a whole since there is no event name `blazy`.
   *
   * @return {Object}
   *   This dBlazy object.
   */
  function on(els, eventName, selector, cb, params, isCustom) {
    return toEvent(els, eventName, selector, cb, params, isCustom, V_ADD);
  }

  /**
   * A simple wrapper for event detachment.
   *
   * @private
   *
   * @param {dBlazy|Array.<Element>|Element} els
   *   The HTML element(s), or dBlazy instance.
   * @param {string} eventName
   *   The event name to trigger.
   * @param {string} selector
   *   Child selector to match against (class, ID, data attribute, or tag).
   * @param {Function} cb
   *   The callback function.
   * @param {Object|bool} params
   *   The optional param passed into a custom event.
   * @param {bool} isCustom
   *   True, if a custom event.
   *
   * @return {Object}
   *   This dBlazy object.
   */
  function off(els, eventName, selector, cb, params, isCustom) {
    return toEvent(els, eventName, selector, cb, params, isCustom, V_REMOVE);
  }

  /**
   * A simple wrapper for addEventListener once.
   *
   * @private
   *
   * @param {dBlazy|Array.<Element>|Element} els
   *   The HTML element(s), or dBlazy instance.
   * @param {string} eventName
   *   The event name to remove.
   * @param {Function} cb
   *   The callback function.
   * @param {bool} isCustom
   *   True, if a custom event.
   *
   * @return {Object}
   *   This dBlazy object.
   */
  function one(els, eventName, cb, isCustom) {
    return on(els, eventName, cb, {
      once: true
    }, isCustom);
  }

  /**
   * Checks if image is decoded/ completely loaded.
   *
   * @private
   *
   * @param {Image} img
   *   The Image object.
   *
   * @return {bool}
   *   True if the image is loaded.
   */
  function isDecoded(img) {
    return img.decoded || img.complete;
  }

  /**
   * A shortcut for Array.prototype.slice.
   *
   * @private
   *
   * Ensures an array is returned and not a NodeList or an Array-like object.
   *
   * @param {NodeList|Array.<Element>} elements
   *   A NodeList, array of elements.
   *
   * @see https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Array/from
   *
   * @return {Array.<Element>}
   *   An array of elements.
   */
  function slice(elements) {
    return PROTO_A.slice.call(elements);
  }

  /**
   * Process arguments, query the DOM if necessary. Adapted from core/once.
   *
   * @private
   *
   * @param {NodeList|Array.<Element>|Element|string} selector
   *   A NodeList, array of elements, or string.
   * @param {Document|Element} ctx
   *   An element to use as context for querySelectorAll.
   *
   * @return {Array.<Element>}
   *   An array of elements to process.
   */
  function toElms(selector, ctx) {
    ctx = ctx || _doc;

    // Assume selector is an array-like element unless a string.
    var elements = toArray(selector);
    if (isStr(selector)) {
      elements = ctx.querySelectorAll(selector);
    }

    return slice(elements);
  }

  // Use colon to be namespaced with DOT properly, e.g:
  // blazy:done.NAMESPACE rather than problematic blazy.done.
  // @todo remove isCustom at 3.x for just colon separator.
  function eType(e, isCustom) {
    var custom = isCustom || startsWith(e, ['blazy.', 'bio.']);

    // @todo at 3.x: return e.split('.')[0].trim();
    return (custom ? e : e.split('.')[0]).trim();
  }

  // @todo remove isCustom at 3.x for just colon separator.
  var eHandler = {
    _opts: function (params) {
      var _one = false;
      var options = params || false;
      var defaults = {
        capture: false,
        passive: true
      };

      if (isObj(params)) {
        options = EXTEND(defaults, params);
        _one = options.once || false;
      }

      return {
        one: _one,
        options: options
      };
    },

    add: function (el, e, cb, params, isCustom) {
      var me = this;
      var opts = me._opts(params);
      var options = opts.options;
      var type = eType(e, isCustom);
      var _cb = cb;

      // See https://caniuse.com/once-event-listener.
      // @todo remove IE at 10+.
      if (opts.one && ie()) {
        var cbone = function cbone() {
          el[V_REMOVE + E_LISTENER](type, cbone);
          cb.apply(this, arguments);
        };
        _cb = cbone;
      }

      // Remove existing listeners, if any references.
      // @todo remove after another check, might be assigned to others:
      // if (EVENTS[e] === _cb) {
      // el[V_REMOVE + E_LISTENER](type, EVENTS[e], options);
      // }
      if (isFun(_cb)) {
        var customEvent = {
          name: e,
          callback: _cb,
          type: type
        };

        EVENTS[e] = _cb;
        EVENTS[type] = customEvent;

        el[V_ADD + E_LISTENER](type, _cb, options);
      }
    },

    remove: function (el, e, cb, params, isCustom) {
      var me = this;
      var opts = me._opts(params);
      var options = opts.options;
      var type = eType(e, isCustom);
      var _cb = EVENTS[e] || cb;

      if (isFun(_cb)) {
        el[V_REMOVE + E_LISTENER](type, _cb, options);
        delete EVENTS[e];
        delete EVENTS[type];
      }
    }
  };

  // @todo compare with direct querySelectorAll:
  // - Assumed/ make els single? Unless moved to the chain, but complicated.
  // - Replace els with els children identified by selector.
  // - Remove this onoffEvent callback. Any taker, please?
  function eOnOff(e, cb, selector) {
    // @todo handle automatically by its return value.
    // e.preventDefault();
    // e.stopPropagation();
    var t = e.target;

    if (is(t, selector)) {
      cb.call(t, e);
    }
    else {
      while (t && t !== this) {
        if (is(t, selector)) {
          cb.call(t, e);
          break;
        }
        t = t.parentElement || t.parentNode;
      }
    }
  }

  /**
   * A not simple wrapper for the namespaced [add|remove]EventListener.
   *
   * @private
   *
   * @param {dBlazy|Array.<Element>|Element} els
   *   The HTML element(s), or dBlazy instance.
   * @param {string} eventName
   *   The event name, optionally namespaced, to add or remove.
   * @param {string|Function} selector
   *   Child selector to delegate (valid CSS selector). Or a callback.
   * @param {Function|Object|bool} cb
   *   The callback function. Or params passed into on/off like.
   * @param {Object|bool} params
   *   The optional param passed into a custom event. Or isCustom for on/off.
   * @param {bool|string} isCustom
   *   Like namespaced, but not, LHS is not native event. Or add/remove op.
   * @param {string|undefined} op
   *   Whether to add or remove the event. Or undefined for on/off like.
   *
   * @return {Object}
   *   This dBlazy object.
   *
   * @todo https://developer.mozilla.org/en-US/docs/Web/API/AbortController
   * @todo automatically handled by its return value.
   * @todo remove isCustom at 3.x for just colon.
   *
   * @see https://developer.mozilla.org/en-US/docs/Web/API/EventTarget/addEventListener
   * @see https://caniuse.com/once-event-listener
   * @see https://github.com/WICG/EventListenerOptions/blob/gh-pages/explainer.md
   */
  function toEvent(els, eventName, selector, cb, params, isCustom, op) {
    var _cbt = cb;
    var _ie = ie();

    // 1. Assumes window events if no elements: $.on('scroll', cb, params);
    // Shift one argument if no real elements are provided.
    if (isStr(els) && isFun(eventName)) {
      params = selector;
      cb = eventName;
      eventName = els;
      els = [_win];
    }
    // 2. Delegated events like on/off: $.on(el, 'click', '.btn', cb, params);
    else if (isStr(selector)) {
      var shouldPassive = contains(eventName, ['touchstart', E_SCROLL, 'wheel']);
      if (isUnd(params)) {
        params = _ie ? false : {
          capture: !shouldPassive,
          passive: shouldPassive
        };
      }

      cb = function (e) {
        eOnOff(e, _cbt, selector);
      };
    }
    // 3. Non-delegated events: $.on(el, 'click', cb, params);
    // Shift one argument if selector is expected as a callback function.
    else {
      if (isFun(selector)) {
        isCustom = params;
        params = _cbt;
        cb = selector;
      }
    }

    var chainCallback = function (el) {
      if (!isEvt(el)) {
        return;
      }

      var process = function (e) {
        eHandler[op](el, e, cb, params, isCustom);
      };

      each(toArray(eventName), process);
    };

    return chain.call(els, chainCallback);
  }

  /**
   * A not simple wrapper for triggering event like jQuery.trigger().
   *
   * Namespacing is not done here, instead when calling $.on() or $.off().
   *
   * @param {dBlazy|Array.<Element>|Element} els
   *   The HTML element(s), or dBlazy instance.
   * @param {string} eventNames
   *   The event name to trigger, space delimited for multi-value.
   * @param {Object} details
   *   The optional detail object passed into a custom event detail property.
   * @param {Object} param
   *   The optional param passed into a custom event.
   *
   * @return {Object}
   *   Returns this instance.
   *
   * @see https://developer.mozilla.org/en-US/docs/Web/Guide/Events/Creating_and_triggering_events
   * @see https://developer.mozilla.org/en-US/docs/Web/API/EventTarget/dispatchEvent
   * @see https://developer.mozilla.org/en-US/docs/Web/API/Document/createEvent
   */
  function trigger(els, eventNames, details, param) {
    // Supports $.trigger('resize') for window;
    if (isStr(els)) {
      details = eventNames;
      eventNames = els;
      els = [_win];
    }

    var chainCallback = function (el) {
      if (!isEvt(el)) {
        return;
      }

      var execute = function (eventName) {
        var event;
        if (isUnd(details)) {
          event = new Event(eventName);
        }
        else {
          // Bubbles to be caught by ancestors. Cancelable to preventDefault.
          var data = {
            bubbles: true,
            cancelable: true,
            detail: isObj(details) ? details : {}
          };

          if (isObj(param)) {
            data = EXTEND(data, param);
          }

          event = new CustomEvent(eventName, data);
        }

        el.dispatchEvent(event);

        // Supports triggering events with extra arguments ala jQuery.
        // $.trigger(ROOT, 'custom:move', [ctx, width]);
        // $.on(ROOT, 'custom:move.NAMESPACE', function (e, ctx, width) {});
        var type = eType(eventName);
        if (EVENTS[type] && EVENTS[type].type === eventName && isArr(details)) {
          EVENTS[type].callback.apply(null, [event].concat(details));
        }
      };

      each(toArray(eventNames), execute);
    };

    return chain.call(els, chainCallback);
  }

  /**
   * Load a script dynamically.
   *
   * @link https://stackoverflow.com/questions/16839698
   *
   * @param {string} url
   *   The script url.
   * @param {Function} callback
   *   The optional callback function.
   * @param {string} id
   *   The script id.
   */
  function getScript(url, callback, id) {
    var script = _doc.createElement('script');
    var prior = _doc.getElementsByTagName('script')[0];
    script.async = 1;
    script.id = id;

    script.onload = script.onreadystatechange = function (_, isAbort) {
      if (isAbort || !script.readyState || /loaded|complete/.test(script.readyState)) {
        script.onload = script.onreadystatechange = null;
        script = null;

        if (!isAbort && callback) {
          _win.setTimeout(callback, 0);
        }
      }
    };

    script.src = url;
    prior.parentNode.insertBefore(script, prior);
  }

  // Type methods.
  // Wonder why ES6 has alt lambda `=>` for `function`? Compact, to save bytes.
  // Kotlin has useless `fun` due to being compiled back to `function`. But ES6
  // lambda is true savings unless being transpiled. So these stupid abbr are.
  // The contract here is no rigid minds, fun, less bytes. Hail to Linux.
  DB.isTag = isTag;
  DB.isArr = isArr;
  DB.isBool = isBool;
  DB.isDoc = isDoc;
  DB.isElm = isElm;
  DB.isFun = isFun;
  DB.isEmpty = isEmpty;
  DB.isInt = isInt;
  DB.isNull = isNull;
  DB.isNum = isNum;
  DB.isObj = isObj;
  DB.isStr = isStr;
  DB.isUnd = isUnd;
  DB.isEvt = isEvt;
  DB.isQsa = isQsa;
  DB.isIo = 'Intersection' + S_OBSERVER in _win;
  DB.isMo = 'Mutation' + S_OBSERVER in _win;
  DB.isRo = 'Resize' + S_OBSERVER in _win;
  DB.isNativeLazy = 'loading' in HTMLImageElement.prototype;
  DB.isAmd = typeof define === 'function' && define.amd;
  DB.isWin = isWin;
  DB.isBigPipe = isBigPipe;
  DB.wwoBigPipeDone = wwoBigPipeDone;
  DB.wwoBigPipe = wwoBigPipe;
  DB.isTouch = isTouch;
  DB.touchOrNot = touchOrNot;
  DB._er = -1;
  DB._ok = 1;

  // Collection methods.
  DB.chain = function (els, cb) {
    return chain.call(els, cb);
  };

  DB.each = each;

  DB.extend = EXTEND;
  FN.extend = function (plugins, reverse) {
    reverse = reverse || false;
    return reverse ? EXTEND(plugins, FN) : EXTEND(FN, plugins);
  };

  // Object and array with strings methods.
  DB.hasProp = hasProp;
  DB.parse = parse;
  DB.toArray = toArray;
  DB.toInt = toInt;

  // Attribute methods.
  DB.attr = _attr.bind(DB);
  DB.hasAttr = hasAttr;
  DB.nodeMapAttr = nodeMapAttr;
  DB.removeAttr = removeAttr.bind(DB);

  // Class name methods.
  DB.hasClass = hasClass;
  DB.toggleClass = toggleClass;
  DB.addClass = addClass;
  DB.removeClass = removeClass;

  // String methods.
  DB.contains = contains;
  DB.escape = escape;
  DB.startsWith = startsWith;
  DB.trimSpaces = trimSpaces;

  // DOM query methods.
  DB.closest = closest;
  DB.is = is;

  // @todo merge with ::is().
  DB.equal = equal;
  DB.find = find;
  DB.findAll = findAll;
  DB.remove = remove;

  // Window methods.
  DB.ie = ie;
  DB.pixelRatio = pixelRatio;
  DB.windowWidth = windowWidth;
  DB.windowSize = windowSize;
  DB.activeWidth = activeWidth;

  // Event methods.
  // DB.toEvent = toEvent;
  DB.on = on;
  DB.off = off;
  DB.one = one;
  DB.trigger = trigger;
  DB.getScript = getScript;

  // Image methods.
  DB.isDecoded = isDecoded;
  DB.ready = ready.bind(DB);

  /**
   * Decodes the image.
   *
   * @param {Image} img
   *   The Image object.
   *
   * @return {Promise}
   *   The Promise object.
   *
   * @see https://caniuse.com/promises
   * @see https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Promise
   * @see https://github.com/taylorhakes/promise-polyfill
   * @see https://chromestatus.com/feature/5637156160667648
   * @see https://html.spec.whatwg.org/multipage/embedded-content.html#dom-img-decode
   */
  DB.decode = function (img) {
    if (isDecoded(img)) {
      return Promise.resolve(img);
    }

    if ('decode' in img) {
      img.decoding = 'async';
      return img.decode();
    }

    return new Promise(function (resolve, reject) {
      img.onload = function () {
        resolve(img);
      };
      img.onerror = reject();
    });
  };

  /**
   * A simple wrapper to delay callback function, taken out of blazy library.
   *
   * Alternative to core Drupal.debounce for D7 compatibility, and easy port.
   *
   * @param {Function} cb
   *   The callback function.
   * @param {number} minDelay
   *   The execution delay in milliseconds.
   * @param {Object} scope
   *   The scope of the function to apply to, normally this.
   *
   * @return {Function}
   *   The function executed at the specified minDelay.
   */
  DB.throttle = function (cb, minDelay, scope) {
    minDelay = minDelay || 50;
    var lastCall = 0;
    return function () {
      var now = +new Date();
      if (now - lastCall < minDelay) {
        return;
      }
      lastCall = now;
      cb.apply(scope, arguments);
    };
  };

  function boxSize(entry) {
    var width;
    var height;
    var size;

    if (entry.contentBoxSize) {
      size = entry.contentBoxSize[0];
      if (size) {
        width = size.inlineSize;
        height = size.blockSize;
      }
    }

    if (!height) {
      // entry.contentRect is deprecated.
      size = entry.contentRect || rect(entry.target);
      width = size.width;
      height = size.height;
    }

    return {
      width: Math.floor(width),
      height: Math.floor(height)
    };
  }

  /**
   * A simple wrapper to delay callback function on window resize.
   *
   * @link https://github.com/louisremi/jquery-smartresize
   *
   * @param {Function} cb
   *   The callback function.
   * @param {undefined|String|Array.<Element>|Element} t
   *   The timeout, selector, or element(s).
   * @param {Function} cbt
   *   The touch callback function, else default to cb.
   *
   * @return {Function}
   *   The callback function.
   *
   * See https://dev.to/murashow/quick-guide-to-resize-observer-gam
   * See https://developer.mozilla.org/en-US/docs/Web/API/ResizeObserver
   */
  DB.resize = function (cb, t, cbt) {
    // Preserves oldies till updated: lory, extended, etc.
    // Safe to replace, previously only called: $.resize(cb)();
    if (this.isRo && !isUnd(t)) {
      var observer = new ResizeObserver(function (entries) {
        var me = this;
        var winsize = windowSize();
        var touch = isTouch(cbt || cb);

        each(entries, function (entry) {
          var size = boxSize(entry);
          var data = {
            width: size.width,
            height: size.height,
            window: winsize,
            touch: touch
          };

          // Pass it to callback.
          cb.apply(null, [me, data, entry]);
        });
      });

      var elms = toElms(t);
      if (elms.length) {
        each(elms, function (el) {
          if (isElm(el)) {
            observer.observe(el);
          }
        });
      }
      return cb;
    }

    _win.onresize = function () {
      clearTimeout(t);
      t = setTimeout(cb, 200);
    };

    return cb;
  };

  /**
   * Replaces string occurances to simplify string templating.
   *
   * @param {string} string
   *   The original source string.
   * @param {Object.<string, string>} map
   *   The mapping object.
   *
   * @return {string}
   *   The modified string.
   *
   * @see https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Template_literals
   * @see https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/String/replaceAll
   * @see https://caniuse.com/mdn-javascript_builtins_string_replaceall
   * @see https://stackoverflow.com/questions/1144783
   * @todo use template string or replaceAll for D10, or D11 at the latest.
   */
  DB.template = function (string, map) {
    for (var key in map) {
      if (hasProp(map, key)) {
        string = string.replace(new RegExp(escape('$' + key), 'g'), map[key]);
      }
    }
    return trimSpaces(string);
  };

  /**
   * A simple wrapper for context insanity.
   *
   * Context is unreliable with AJAX contents like product variations, etc.
   * This can be null after Colorbox close, or absurd <script> element, likely
   * arbitrary, etc. Since D10, or blazy:2.17, also identified that the context
   * can be returned as the element with the given selector itself to QSA for
   * causing QSA fail since it QSA itself.
   *
   * @param {Document|Element} ctx
   *   Any element, including weird script element.
   * @param {string} selector
   *   The selector to compare against ctx in case borked somewhere.
   *
   * @return {Element|Document|DocumentFragment}
   *   The Element|Document|DocumentFragment to not fail querySelector, etc.
   *
   * @todo refine core/once expects Element only, or patch it for [1,9,11].
   */
  function context(ctx, selector) {
    // Weirdo: context may be null after Colorbox close.
    ctx = ctx || _doc;

    // In case a string, and if none is found, give a default document here on.
    ctx = toElm(ctx, true) || _doc;

    // @todo fix why the selector itself is given as context on lightboxes
    // since D10/ blazy:2.17. And also check it around for internal mistakes.
    if (selector) {
      if (is(ctx, selector) ||
        is(selector, S_BODY) ||
        is(selector, S_HTML)) {
        ctx = _doc;
      }
    }

    // Absurd arbitrary <script> elements which have no children may be spit on
    // AJAX causing temporary failures as seen at Views UI.
    if (isQsa(ctx) && ctx.children && ctx.children.length) {
      return ctx;
    }

    // IE9 knows not deprecated HTMLDocument, IE8 does.
    // Node.DOCUMENT_NODE|Node.DOCUMENT_FRAGMENT_NODE is not just _doc.
    return isDoc(ctx) ? ctx : _doc;
  }

  // Valid elements for querySelector with length: form, select, etc.
  function toElm(el, isCtx) {
    // Checks if a string is given as a context.
    if (isStr(el)) {
      if (el === S_BODY) {
        return _doc.body;
      }
      // Prevents problematic _doc.documentElement as the element.
      else if (el === S_HTML) {
        return _doc;
      }
      return _doc.querySelector(el);
    }

    // Prevents problematic _doc.documentElement as the context.
    // Ensures to not break valid expectation outside context, like jumper.
    // Normally when operating with attributes, not as a context for QSA.
    if (isCtx && is(el, S_HTML)) {
      return _doc;
    }

    // jQuery may pass its array as non-expected context identified by length.
    var isJq = IS_JQ && el instanceof _win.jQuery;
    var isCash = IS_CASH && el instanceof _win.cash;
    return el && (isMe(el) || isJq || isCash) ? el[0] : el;
  }

  // Minimum common DOM methods taken and modified from cash.
  // @todo refactor or remove dups when everyone uses cash, or vanilla alike.
  function camelCase(str) {
    return str.replace(RE_DASH_ALPHA, function (match, letter) {
      return letter.toUpperCase();
    });
  }

  function isVar(prop) {
    return RE_CSS_VARIABLE.test(prop);
  }

  // @see https://developer.mozilla.org/en-US/docs/Web/API/Window/getComputedStyle
  function computeStyle(el, prop, isVariable) {
    if (!isElm(el)) {
      return null;
    }

    var _style = getComputedStyle(el, null);
    if (isUnd(prop)) {
      return _style;
    }

    if (isVariable || isVar(prop)) {
      return _style.getPropertyValue(prop) || null;
    }

    return _style[prop] || el.style[prop];
  }

  // https://developer.mozilla.org/en-US/docs/Web/API/Element/getBoundingClientRect
  function rect(el) {
    return isElm(el) ? el.getBoundingClientRect() : {};
  }

  function traverse(el, selector, relative) {
    if (isElm(el)) {
      var target = el[relative];

      if (isUnd(selector)) {
        return target;
      }

      while (target) {
        if (is(target, selector) || equal(target, selector)) {
          return target;
        }
        target = target[relative];
      }
    }
    return null;
  }

  function parent(el, selector) {
    return traverse(el, selector, 'parentElement');
  }

  function prevnext(el, selector, prefix) {
    return traverse(el, selector, prefix + 'ElementSibling');
  }

  function prev(el, selector) {
    return prevnext(el, selector, 'previous');
  }

  function next(el, selector) {
    return prevnext(el, selector, 'next');
  }

  function empty(els) {
    var chainCallback = function (el) {
      if (isElm(el)) {
        while (el.firstChild) {
          el.removeChild(el.firstChild);
        }
      }
    };

    return chain.call(els, chainCallback);
  }

  function index(el, parents) {
    var i = 0;
    var loop = true;
    if (isElm(el)) {
      if (!isUnd(parents)) {
        each(toArray(parents), function (sel, idx) {
          if (isElm(sel)) {
            loop = false;
            if (is(el, sel)) {
              i = idx;
              return false;
            }
          }
          else if (isStr(sel)) {
            var check = closest(el, sel);
            if (isElm(check)) {
              el = check;
              return false;
            }
          }
        });
      }

      if (loop) {
        while (!isNull(el = prev(el))) {
          i++;
        }
      }
    }
    return i;
  }

  DB.context = context;
  DB.slice = slice;
  DB.toElm = toElm;
  DB.toElms = toElms;
  DB.camelCase = camelCase;
  DB.isVar = isVar;
  DB.computeStyle = computeStyle;
  DB.rect = rect;
  DB.empty = empty;
  DB.parent = parent;
  DB.next = next;
  DB.prev = prev;
  DB.index = index;
  DB.keys = keys;
  DB._op = _op;

  // See https://caniuse.com/?search=localstorage
  DB.storage = function (key, value, defValue, restore) {
    if (STORAGE) {
      if (isUnd(value)) {
        return STORAGE.getItem(key);
      }

      if (isNull(value)) {
        STORAGE.removeItem(key);
      }
      else {
        try {
          STORAGE.setItem(key, value);
        }
        catch (e) {
          // Reset if (2 - 10MB) quota is exceeded, if value is growing.
          STORAGE.removeItem(key);

          // Only makes sense if the value is incremental, not the quota limit.
          if (restore) {
            STORAGE.setItem(key, value);
          }
        }
      }
    }
    return defValue || false;
  };

  // @todo merge with cash if available.
  // if (IS_CASH) {
  // FN.extend(cash.fn, true);
  // }
  // Collects base prototypes for clarity.
  var objs = {
    chain: function (cb) {
      return chain.call(this, cb);
    },
    each: function (cb) {
      return each(this, cb);
    },
    ready: function (callback) {
      return ready.call(this, callback);
    }
  };

  // Merge base prototypes.
  FN.extend(objs);

  // @deprecated for shorter ::is(). Hardly used, except lory.
  DB.matches = is;

  // @tbd deprecated for DB.each to save bytes. Used by many sub-modules.
  DB.forEach = each;

  // @tbd deprecated for on/off with shifted arguments. Use on/ off instead.
  DB.bindEvent = on.bind(DB);

  DB.unbindEvent = off.bind(DB);

  if (typeof exports !== 'undefined') {
    // Node.js.
    module.exports = DB;
  }
  else {
    // Browser.
    _win.dBlazy = DB;
  }

})(this, this.document, drupalSettings);
