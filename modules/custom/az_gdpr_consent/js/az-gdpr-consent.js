/**
 * @file
 * AZ GDPR Consent Management - Pure JavaScript implementation (ES6)
 *
 * This script uses client-side geolocation detection via Pantheon's /cdn-loc
 * endpoint to determine visitor location and conditionally auto-accepts
 * consent for visitors from non-GDPR countries.
 *
 * For GDPR countries, the Klaro banner displays normally.
 * For non-GDPR countries, consent is auto-accepted before Klaro loads.
 */

(() => {
  'use strict';

  // list of GDPR and GDPR-like countries (ISO 3166-1 alpha-2 codes)
  const GDPR_COUNTRIES = [
    // EU Member States (27)
    'AT', 'BE', 'BG', 'HR', 'CY', 'CZ', 'DK', 'EE', 'FI', 'FR',
    'DE', 'GR', 'HU', 'IE', 'IT', 'LV', 'LT', 'LU', 'MT', 'NL',
    'PL', 'PT', 'RO', 'SK', 'SI', 'ES', 'SE',
    // EEA Countries (3)
    'IS', 'LI', 'NO',
    // UK (post-Brexit, still has UK GDPR)
    'GB',
    // European Countries NOT in EU but should comply
    'AL', 'BY', 'BA', 'XK', 'MD', 'ME', 'MK', 'RU', 'RS', 'TR', 'UA',
    // Countries with data protection laws similar to GDPR
    'CH', // Switzerland
    'BH', 'IL', 'QA', // Middle East
    'KE', 'MU', 'NG', 'ZA', 'UG', // Africa
    'JP', 'KR', // Asia
    'NZ', // Oceania
    'AR', 'BR', 'UY', // South America
    'CA' // Canada
  ];

  // Hard-coded Klaro configuration
  // Note: These MUST match ALL services configured in Klaro
  const KLARO_SERVICES = {
    'ga': true,
    'gtm': true,
    'facebook': true,
    'cms': true,
    'klaro': true,
    'vimeo': true,
    'youtube': true
  };
  const STORAGE_NAME = 'klaro';
  const STORAGE_METHOD = 'cookie'; // 'cookie' or 'localStorage'
  const COOKIE_EXPIRES_AFTER_DAYS = 180;

  /**
   * Sets auto-accepted consent for all configured Klaro services.
   */
  const setAutoAcceptedConsent = () => {
    // Use the pre-configured service settings
    const consentsJson = JSON.stringify(KLARO_SERVICES);

    try {
      if (STORAGE_METHOD === 'cookie') {
        const expiryDays = COOKIE_EXPIRES_AFTER_DAYS;
        const expiryDate = new Date();
        expiryDate.setTime(expiryDate.getTime() + (expiryDays * 24 * 60 * 60 * 1000));
        const expires = `expires=${expiryDate.toUTCString()}`;
        document.cookie = `${STORAGE_NAME}=${encodeURIComponent(consentsJson)};${expires};path=/;SameSite=Lax`;

        if (console && console.log) {
          console.log('[AZ GDPR Consent] Auto-accepted all services (cookie) for non-GDPR country');
        }
      } else {
        // Use localStorage
        localStorage.setItem(STORAGE_NAME, consentsJson);

        if (console && console.log) {
          console.log('[AZ GDPR Consent] Auto-accepted all services (localStorage) for non-GDPR country');
        }
      }
    } catch (e) {
      if (console && console.error) {
        console.error('[AZ GDPR Consent] Error auto-accepting services:', e);
      }
    }
  };

  /**
   * Checks if a country code is in the GDPR countries list.
   */
  const isGdprCountry = (countryCode) => {
    return GDPR_COUNTRIES
      .map(c => c.toUpperCase())
      .includes(countryCode.toUpperCase());
  };

  /**
   * Checks if consent cookie already exists.
   */
  const hasExistingConsent = () => {
    if (STORAGE_METHOD === 'cookie') {
      return document.cookie.split(';').some(cookie => {
        return cookie.trim().startsWith(`${STORAGE_NAME}=`);
      });
    } else {
      return localStorage.getItem(STORAGE_NAME) !== null;
    }
  };

  /**
   * Main execution: Fetch geolocation and conditionally set consent.
   */
  const initialize = () => {
    // Skip if consent already exists (from previous visit)
    if (hasExistingConsent()) {
      if (console && console.log) {
        console.log('[AZ GDPR Consent] Existing consent found, skipping');
      }
      return;
    }

    // Fetch geolocation data from Pantheon /cdn-loc endpoint
    fetch('/cdn-loc')
      .then(response => {
        if (!response.ok) {
          throw new Error('Network response was not ok');
        }
        return response.json();
      })
      .then(data => {
        const countryCode = data['client.geo.country_code'];

        if (!countryCode) {
          throw new Error('Country code not found in /cdn-loc response');
        }

        if (isGdprCountry(countryCode)) {
          // GDPR country detected - let Klaro banner display normally
          if (console && console.log) {
            console.log(`[AZ GDPR Consent] GDPR country detected: ${countryCode} - Allowing banner to display.`);
          }
        } else {
          // Non-GDPR country - auto-accept consent
          if (console && console.log) {
            console.log(`[AZ GDPR Consent] Non-GDPR country detected: ${countryCode}`);
          }
          setAutoAcceptedConsent();
        }
      })
      .catch(error => {
        // If geolocation fetch fails, assume GDPR applies for safety
        if (console && console.error) {
          console.error('[AZ GDPR Consent] Failed to fetch geolocation data:', error);
          console.log('[AZ GDPR Consent] Assuming GDPR country due to error - banner will display.');
        }
      });
  };

  // Execute immediately
  initialize();
})();
