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

## Installation

1. Enable the modules:
   ```bash
   lando drush en klaro smart_ip az_gdpr_consent -y
   ```

2. Configure Klaro module at `/admin/config/user-interface/klaro`

3. Configure Smart IP module at `/admin/config/people/smart_ip`
   - Set up a geolocation data source (MaxMind GeoIP2 recommended)

4. Configure GDPR Consent Management at `/admin/config/system/az-gdpr-consent`

## Configuration

### Klaro Configuration

Configure Klaro services and purposes at `/admin/config/system/klaro`. This includes:
- Defining cookie purposes (e.g., analytics, marketing)
- Configuring services (e.g., Google Analytics, Google Tag Manager)
- Customizing consent banner text and styling

### Smart IP Configuration

Configure Smart IP at `/admin/config/people/smart_ip`:
- Choose a geolocation data source
- For MaxMind GeoIP2 (recommended):
  - Sign up for a free MaxMind account
  - Download the GeoLite2 Country database
  - Upload to your Drupal private files directory
  - Configure the path in Smart IP settings

### GDPR Consent Management Settings

Access the configuration form at `/admin/config/system/az-gdpr-consent`:

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
- Verify Smart IP has a data source configured
- Check if test mode is disabled when testing from target countries
- Clear Drupal cache: `lando drush cr`

**Consent banner showing for all visitors:**
- Check if test mode is enabled
- Verify Smart IP is correctly detecting location
- Check Smart IP session data: `/admin/reports/smart_ip`

**Location detection not working:**
- Verify Smart IP data source is configured
- Check that geolocation database is up to date
- Review Smart IP documentation for troubleshooting

## Related Issues

- GitHub Issue: [#3699 - Solution needed for GDPR Compliance](https://github.com/az-digital/az_quickstart/issues/3699)

