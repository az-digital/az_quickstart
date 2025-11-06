/**
 * @file
 * DEPRECATED: Client-side GDPR Consent Banner Control (ES6)
 *
 * This file is NO LONGER USED. The module now uses server-side geolocation
 * via Pantheon's X-Geo-Country-Code header instead of client-side detection.
 *
 * Kept for reference in case we need to revert to the client-side approach.
 *
 * Original approach:
 * This script fetches geolocation data from Pantheon's /cdn-loc endpoint
 * and shows/hides the Klaro consent banner based on the visitor's country.
 */

// List of countries requiring GDPR consent banner
// Based on EU/EEA countries and other jurisdictions with similar data protection laws
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
  'CH', // Switzerland
  'BR', // Brazil (LGPD)
  'CA', // Canada (PIPEDA)
  'AU', // Australia (Privacy Act)
  'NZ', // New Zealand
  'JP', // Japan (APPI)
  'KR', // South Korea
  'ZA', // South Africa (POPIA)
  'IL', // Israel
  'AR', // Argentina
  'UY', // Uruguay
  'MX', // Mexico
  'CL', // Chile
  'CO', // Colombia
  'PE', // Peru
  'IN', // India
  'TH', // Thailand
  'PH', // Philippines
  'ID', // Indonesia
  'MY', // Malaysia
  'SG', // Singapore
  'TW', // Taiwan
  'HK', // Hong Kong
  'AE', // UAE
  'SA', // Saudi Arabia
  'EG', // Egypt
  'KE', // Kenya
  'NG', // Nigeria
  'TR', // Turkey
];

/**
 * Configuration options
 */
const config = {
  // Hide banner if country detection fails (assume outside GDPR regions)
  showOnUnknown: false,
  // Enable test mode (always show banner for debugging)
  testMode: false,
  // Enable debug logging
  debug: true,
  // Cache duration in milliseconds (5 minutes)
  cacheDuration: 5 * 60 * 1000,
  // Local storage key for caching
  cacheKey: 'az_gdpr_country_code',
};

/**
 * Log debug messages if debug mode is enabled
 */
const debugLog = (message, data = '') => {
  if (config.debug) {
    console.log('[AZ GDPR Consent]', message, data);
  }
};

/**
 * Get cached country code if available and not expired
 */
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

/**
 * Cache the country code
 */
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

/**
 * Fetch geolocation data from Pantheon's AGCDN endpoint
 */
const fetchCountryCode = async () => {
  try {
    const response = await fetch('/cdn-loc', {
      method: 'GET',
      headers: {
        'Accept': 'application/json',
      },
      // Don't cache the response in the browser
      cache: 'no-cache',
    });

    if (!response.ok) {
      throw new Error(`AGCDN location endpoint returned: ${response.status}`);
    }

    const data = await response.json();

    // Extract country code from the response
    // Based on Pantheon's format: client.geo.country_code
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

/**
 * Determine if the banner should be shown for the given country
 */
const shouldShowBanner = (countryCode) => {
  // Unknown country: use fallback setting
  if (!countryCode) {
    debugLog('Unknown country - using fallback:', config.showOnUnknown);
    return config.showOnUnknown;
  }

  // Check if country is in GDPR list
  const showBanner = GDPR_COUNTRIES.includes(countryCode.toUpperCase());

  if (config.testMode) {
    debugLog('Test mode enabled - Country:', countryCode, 'Show banner:', showBanner);
  } else {
    debugLog('Country:', countryCode, 'Show banner:', showBanner);
  }

  return showBanner;
};

/**
 * Auto-accept all Klaro services without showing banner
 */
const autoAcceptAll = () => {
  debugLog('Auto-accepting all cookies (non-GDPR country)');

  // Wait for Klaro to be available, then auto-accept
  const acceptAll = () => {
    if (window.klaro && window.klaro.getManager) {
      try {
        const manager = window.klaro.getManager();

        // Get all configured services
        if (manager && manager.config && manager.config.services) {
          const serviceNames = manager.config.services.map(service => service.name);

          debugLog('Auto-accepting services:', serviceNames);

          // Build consents object
          const consents = {};
          serviceNames.forEach(name => {
            consents[name] = true;
          });

          // Try different methods to accept consents
          if (typeof manager.updateConsent === 'function') {
            // Try single consent update for each service
            serviceNames.forEach(name => {
              try {
                manager.updateConsent(name, true);
              } catch (e) {
                debugLog('Error updating consent for ' + name, e);
              }
            });
          }

          // Also try to save to storage directly
          if (manager.storageName) {
            localStorage.setItem(manager.storageName, JSON.stringify(consents));
          }

          debugLog('All services auto-accepted');
        }
      } catch (error) {
        debugLog('Error auto-accepting services:', error);
      }
    } else {
      // Klaro not ready yet, wait a bit
      setTimeout(acceptAll, 100);
    }
  };

  // Start trying to accept
  acceptAll();
};

/**
 * Show the Klaro consent banner
 */
const showBanner = () => {
  debugLog('Showing Klaro banner (allowing Klaro to initialize)');
  // Klaro will initialize itself via Drupal behaviors
  // We don't need to do anything special here
};

/**
 * Main initialization function
 */
const init = async () => {
  debugLog('Initializing AZ GDPR Consent check');

  // Try to get cached country code first
  let countryCode = getCachedCountryCode();

  // If no cache, fetch from API
  if (!countryCode) {
    countryCode = await fetchCountryCode();
  }

  // Determine if we should show the banner
  const show = shouldShowBanner(countryCode);

  if (show) {
    showBanner();
  } else {
    autoAcceptAll();
  }
};

/**
 * Drupal behavior to run on page load
 */
Drupal.behaviors.azGdprConsent = {
  attach(context, settings) {
    // Only run once on the document
    if (context !== document) {
      return;
    }

    // Override config from Drupal settings if provided
    if (settings.azGdprConsent) {
      Object.assign(config, settings.azGdprConsent);
    }

    // Run initialization (Klaro will load normally and we'll auto-accept if needed)
    init();
  }
};
