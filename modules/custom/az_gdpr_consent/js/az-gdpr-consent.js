/**
 * @file
 * AZ GDPR Consent Management - Pure JavaScript implementation (ES6)
 *
 * This script uses client-side geolocation detection via Pantheon's /cdn-loc
 * endpoint to determine visitor location and conditionally auto-accepts
 * consent for visitors from non-GDPR countries.
 *
 * For GDPR countries, the Klaro banner displays normally and toggle button is shown.
 * For non-GDPR countries, consent is auto-accepted and toggle button stays hidden.
 */

/* eslint-disable no-console */

(() => {
  let settings = null;
  const settingsElement = document.querySelector(
    '[data-drupal-selector="drupal-settings-json"]',
  );

  if (settingsElement) {
    try {
      settings = JSON.parse(settingsElement.textContent);
    } catch (e) {
      console.error('[AZ GDPR] Error parsing drupalSettings JSON:', e);
    }
  }

  // If parsing failed or services not available, exit
  if (!settings?.azGdprConsent?.klaroServices) {
    console.error('[AZ GDPR] Services not available in parsed settings');
    return;
  }

  // Inject CSS to hide toggle button by default
  const style = document.createElement('style');
  style.id = 'klaro-toggle-hide';
  style.textContent = '#klaro_toggle_dialog { display: none !important; }';
  document.head.appendChild(style);

  // list of GDPR and GDPR-like countries (ISO 3166-1 alpha-2 codes)
  const GDPR_COUNTRIES = [
    // EU Member States (27)
    'AT',
    'BE',
    'BG',
    'HR',
    'CY',
    'CZ',
    'DK',
    'EE',
    'FI',
    'FR',
    'DE',
    'GR',
    'HU',
    'IE',
    'IT',
    'LV',
    'LT',
    'LU',
    'MT',
    'NL',
    'PL',
    'PT',
    'RO',
    'SK',
    'SI',
    'ES',
    'SE',
    // EEA Countries (3)
    'IS',
    'LI',
    'NO',
    // UK (post-Brexit, still has UK GDPR)
    'GB',
    // European Countries NOT in EU but should comply
    'AL',
    'BY',
    'BA',
    'XK',
    'MD',
    'ME',
    'MK',
    'RU',
    'RS',
    'TR',
    'UA',
    // Countries with data protection laws similar to GDPR
    'CH', // Switzerland
    'BH',
    'IL',
    'QA', // Middle East
    'KE',
    'MU',
    'NG',
    'ZA',
    'UG', // Africa
    'JP',
    'KR', // Asia
    'NZ', // Oceania
    'AR',
    'BR',
    'UY', // South America
    'CA', // Canada
  ];

  const STORAGE_NAME = 'klaro';
  const STORAGE_METHOD = 'cookie'; // 'cookie' or 'localStorage'
  const COOKIE_EXPIRES_AFTER_DAYS = 180;

  /**
   * Sets auto-accepted consent for all configured Klaro services.
   */
  const setAutoAcceptedConsent = () => {
    const services = settings.azGdprConsent.klaroServices;
    const consentsJson = JSON.stringify(services);

    try {
      if (STORAGE_METHOD === 'cookie') {
        const expiryDays = COOKIE_EXPIRES_AFTER_DAYS;
        const expiryDate = new Date();
        expiryDate.setTime(
          expiryDate.getTime() + expiryDays * 24 * 60 * 60 * 1000,
        );
        const expires = `expires=${expiryDate.toUTCString()}`;
        document.cookie = `${STORAGE_NAME}=${encodeURIComponent(consentsJson)};${expires};path=/;SameSite=Lax`;
      } else {
        // Use localStorage
        localStorage.setItem(STORAGE_NAME, consentsJson);
      }
    } catch (e) {
      console.error('[AZ GDPR] Error auto-accepting services:', e);
    }
  };

  /**
   * Checks if a country code is in the GDPR countries list.
   *
   * @param {string} countryCode - The ISO 3166-1 alpha-2 country code.
   * @return {boolean} True if country requires GDPR compliance.
   */
  const isGdprCountry = (countryCode) => {
    return GDPR_COUNTRIES.map((c) => c.toUpperCase()).includes(
      countryCode.toUpperCase(),
    );
  };

  /**
   * Shows the Klaro toggle button by removing the hiding CSS.
   */
  const showToggleButton = () => {
    try {
      const hideStyle = document.getElementById('klaro-toggle-hide');
      if (hideStyle) {
        hideStyle.remove();
      }
    } catch (e) {
      console.error('[AZ GDPR] Error showing toggle button:', e);
    }
  };

  /**
   * Main execution: Fetch geolocation and conditionally set consent.
   */
  const initialize = () => {
    // Fetch geolocation to determine whether to show toggle button
    fetch('/cdn-loc')
      .then((response) => {
        if (!response.ok) {
          throw new Error('Network response was not ok');
        }
        return response.json();
      })
      .then((data) => {
        const countryCode = data['client.geo.country_code'];

        if (!countryCode) {
          throw new Error('Country code not found in /cdn-loc response');
        }

        if (isGdprCountry(countryCode)) {
          // GDPR country - show toggle button (hidden by default)
          showToggleButton();
        } else {
          // Non-GDPR country - auto-accept consent every time
          setAutoAcceptedConsent();
        }
      })
      .catch((error) => {
        // If geolocation fetch fails, assume GDPR applies
        console.error('[AZ GDPR] Failed to fetch geolocation data:', error);
        showToggleButton();
      });
  };

  // Execute initialization
  initialize();
})();
