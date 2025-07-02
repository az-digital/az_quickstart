/**
 * @file
 * To have minimal reactive objects ala Backbone, Vue, etc.
 *
 * Not currently in use, but GridStack, etc. due to core/backbone deprecation.
 * Credits: https://gist.github.com/mudge/5830382
 */

(function () {

  'use strict';

  var _symbol = typeof Symbol !== 'undefined' && Symbol;
  var _typeof = typeof _symbol === 'function' &&
    typeof _symbol.iterator === 'symbol' ?
    function (obj) {
      return typeof obj;
    } : function (obj) {
      return obj &&
        typeof _symbol === 'function' &&
        obj.constructor === _symbol ?
        'symbol' :
        typeof obj;
    };

  /**
   * Object for Eventify.
   *
   * @namespace
   *
   * @param {Object} obj
   *   The optional object to merge.
   *
   * @return {Eventify|Object}
   *   Returns this, or the passed mixed object.
   */
  function Eventify(obj) {
    if (obj) {
      return mixin(obj);
    }
    return this;
  }

  var fn = Eventify.prototype;
  fn.constructor = Eventify;
  fn._events = {};

  function mixin(obj) {
    for (var key in fn) {
      if (Object.prototype.hasOwnProperty.call(fn, key)) {
        obj[key] = fn[key];
      }
    }
    return obj;
  }

  fn.on = function (event, listener) {
    var me = this;
    if (_typeof(me._events[event]) !== 'object') {
      me._events[event] = [];
    }

    me._events[event].push(listener);
    return function () {
      me.off(event, listener);
    };
  };

  fn.off = function (event, listener) {
    var me = this;
    var idx = void 0;

    if (_typeof(me._events[event]) === 'object') {
      idx = me._events[event].indexOf(listener);

      if (idx > -1) {
        me._events[event].splice(idx, 1);
      }
    }
    return me;
  };

  fn.emit = function (event) {
    var me = this;
    var i;
    var listeners;
    var length;
    var args = [].slice.call(arguments, 1);

    if (_typeof(me._events[event]) === 'object') {
      listeners = me._events[event].slice();
      length = listeners.length;

      for (i = 0; i < length; i++) {
        listeners[i].apply(me, args);
      }
    }
    return me;
  };

  fn.once = function (event, listener) {
    var me = this;
    me.on(event, function g() {
      me.off(event, g);
      listener.apply(me, arguments);
    });
    return me;
  };

  if (typeof exports !== 'undefined') {
    // Node.js.
    module.exports = Eventify;
  }
  else {
    // Browser.
    window.Eventify = Eventify;
  }

})();
