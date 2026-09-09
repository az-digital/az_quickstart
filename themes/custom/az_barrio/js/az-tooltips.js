/**
 * @file
 * Initializes Arizona Bootstrap tooltips.
 */

/* global arizonaBootstrap */

((Drupal, once) => {
  Drupal.behaviors.azAccordionAnchors = {
    attach: (context) => {
      once(
        'az-tooltips',
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
