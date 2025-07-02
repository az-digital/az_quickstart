/**
 * @file
 * Provides a fullscreen polyfill for Fullscren API.
 *
 * Not currently loaded till a sub-module, or Blazy feature provides one.
 *
 * https://wiki.mozilla.org/Gecko:FullScreenAPI
 * https://developer.mozilla.org/en-US/docs/Web/API/Fullscreen_API
 * https://developer.mozilla.org/en-US/docs/Web/API/Fullscreen_API/Guide
 */

(function (_doc) {

  'use strict';

  var _proto = Element.prototype;
  if (!_proto.requestFullscreen) {
    _proto.requestFullscreen = _proto.mozRequestFullscreen || _proto.webkitRequestFullscreen || _proto.msRequestFullscreen;
  }

  if (!_doc.exitFullscreen) {
    _doc.exitFullscreen = _doc.mozExitFullscreen || _doc.webkitExitFullscreen || _doc.msExitFullscreen;
  }

  if (!('fullscreenElement' in _doc)) {
    Object.defineProperty(_doc, 'fullscreenElement', {
      get: function () {
        return _doc.mozFullScreenElement || _doc.msFullscreenElement || _doc.webkitFullscreenElement;
      }
    });

    Object.defineProperty(_doc, 'fullscreenEnabled', {
      get: function () {
        return _doc.mozFullScreenEnabled || _doc.msFullscreenEnabled || _doc.webkitFullscreenEnabled;
      }
    });
  }

})(this.document);
