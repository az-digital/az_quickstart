# Quickstart GDPR Consent Management

Provides geolocation-based GDPR cookie consent management using Klaro and Smart IP.

## Overview

This module integrates Klaro cookie consent management with Smart IP geolocation to conditionally display consent banners only to visitors from countries that require GDPR compliance or have similar data protection laws.

## Features

- **Geolocation-based consent**: Only shows consent banner to visitors from target countries
- **Comprehensive country list**: Pre-configured with:
  - 27 EU Member States
  - 3 EEA Countries (Iceland, Liechtenstein, Norway)
  - United Kingdom (UK GDPR)
  - 11 European countries subject to GDPR compliance
  - 15 countries with similar data protection laws
- **Configurable**: Admin UI to manage settings and country list
- **Test mode**: Force display consent banner for testing
- **Safe fallback**: Option to show consent when location cannot be determined

## Dependencies

- `az_core`
- `klaro` - Cookie consent management module
- `smart_ip` - IP geolocation module
- `smart_ip_maxmind_geoip2_bin_db` - MaxMind GeoIP2 binary database integration (used by default)

## Installation

### Development (Lando)

1. Enable the modules:
   ```bash
   lando drush en klaro smart_ip smart_ip_maxmind_geoip2_bin_db az_gdpr_consent -y
   ```

2. When using Lando, `lando install` automatically:
   - Creates `web/sites/default/files/private/` directory
   - Sets `$settings['file_private_path']` in `settings.php`
   - Configures Smart IP to use MaxMind GeoIP2 Binary Database as the data source
   - Downloads the MaxMind GeoLite2 Country database on first use

3. Configure Klaro module at `/admin/config/user-interface/klaro`

4. Configure GDPR Consent Management at `/admin/config/az-quickstart/settings/az-gdpr-consent`

### Production/Manual Setup

If you're not using Lando or need to configure manually:

1. **Configure private file path** - Add to your `settings.php`:
   ```php
   $settings['file_private_path'] = 'sites/default/files/private';
   ```

2. **Create the directory**:
   ```bash
   mkdir -p web/sites/default/files/private
   chmod 755 web/sites/default/files/private
   ```

3. **Enable the modules**:
   ```bash
   drush en klaro smart_ip smart_ip_maxmind_geoip2_bin_db az_gdpr_consent -y
   ```

4. **Verify setup** at `/admin/reports/status`:
   - Check for **"GDPR Consent: Private file path"** - Should be configured
   - The GeoIP database will download automatically on first page load

5. Configure Klaro and GDPR Consent Management as described above

**Why private files?**
- **Security**: GeoIP databases shouldn't be publicly accessible
- **Best Practice**: Drupal automatically creates a `.htaccess` file to deny web access

### Verification

Visit `/admin/config/people/smart_ip` to verify:
- Data source should be set to: **MaxMind GeoIP2 Binary Database**
- Auto-update should be enabled by default

## Configuration

### Klaro Configuration

Configure Klaro services and purposes at `/admin/config/system/klaro`. This includes:
- Defining cookie purposes (e.g., analytics, marketing)
- Configuring services (e.g., Google Analytics, Google Tag Manager)
- Customizing consent banner text and styling

### Smart IP Configuration

The module automatically configures Smart IP with the following defaults:
- **Data source**: MaxMind GeoIP2 Binary Database (`smart_ip_maxmind_geoip2_bin_db`)
- **Database auto-update**: Enabled (database updates automatically)
- **Version**: GeoLite2 Country (Lite version)
- **Edition**: Country database

These settings are configured via `config/quickstart/smart_ip.settings.yml` and `config/quickstart/smart_ip_maxmind_geoip2_bin_db.settings.yml`.

You can verify or modify these settings at `/admin/config/people/smart_ip` if needed.

**Note**: The MaxMind GeoLite2 database is downloaded automatically on first use. You no longer need to manually download or configure the database file.

### GDPR Consent Management Settings

Access the configuration form at `/admin/config/az-quickstart/settings/az-gdpr-consent`:

- **Enable geolocation-based consent management**: Turn the feature on/off
- **Test mode**: Always show consent banner (useful for testing)
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

## How It Works

1. Visitor requests a page
2. Smart IP detects visitor's country based on IP address
3. Module checks if country is in the target list
4. If yes: Klaro consent banner is shown
5. If no: Klaro consent banner is removed from the page

## Testing

Enable **Test mode** in the configuration form to always show the consent banner regardless of location. This is useful for:
- Testing consent banner appearance and functionality
- Verifying Klaro configuration

## Troubleshooting

**Consent banner not showing:**
- Check that Klaro is configured and enabled
- Verify Smart IP has downloaded the GeoIP2 database (check `/admin/config/people/smart_ip`)
- Check if test mode is disabled when testing from target countries
- Clear Drupal cache: `lando drush cr`

**Consent banner showing for all visitors:**
- Check if test mode is enabled
- Verify Smart IP is correctly detecting location
- Check Smart IP session data: `/admin/reports/smart_ip`

**Location detection not working:**
- Verify Smart IP data source is set to "MaxMind GeoIP2 Binary Database"
- Check that the GeoIP2 database has been downloaded (first page load triggers download)
- Ensure the private file path is configured correctly at `/admin/config/media/file-system`
- Verify the private directory exists: `ls -la web/sites/default/files/private/` (should show `.htaccess` and `smart_ip/` subdirectory)
- Review Smart IP documentation for troubleshooting

**Database download issues:**
- Check that the private files directory exists and is writable
- Verify network connectivity to MaxMind servers
- Check Drupal logs for any error messages: `lando drush watchdog:show`

**Private file path issues:**
- Visit `/admin/reports/status` and look for "GDPR Consent: Private file path" status
- Verify `$settings['file_private_path']` is set in `settings.php`
- Check directory permissions: `chmod 755 web/sites/default/files/private`

## Related Issues

- GitHub Issue: [#3699 - Solution needed for GDPR Compliance](https://github.com/az-digital/az_quickstart/issues/3699)

