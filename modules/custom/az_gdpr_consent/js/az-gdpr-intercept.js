/**
 * @file
 * Early geolocation fetch and Drupal.attachBehaviors interception.
 *
 * This script must run in <head> before any other Drupal scripts to ensure
 * it can intercept Drupal.attachBehaviors before it's called.
 */

/* eslint-disable no-console */

// Start geolocation fetch immediately
window.azGdprGeoPromise = fetch('/cdn-loc')
  .then((r) => r.json())
  .catch((e) => {
    console.error('[AZ GDPR] Early fetch failed:', e);
    return null;
  });

// Intercept Drupal object creation and attachBehaviors assignment
(() => {
  let _drupal = window.Drupal;
  let _attachBehaviors = null;

  // Set up trap for Drupal property on window
  Object.defineProperty(window, 'Drupal', {
    get() {
      return _drupal;
    },
    set(drupalObj) {
      _drupal = drupalObj;

      // Now set up trap for attachBehaviors on the Drupal object
      Object.defineProperty(_drupal, 'attachBehaviors', {
        get() {
          return _attachBehaviors;
        },
        set(originalAttachBehaviors) {
          // Store wrapped version
          _attachBehaviors = (context, settings) => {
            if (window.azGdprGeoPromise) {
              // Delaying attachBehaviors until geolocation settles
              window.azGdprGeoPromise
                .then(() => {
                  originalAttachBehaviors.call(_drupal, context, settings);
                })
                .catch(() => {
                  console.warn(
                    '[AZ GDPR] Geolocation failed, calling attachBehaviors',
                  );
                  originalAttachBehaviors.call(_drupal, context, settings);
                });
            } else {
              originalAttachBehaviors.call(_drupal, context, settings);
            }
          };
        },
        configurable: true,
      });
    },
    configurable: true,
  });
})();
