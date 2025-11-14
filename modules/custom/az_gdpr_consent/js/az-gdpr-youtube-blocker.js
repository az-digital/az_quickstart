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
    document.createElement = function (tagName) {
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

                // Set up Klaro consent listener
                window.addEventListener('klaro-consent-updated', () => {
                  if (hasYouTubeConsent()) {
                    if (console && console.log) {
                      console.log(
                        '[AZ GDPR] YouTube consent granted, loading API',
                      );
                    }
                    // Now load the script
                    originalSrcDescriptor.set.call(scriptElement, value);
                  }
                });

                // Don't set the src yet - wait for consent
                return;
              }

              if (console && console.log) {
                console.log(
                  '[AZ GDPR] YouTube consent already granted, allowing API load',
                );
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

    if (console && console.log) {
      console.log('[AZ GDPR] YouTube API blocker initialized');
    }
  };

  // Initialize immediately
  blockYouTubeScripts();
})();
