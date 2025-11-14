/**
 * @file
 * Blocks YouTube IFrame API from loading until Klaro consent is given.
 *
 * This script runs BEFORE az_paragraphs_text_media's YouTube script
 * and intercepts the YouTube API loading process to ensure GDPR compliance.
 */

/* eslint-disable no-console */

(() => {
  /**
   * Checks if Klaro consent has been given for YouTube.
   *
   * @return {boolean} True if YouTube consent is granted.
   */
  const hasYouTubeConsent = () => {
    try {
      // Check for Klaro cookie
      const klaroCookie = document.cookie
        .split(';')
        .find((cookie) => cookie.trim().startsWith('klaro='));

      if (!klaroCookie) {
        return false;
      }

      const klaroValue = klaroCookie.split('=')[1];
      const consents = JSON.parse(decodeURIComponent(klaroValue));

      // Check if youtube is explicitly accepted
      return consents.youtube === true;
    } catch (e) {
      if (console && console.error) {
        console.error('[AZ GDPR] Error checking YouTube consent:', e);
      }
      return false;
    }
  };

  /**
   * Watches for script tags trying to load YouTube API and blocks them.
   */
  const blockYouTubeScripts = () => {
    // Store the original createElement
    const originalCreateElement = document.createElement;

    // Override createElement to intercept YouTube API script creation
    document.createElement = function createElementOverride(tagName) {
      const element = originalCreateElement.call(document, tagName);

      if (tagName.toLowerCase() === 'script') {
        // Store the original src setter
        const originalSrcDescriptor = Object.getOwnPropertyDescriptor(
          HTMLScriptElement.prototype,
          'src',
        );

        // Override src property to intercept YouTube API
        Object.defineProperty(element, 'src', {
          get() {
            return originalSrcDescriptor.get.call(this);
          },
          set(value) {
            // Check if this is the YouTube IFrame API
            if (
              value &&
              typeof value === 'string' &&
              (value.includes('youtube.com/iframe_api') ||
                value.includes('youtube.com/player_api'))
            ) {
              if (!hasYouTubeConsent()) {
                if (console && console.log) {
                  console.log(
                    '[AZ GDPR] Blocking YouTube API - no consent given',
                  );
                }

                // Store the script element for later
                const scriptElement = this;
                let apiLoaded = false;

                /**
                 * Klaro Consent Watcher
                 *
                 * Sets up a watcher to monitor Klaro consent changes and
                 * loads the YouTube API right after the user grants consent.
                 *
                 * 1. YouTube API was blocked from loading initially (see lines 66-77)
                 * 2. Now we register a watcher with Klaro's consent manager
                 * 3. When the user accepts consent, Klaro triggers the watcher's update() method
                 * 4. The watcher checks if YouTube consent was granted and loads the API
                 * 5. An apiLoaded flag prevents duplicate loading (Klaro fires multiple events)
                 *
                 * Retry mechanism:
                 * - Klaro might not be initialized when this script runs (weight -2000)
                 * - We retry every 100ms up to 50 times (5 seconds) until Klaro is ready
                 * - Once ready, we register a watcher object with an update() method
                 */
                const consentHandler = () => {
                  if (hasYouTubeConsent() && !apiLoaded) {
                    apiLoaded = true;
                    // Now load the script - YouTube will call onYouTubeIframeAPIReady automatically
                    originalSrcDescriptor.set.call(scriptElement, value);
                  }
                };

                let retryCount = 0;
                const maxRetries = 50; // Stop after 5 seconds

                const registerKlaroWatcher = () => {
                  retryCount += 1;

                  try {
                    if (window.klaro && window.klaro.getManager) {
                      const manager = window.klaro.getManager();

                      if (manager && manager.watch && manager.config) {
                        // The watcher object must have an 'update' method
                        const watcherObject = {
                          update: () => {
                            consentHandler();
                          },
                        };

                        manager.watch(watcherObject);
                      } else if (retryCount < maxRetries) {
                        // Manager not fully initialized, try again
                        setTimeout(registerKlaroWatcher, 100);
                      }
                    } else if (retryCount < maxRetries) {
                      // Klaro not ready yet, try again soon
                      setTimeout(registerKlaroWatcher, 100);
                    }
                  } catch (e) {
                    // Klaro threw an error (not ready yet), try again
                    if (retryCount < maxRetries) {
                      setTimeout(registerKlaroWatcher, 100);
                    }
                  }
                };

                registerKlaroWatcher();

                // Don't set the src yet - wait for consent
                return;
              }
            }

            // Set the src normally
            originalSrcDescriptor.set.call(this, value);
          },
          configurable: true,
        });
      }

      return element;
    };
  };

  // Initialize immediately
  blockYouTubeScripts();
})();
