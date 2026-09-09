/**
 * @file
 * Initializes tooltips for anchored accordions.
 */

/* global arizonaBootstrap */

((Drupal, once) => {
  Drupal.behaviors.azAccordionAnchors = {
    attach: (context) => {
      once(
        'az-accordion-anchor-tooltip',
        '[data-bs-toggle="tooltip"]',
        context,
      ).forEach((trigger) => {
        arizonaBootstrap.Tooltip.getOrCreateInstance(trigger, {
          container: 'body',
        });
      });
    },
  };
})(Drupal, once);
