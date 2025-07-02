/**
 * @file
 * draggableviews.js
 *
 * Defines the behaviors needed for draggableviews integration.
 */

(function (Drupal) {
  Drupal.behaviors.draggableviewsWeights = {
    attach(context, settings) {
      const weights = document.querySelectorAll('.draggableviews-weight');
      if (weights.length) {
        weights.forEach(function (el, weight) {
          el.setAttribute('value', weight);
        });
      }
    },
  };
})(Drupal);
