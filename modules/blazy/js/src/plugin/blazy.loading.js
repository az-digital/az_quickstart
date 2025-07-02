/**
 * @file
 * Provides loading extension for dBlazy.
 */

(function ($) {

  'use strict';

  /**
   * Removes common loading indicator classes.
   *
   * @private
   *
   * @param {dBlazy|Array.<Element>|Element} els
   *   The loading HTML element(s), or dBlazy instance.
   *
   * @return {Object}
   *   This dBlazy object.
   */
  function unloading(els) {
    var chainCallback = function (el) {
      var _loading = 'loading';
      var parent = $.parent(el, '.media') || el;
      var bloader;

      // The .b-lazy element can be attached to IMG, or DIV as CSS background.
      // The .(*)loading can be .media, .grid, .slide__content, .box, etc.
      // Check for potential nested loading classes.
      var loaders = [
        el,
        $.closest(el, '.is-' + _loading),
        $.closest(el, '[class*="' + _loading + '"]')
      ];

      var cleanout = function (loader) {
        if ($.isElm(loader)) {
          var name = loader.className;
          if ($.contains(name, _loading)) {
            loader.className = name.replace(/(\S+)loading/g, '');
          }
        }
      };

      // Looks like Ajaxin fails given iframes with various lightboxes/ options.
      // Be sure to not interupt success ones, only the failures.
      // @todo remove once Ajaxin is better handling iframes.
      setTimeout(function () {
        bloader = $.next(parent, '.b-loader');
        if ($.isElm(bloader)) {
          $.remove(bloader);
        }
      }, 1500);

      $.each(loaders, cleanout);
    };

    return $.chain(els, chainCallback);
  }

  $.unloading = unloading;
  $.fn.unloading = function () {
    return unloading(this);
  };

}(dBlazy));
