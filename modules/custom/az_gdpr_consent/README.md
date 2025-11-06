# Quickstart GDPR Consent Management

Provides geolocation-based GDPR cookie consent management using Klaro and Pantheon's AGCDN geolocation headers.

## Overview

This module integrates Klaro cookie consent management with Pantheon's AGCDN geolocation headers (`X-Geo-Country-Code`) for server-side geolocation detection. It conditionally displays consent banners only to visitors from countries that require GDPR compliance or have similar data protection laws.

For non-GDPR countries (like the United States), the module automatically pre-sets consent for all services before Klaro loads, allowing tracking to work as if Klaro was not installed.

## Features

- **Server-side geolocation**: Uses Pantheon's AGCDN `X-Geo-Country-Code` header at the edge
- **Cache-aware**: Uses Drupal cache contexts to vary cached pages by country
- **Auto-accept for non-GDPR**: Automatically grants consent for visitors outside GDPR regions
- **Comprehensive country list**: Pre-configured with 50+ countries:
  - 27 EU Member States
  - 3 EEA Countries (Iceland, Liechtenstein, Norway)
  - United Kingdom (UK GDPR)
  - 30+ countries with similar data protection laws
- **Test mode**: Override country code for testing without VPN (useful for local development)
- **Configurable**: Admin UI to manage settings
- **Safe fallback**: Option to show/hide consent when location cannot be determined

## Dependencies

- `az_core`
- `klaro` - Cookie consent management module
- **Pantheon AGCDN** - Requires Pantheon's Advanced Global CDN with geolocation headers

## Installation

### Setup

1. **Enable the modules**:
   ```bash
   drush en klaro az_gdpr_consent -y
   ```

2. **Clear caches**:
   ```bash
   drush cr
   ```

3. Configure Klaro module at `/admin/config/user-interface/klaro`

4. Configure GDPR Consent Management at `/admin/config/az-quickstart/settings/az-gdpr-consent`

## How It Works

### Server-Side Geolocation

1. **Edge Detection**: Pantheon's AGCDN detects visitor location at the CDN edge and adds `X-Geo-Country-Code` header
2. **Cache Context**: Module adds cache context for the header, ensuring Pantheon caches separate versions per country
3. **PHP Logic**: `hook_page_attachments()` reads the header and determines if visitor is in a GDPR country
4. **Auto-Accept**: For non-GDPR countries, inline JavaScript pre-sets Klaro consent cookie before Klaro loads
5. **Banner Display**: For GDPR countries, Klaro loads normally and shows the consent banner

### Directory Structure

```
az_gdpr_consent/
├── src/
│   └── Form/
│       └── GdprConsentSettingsForm.php     # Admin settings form
|
├── az_gdpr_consent.module                  # Main module logic (server-side approach)
├── az_gdpr_consent.routing.yml             # Routes
└── az_gdpr_consent.info.yml               # Module metadata
```

## Configuration

### Klaro Configuration

Configure Klaro services and purposes at `/admin/config/user-interface/klaro`:
- Define cookie purposes (e.g., analytics, marketing)
- Configure services (e.g., Google Analytics, Google Tag Manager)
- Customize consent banner text and styling

### GDPR Consent Management Settings

Access the configuration form at `/admin/config/az-quickstart/settings/az-gdpr-consent`:

- **Enable geolocation-based consent management**: Turn the feature on/off
- **Test mode**: Override AGCDN country code for testing (useful for local development)
- **Test country code**: Two-letter ISO code to simulate (e.g., DE, US, GB)
- **Show consent banner when location is unknown**: Safer option for compliance
- **Target country codes**: List of ISO 3166-1 alpha-2 country codes (one per line)

### Target Countries (Default)

The module includes these countries by default:

**EU Member States (27):**
AT, BE, BG, HR, CY, CZ, DK, EE, FI, FR, DE, GR, HU, IE, IT, LV, LT, LU, MT, NL, PL, PT, RO, SK, SI, ES, SE

**EEA Countries (3):**
IS, LI, NO

**UK (post-Brexit):**
GB

**European countries subject to GDPR:**
AL, BY, BA, XK, MD, ME, MK, RU, RS, TR, UA

**Countries with similar data protection laws:**
- Europe: CH (Switzerland)
- Middle East: BH, IL, QA
- Africa: KE, MU, NG, ZA, UG
- Asia: JP, KR
- Oceania: NZ
- South America: AR, BR, UY
- North America: CA

## Caching Strategy

The module uses Drupal's cache context system to ensure proper CDN caching:

