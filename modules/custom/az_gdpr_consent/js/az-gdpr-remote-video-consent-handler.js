/**
 * @file
 * Blocks YouTube and Vimeo API scripts from loading until Klaro consent is given.
 *
 * This script runs BEFORE az_paragraphs video scripts and intercepts API loading
 * to ensure GDPR compliance. It also handles consent revocation by destroying players.
 */

/* eslint-disable no-console */

(() => {
  // Track which APIs have been loaded
  const loadedAPIs = {
    youtube: false,
    vimeo: false,
  };

  /**
   * Checks if Klaro consent has been given for a service.
   *
   * @param {string} service - The service name (youtube, vimeo).
   * @return {boolean} True if consent is granted.
   */
  const hasConsent = (service) => {
    try {
      const klaroCookie = document.cookie
        .split(';')
        .find((cookie) => cookie.trim().startsWith('klaro='));

      if (!klaroCookie) {
        return false;
      }

      const klaroValue = klaroCookie.split('=')[1];
      const consents = JSON.parse(decodeURIComponent(klaroValue));

      return consents[service] === true;
    } catch (e) {
      if (console && console.error) {
        console.error(`[AZ GDPR] Error checking ${service} consent:`, e);
      }
      return false;
    }
  };

  /**
   * Destroys all YouTube players on the page and restores preview image.
   */
  const destroyYouTubePlayers = () => {
    try {
      const containers = document.querySelectorAll('.az-js-video-background');
      containers.forEach((container) => {
        // Destroy the player instance
        if (
          container.player &&
          typeof container.player.destroy === 'function'
        ) {
          container.player.destroy();
          container.player = null;
        }

        // Remove any iframes that might remain
        const iframe = container.querySelector('iframe');
        if (iframe) {
          iframe.remove();
        }

        // Clean up parent paragraph classes to restore preview state
        if (container.dataset.parentid) {
          const parentParagraph = document.getElementById(
            container.dataset.parentid,
          );
          if (parentParagraph) {
            parentParagraph.classList.remove('az-video-playing');
            parentParagraph.classList.remove('az-video-paused');
            parentParagraph.classList.remove('az-video-loading');
          }
        }
      });
    } catch (e) {
      if (console && console.error) {
        console.error('[AZ GDPR] Error destroying YouTube players:', e);
      }
    }
  };

  /**
   * Destroys all Vimeo players on the page and restores preview image.
   */
  const destroyVimeoPlayers = () => {
    try {
      const containers = document.querySelectorAll(
        '.az-js-vimeo-video-background',
      );
      containers.forEach((container) => {
        // Destroy the player instance
        if (
          container.player &&
          typeof container.player.destroy === 'function'
        ) {
          container.player.destroy();
          container.player = null;
        }

        // Remove any iframes that might remain
        const iframe = container.querySelector('iframe');
        if (iframe) {
          iframe.remove();
        }

        // Clean up parent paragraph classes to restore preview state
        if (container.dataset.parentid) {
          const parentParagraph = document.getElementById(
            container.dataset.parentid,
          );
          if (parentParagraph) {
            parentParagraph.classList.remove('az-video-playing');
            parentParagraph.classList.remove('az-video-paused');
            parentParagraph.classList.remove('az-video-loading');
          }
        }
      });
    } catch (e) {
      if (console && console.error) {
        console.error('[AZ GDPR] Error destroying Vimeo players:', e);
      }
    }
  };

  /**
   * Watches for script tags trying to load video APIs and blocks them.
   */
  const blockVideoScripts = () => {
    // Store the original createElement
    const originalCreateElement = document.createElement;

    // Override createElement to intercept video API script creation
    document.createElement = function createElementOverride(tagName) {
      const element = originalCreateElement.call(document, tagName);

      if (tagName.toLowerCase() === 'script') {
        // Store the original src setter
        const originalSrcDescriptor = Object.getOwnPropertyDescriptor(
          HTMLScriptElement.prototype,
          'src',
        );

        // Override src property to intercept video APIs
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
              if (!hasConsent('youtube')) {
                // Store the script element for later
                const scriptElement = this;

                /**
                 * Klaro Consent Watcher for YouTube
                 *
                 * Sets up a watcher to monitor Klaro consent changes.
                 * - When consent is granted: Load the YouTube API
                 * - When consent is revoked: Destroy all YouTube players
                 *
                 * Retry mechanism:
                 * - Klaro might not be initialized when this script runs (weight -2000)
                 * - We retry every 100ms up to 50 times (5 seconds) until Klaro is ready
                 * - Once ready, we register a watcher object with an update() method
                 */
                const registerKlaroWatcher = () => {
                  let retryCount = 0;
                  const maxRetries = 50;

                  const attemptSetup = () => {
                    retryCount += 1;

                    try {
                      if (window.klaro && window.klaro.getManager) {
                        const manager = window.klaro.getManager();

                        if (manager && manager.watch && manager.config) {
                          const watcherObject = {
                            update: () => {
                              if (
                                hasConsent('youtube') &&
                                !loadedAPIs.youtube
                              ) {
                                // Consent granted - load API
                                loadedAPIs.youtube = true;
                                originalSrcDescriptor.set.call(
                                  scriptElement,
                                  value,
                                );
                              } else if (
                                !hasConsent('youtube') &&
                                loadedAPIs.youtube
                              ) {
                                // Consent revoked - destroy players
                                destroyYouTubePlayers();
                              }
                            },
                          };

                          manager.watch(watcherObject);
                          return; // Success
                        }

                        if (retryCount < maxRetries) {
                          setTimeout(attemptSetup, 100);
                        }
                      } else if (retryCount < maxRetries) {
                        setTimeout(attemptSetup, 100);
                      }
                    } catch (e) {
                      if (retryCount < maxRetries) {
                        setTimeout(attemptSetup, 100);
                      }
                    }
                  };

                  attemptSetup();
                };

                registerKlaroWatcher();

                // Don't set the src yet - wait for consent
                return;
              }
            }

            // Check if this is the Vimeo Player API
            if (
              value &&
              typeof value === 'string' &&
              value.includes('player.vimeo.com/api/player.js')
            ) {
              if (!hasConsent('vimeo')) {
                // Store the script element for later
                const scriptElement = this;

                /**
                 * Klaro Consent Watcher for Vimeo
                 *
                 * Sets up a watcher to monitor Klaro consent changes.
                 * - When consent is granted: Load the Vimeo API
                 * - When consent is revoked: Destroy all Vimeo players
                 */
                const registerKlaroWatcher = () => {
                  let retryCount = 0;
                  const maxRetries = 50;

                  const attemptSetup = () => {
                    retryCount += 1;

                    try {
                      if (window.klaro && window.klaro.getManager) {
                        const manager = window.klaro.getManager();

                        if (manager && manager.watch && manager.config) {
                          const watcherObject = {
                            update: () => {
                              if (hasConsent('vimeo') && !loadedAPIs.vimeo) {
                                // Consent granted - load API
                                loadedAPIs.vimeo = true;
                                originalSrcDescriptor.set.call(
                                  scriptElement,
                                  value,
                                );
                              } else if (
                                !hasConsent('vimeo') &&
                                loadedAPIs.vimeo
                              ) {
                                // Consent revoked - destroy players
                                destroyVimeoPlayers();
                              }
                            },
                          };

                          manager.watch(watcherObject);
                          return; // Success
                        }

                        if (retryCount < maxRetries) {
                          setTimeout(attemptSetup, 100);
                        }
                      } else if (retryCount < maxRetries) {
                        setTimeout(attemptSetup, 100);
                      }
                    } catch (e) {
                      if (retryCount < maxRetries) {
                        setTimeout(attemptSetup, 100);
                      }
                    }
                  };

                  attemptSetup();
                };

                registerKlaroWatcher();

                // Don't set the src yet - wait for consent
                return;
              }
            }

            // Set the src normally for non-video scripts
            originalSrcDescriptor.set.call(this, value);
          },
          configurable: true,
        });
      }

      return element;
    };
  };

  // Initialize immediately
  blockVideoScripts();
})();
