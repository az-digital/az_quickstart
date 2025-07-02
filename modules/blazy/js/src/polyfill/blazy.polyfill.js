/**
 * @file
 * Provides a few disposable polyfills till IE is gone from planet earth.
 *
 * @todo remove a few when min D9.2+ since they are included as core polyfills
 * and can be made dependencies instead. Unless by then, IE is already gone, and
 * core deprecates them like classList.
 * @see https://www.drupal.org/node/3243406
 * @see https://www.drupal.org/node/3159731
 * @see https://www.drupal.org/node/3211146
 * @see https://www.drupal.org/node/3079238
 * @see https://www.drupal.org/node/3113447
 */

(function (_win) {

  'use strict';

  var _aProto = Array.prototype;
  var _eProto = Element.prototype;
  var _nProto = NodeList.prototype;
  var _sProto = String.prototype;

  // See https://developer.mozilla.org/en-US/docs/Web/API/Element/closest
  if (!_eProto.matches) {
    _eProto.matches = _eProto.msMatchesSelector || _eProto.webkitMatchesSelector;
  }

  // https://developer.mozilla.org/en-US/docs/Web/API/Element/closest
  // @todo remove when min D9.2 for drupal.element.closest|matches.
  // @see https://caniuse.com/#feat=element-closest
  // @see https://caniuse.com/#feat=matchesselector
  // @see https://developer.mozilla.org/en-US/docs/Web/API/Element/matches
  // @see https://developer.mozilla.org/en-US/docs/Web/API/Node/nodeType
  // @see https://developer.mozilla.org/en-US/docs/Web/API/Element/closest
  if (!_eProto.closest) {
    _eProto.closest = function (s) {
      var el = this;

      do {
        if (_eProto.matches.call(el, s)) {
          return el;
        }
        el = el.parentElement || el.parentNode;
      } while (el !== null && el.nodeType === 1);
      return null;
    };
  }

  // @see https://developer.mozilla.org/en-US/docs/Web/API/NodeList/forEach#Polyfill
  if (_win.NodeList && !_nProto.forEach) {
    _nProto.forEach = _aProto.forEach;
  }

  // @see https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Object/assign#Polyfill
  // @see https://developer.mozilla.org/en-US/docs/MDN/About#Code_samples_and_snippets
  if (typeof Object.assign !== 'function') {
    Object.defineProperty(Object, 'assign', {
      value: function assign(target, varArgs) {

        if (target === null || target === 'undefined') {
          throw new TypeError('Cannot convert undefined or null to object');
        }

        var to = Object(target);

        for (var index = 1; index < arguments.length; index++) {
          var nextSource = arguments[index];

          if (nextSource !== null && nextSource !== 'undefined') {
            for (var nextKey in nextSource) {
              if (Object.prototype.hasOwnProperty.call(nextSource, nextKey)) {
                to[nextKey] = nextSource[nextKey];
              }
            }
          }
        }

        return to;
      },
      writable: true,
      configurable: true
    });
  }

  // https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/String/startsWith
  if (!_sProto.startsWith) {
    Object.defineProperty(_sProto, 'startsWith', {
      value: function (search, rawPos) {
        var pos = rawPos > 0 ? rawPos | 0 : 0;
        return this.substring(pos, pos + search.length) === search;
      }
    });
  }

  if (!_aProto.includes) {
    // Or use Object.defineProperty.
    _aProto.includes = function (search) {
      return !!~this.indexOf(search);
    };
  }

  // See https://caniuse.com/?search=map
  if (!_aProto.map) {
    _aProto.map = function (cb) {
      var result = [];
      for (var i = 0; i < this.length; i++) {
        result.push(cb(this[i], i, this));
      }
      return result;
    };
  }

  // IE >= 9 compat, else SCRIPT445: Object doesn't support this action.
  // @see https://msdn.microsoft.com/library/ff975299(v=vs.85).aspx.
  if (typeof _win.CustomEvent === 'function') {
    return false;
  }

  function CustomEvent(event, params) {
    params = params || {
      bubbles: false,
      cancelable: false,
      detail: null
    };
    var evt = document.createEvent('CustomEvent');
    evt.initCustomEvent(event, params.bubbles, params.cancelable, params.detail);
    return evt;
  }

  CustomEvent.prototype = _win.Event.prototype;
  _win.CustomEvent = CustomEvent;

})(this);