1. **Cache Context**: `headers:X-Geo-Country-Code` is added to page attachments
2. **Vary Header**: Drupal automatically adds `Vary: X-Geo-Country-Code` to HTTP responses
3. **Separate Caches**: Pantheon CDN maintains separate cached versions for each country
4. **Performance**: Server-side detection is extremely fast (happens at CDN edge)
5. **No Race Conditions**: Unlike client-side approaches, there are no timing or API issues

## Storage Method

The module automatically detects and uses Klaro's configured storage method:

- **Cookie Storage** (default): The module reads Klaro's `library.storage_method` and `library.cookie_name` settings and creates a cookie with the consent data
- **localStorage**: If Klaro is configured to use localStorage, the module will use that instead
- **Automatic Detection**: The inline JavaScript checks Klaro's configuration and uses the appropriate storage method
- **Format**: Stores a JSON object with service names as keys and boolean consent values (e.g., `{"ga":true,"gtm":true}`)

This ensures the module matches Klaro's expected storage format.

## Testing

### Test Mode

Enable test mode to override the country code without needing a VPN:

1. Go to `/admin/config/az-quickstart/settings/az-gdpr-consent`
2. Check "Test mode"
3. Enter a country code (e.g., `DE` for Germany or `US` for United States)
4. Save configuration
5. Clear Drupal cache: `drush cr`
6. Visit your site in incognito mode and check behavior

### Testing Different Countries

**Test GDPR country (should show banner):**
- Test mode: ✓ Enabled
- Test country code: `DE`
- Expected: Banner shows (Germany is in GDPR list)

**Test non-GDPR country (should NOT show banner):**
- Test mode: ✓ Enabled
- Test country code: `US`
- Expected: Banner hidden (USA not in GDPR list)

### Console Output

**Browser console** (for non-GDPR countries):
```
[AZ GDPR Consent] Auto-accepted all services (cookie) for non-GDPR country: US
```
or if using localStorage:
```
[AZ GDPR Consent] Auto-accepted all services (localStorage) for non-GDPR country: US
```

The module automatically logs to the browser console when it auto-accepts services for non-GDPR countries. This helps verify the module is working correctly.

## Troubleshooting

**Consent banner not showing:**
- Check that Klaro is configured and enabled
- Verify you're testing from a GDPR country (or enable test mode)
- Check browser console for auto-accept messages
- Clear Drupal cache: `drush cr`
- Clear browser cookies (especially the `klaro` cookie)

**Consent banner showing for all visitors:**
- Check if test mode is enabled
- Verify test country code is set correctly
- Check browser console for country detection
- Delete the `klaro` cookie and refresh

**Location detection not working:**
- Verify you're on Pantheon hosting with AGCDN enabled
- Contact Pantheon support to confirm `X-Geo-Country-Code` header is enabled
- Use test mode to override country code for testing
- Check browser console for auto-accept messages to verify module is running

**Auto-accept not working for non-GDPR countries:**
- Check browser console for JavaScript errors
- Verify the `klaro` cookie is being set (check Application → Cookies in browser DevTools)
- Check that Klaro services are properly configured
- Ensure Klaro's storage method matches what the module sets (default: cookie)
- Clear browser cookies and refresh

## Advantages Over Client-Side Approaches

1. **Simpler Infrastructure**
   - No MaxMind account/credentials needed
   - No database downloads or updates
   - No external API calls required

2. **Better Performance**
   - Server-side detection at CDN edge (fastest possible)
   - No JavaScript fetch delays or race conditions
   - Proper CDN caching with separate versions per country

3. **More Reliable**
   - No timing issues with JavaScript loading order
   - No localStorage timing problems
   - Decision made before page renders

4. **Cache-Friendly**
   - Uses Drupal cache contexts correctly
   - Pantheon CDN varies cache by country automatically
   - No cache invalidation issues

5. **Easier Maintenance**
   - All logic in one PHP hook
   - No complex JavaScript state management
   - Easier to debug and test

## Browser Compatibility

- All modern browsers with cookie support (default)
- All modern browsers with localStorage support (if Klaro configured for localStorage)
- Chrome, Firefox, Safari, Edge (current versions)
- Mobile browsers (iOS Safari, Chrome Mobile)
- Requires JavaScript enabled for auto-accept functionality
- Cookies must not be blocked by browser privacy settings

## Related Issues

- GitHub Issue: [#3699 - Solution needed for GDPR Compliance](https://github.com/az-digital/az_quickstart/issues/3699)

## License

GPL-2.0-or-later