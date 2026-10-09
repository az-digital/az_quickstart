/**
 * @file
 * AZ GDPR Consent Management - Pure JavaScript implementation (ES6)
 *
 * This script uses client-side geolocation detection via Pantheon's /cdn-loc
 * endpoint to determine visitor location and conditionally auto-accepts
 * consent for visitors from non-GDPR countries.
 *
 */

/* eslint-disable no-console */

(() => {
  // Check if drupalSettings is available
  if (
    typeof drupalSettings === 'undefined' ||
    !drupalSettings?.azGdprConsent?.klaroServices
  ) {
    console.error('[AZ GDPR] drupalSettings or services not available');
    throw new Error('[AZ GDPR] drupalSettings not available');
  }

  const settings = drupalSettings;

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
   * Use the early geolocation fetch that was started by az-gdpr-intercept.js.
   *
   * The az-gdpr-intercept.js script (loaded early in <head>) starts fetch('/cdn-loc')
   * immediately and stores the Promise in window.azGdprGeoPromise. By the time this
   * script runs, the fetch may have already completed. We await the Promise to get
   * the result.
   *
   * If geolocation fails, we assume GDPR (show banner) to be safe.
   */
  if (!window.azGdprGeoPromise) {
    console.error('[AZ GDPR] Early geo fetch promise not found');
  } else {
    // Use .then() to get the result of the early fetch
    window.azGdprGeoPromise
      .then((data) => {
        if (!data) {
          console.error('[AZ GDPR] Failed to fetch geolocation data');
          return;
        }

        try {
          const countryCode = data['client.geo.country_code'];

          if (!countryCode) {
            throw new Error('Country code not found in /cdn-loc response');
          }

          const isGdpr = isGdprCountry(countryCode);

          if (!isGdpr) {
            setAutoAcceptedConsent();
          }
        } catch (error) {
          console.error('[AZ GDPR] Error processing geolocation data:', error);
        }
      })
      .catch((error) => {
        console.error('[AZ GDPR] Failed to fetch geolocation data:', error);
      });
  }
})();
