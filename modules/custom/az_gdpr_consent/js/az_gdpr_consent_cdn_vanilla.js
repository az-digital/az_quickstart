/**
 * @file
 * DEPRECATED: Client-side GDPR Consent Banner Control (Vanilla ES6)
 *
 * This file is NO LONGER USED. The module now uses server-side geolocation
 * via Pantheon's X-Geo-Country-Code header instead of client-side detection.
 *
 * Kept for reference in case we need to revert to the client-side approach.
 *
 * Original approach:
 * This is a standalone version that doesn't require Drupal.
 * It fetches geolocation data from Pantheon's /cdn-loc endpoint
 * and shows/hides the Klaro consent banner based on the visitor's country.
 */

(() => {
  'use strict';

  // List of countries requiring GDPR consent banner
  const GDPR_COUNTRIES = [
    // EU Members (27)
    'AT', 'BE', 'BG', 'HR', 'CY', 'CZ', 'DK', 'EE', 'FI', 'FR',
    'DE', 'GR', 'HU', 'IE', 'IT', 'LV', 'LT', 'LU', 'MT', 'NL',
    'PL', 'PT', 'RO', 'SK', 'SI', 'ES', 'SE',
    // EEA Countries (3)
    'IS', 'LI', 'NO',
    // United Kingdom
    'GB',
    // Additional countries with similar data protection laws
    'CH', 'BR', 'CA', 'AU', 'NZ', 'JP', 'KR', 'ZA', 'IL', 'AR',
    'UY', 'MX', 'CL', 'CO', 'PE', 'IN', 'TH', 'PH', 'ID', 'MY',
    'SG', 'TW', 'HK', 'AE', 'SA', 'EG', 'KE', 'NG', 'TR',
  ];

  // Configuration
  const config = {
    showOnUnknown: false,
    testMode: false,
    debug: true,
    cacheDuration: 5 * 60 * 1000, // 5 minutes
    cacheKey: 'az_gdpr_country_code',
  };

  const debugLog = (message, data = '') => {
    if (config.debug) {
      console.log('[AZ GDPR Consent]', message, data);
    }
  };

  const getCachedCountryCode = () => {
    try {
      const cached = localStorage.getItem(config.cacheKey);
      if (!cached) return null;

      const data = JSON.parse(cached);
      const now = new Date().getTime();

      if (data.expires && now < data.expires) {
        debugLog('Using cached country code:', data.countryCode);
        return data.countryCode;
      } else {
        debugLog('Cached country code expired');
        localStorage.removeItem(config.cacheKey);
      }
    } catch (e) {
      debugLog('Error reading cache:', e);
    }
    return null;
  };

  const cacheCountryCode = (countryCode) => {
    try {
      const data = {
        countryCode,
        expires: new Date().getTime() + config.cacheDuration,
      };
      localStorage.setItem(config.cacheKey, JSON.stringify(data));
      debugLog('Cached country code:', countryCode);
    } catch (e) {
      debugLog('Error caching country code:', e);
    }
  };

  const fetchCountryCode = async () => {
    try {
      const response = await fetch('/cdn-loc', {
        method: 'GET',
        headers: {
          'Accept': 'application/json',
        },
        cache: 'no-cache',
      });

      if (!response.ok) {
        throw new Error(`AGCDN location endpoint returned: ${response.status}`);
      }

      const data = await response.json();

      // Pantheon's format uses: client.geo.country_code
      const countryCode = data['client.geo.country_code'] || data.country_code;

      if (!countryCode) {
        debugLog('No country code in response:', data);
        return null;
      }

      debugLog('Fetched country code from /cdn-loc:', countryCode);
      cacheCountryCode(countryCode);

      return countryCode;
    } catch (error) {
      debugLog('Error fetching country code:', error);
      return null;
    }
  };

  const shouldShowBanner = (countryCode) => {
    if (config.testMode) {
      debugLog('Test mode enabled - showing banner');
      return true;
    }

    if (!countryCode) {
      debugLog('Unknown country - using fallback:', config.showOnUnknown);
      return config.showOnUnknown;
    }

    const showBanner = GDPR_COUNTRIES.includes(countryCode.toUpperCase());
    debugLog('Country:', countryCode, 'Show banner:', showBanner);

    return showBanner;
  };

  const hideBanner = () => {
    debugLog('Hiding Klaro banner');

    const klaroContainer = document.getElementById('klaro');
    if (klaroContainer) {
      klaroContainer.remove();
    }

    if (window.klaro) {
      debugLog('Klaro already loaded - attempting to hide');
      window.klaro = null;
    }

    document.body.classList.remove('klaro-visible', 'klaro-shown');

    const notices = document.querySelectorAll('.klaro, [class*="klaro"]');
    notices.forEach(notice => {
      notice.style.display = 'none';
    });
  };

  const showBanner = () => {
    debugLog('Showing Klaro banner (allowing Klaro to initialize)');
  };

  const init = async () => {
    debugLog('Initializing AZ GDPR Consent check');

    let countryCode = getCachedCountryCode();

    if (!countryCode) {
      countryCode = await fetchCountryCode();
    }

    const show = shouldShowBanner(countryCode);

    if (show) {
      showBanner();
    } else {
      hideBanner();
    }
  };

  // Run when DOM is ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

})();