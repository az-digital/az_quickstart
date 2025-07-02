/**
 * @file
 * JavaScript behaviors for IMCE.
 */

(function ($, Drupal) {

  'use strict';

  /**
   * Override processUrlInput to place the 'Open File Browser' links after the target element.
   *
   * @param {int} i
   *   Element's index.
   * @param {element} el
   *   A element.
   */
  window.imceInput.processUrlInput = function (i, el) {
    var button = imceInput.createUrlButton(el.id, el.getAttribute('data-imce-type'));
    $(button).insertAfter($(el));
  };

})(jQuery, Drupal);
