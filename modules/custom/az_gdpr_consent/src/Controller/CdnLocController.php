<?php

namespace Drupal\az_gdpr_consent\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * DEPRECATED: Controller for mock CDN location endpoint.
 *
 * This controller is NO LONGER USED with the server-side geolocation approach.
 * The module now overrides $_SERVER['HTTP_X_GEO_COUNTRY_CODE'] directly in
 * test mode instead of using this mock endpoint.
 *
 * Kept for reference in case we need to revert to the client-side approach.
 *
 * @deprecated This endpoint was used for client-side testing via /cdn-loc.
 */
class CdnLocController extends ControllerBase {

  /**
   * Mock CDN location data based on country code mapping.
   */
  private const COUNTRY_DATA = [
    'US' => ['city' => 'tucson', 'region' => 'AZ', 'country_name' => 'united states', 'country_code3' => 'USA', 'latitude' => '32.240', 'longitude' => '-110.920', 'postal_code' => '85716', 'continent_code' => 'NA'],
    'DE' => ['city' => 'berlin', 'region' => 'BE', 'country_name' => 'germany', 'country_code3' => 'DEU', 'latitude' => '52.520', 'longitude' => '13.405', 'postal_code' => '10115', 'continent_code' => 'EU'],
    'GB' => ['city' => 'london', 'region' => 'ENG', 'country_name' => 'united kingdom', 'country_code3' => 'GBR', 'latitude' => '51.507', 'longitude' => '-0.128', 'postal_code' => 'SW1A', 'continent_code' => 'EU'],
    'FR' => ['city' => 'paris', 'region' => 'IDF', 'country_name' => 'france', 'country_code3' => 'FRA', 'latitude' => '48.857', 'longitude' => '2.352', 'postal_code' => '75001', 'continent_code' => 'EU'],
    'CA' => ['city' => 'toronto', 'region' => 'ON', 'country_name' => 'canada', 'country_code3' => 'CAN', 'latitude' => '43.651', 'longitude' => '-79.347', 'postal_code' => 'M5H', 'continent_code' => 'NA'],
    'AU' => ['city' => 'sydney', 'region' => 'NSW', 'country_name' => 'australia', 'country_code3' => 'AUS', 'latitude' => '-33.868', 'longitude' => '151.209', 'postal_code' => '2000', 'continent_code' => 'OC'],
    'JP' => ['city' => 'tokyo', 'region' => 'TK', 'country_name' => 'japan', 'country_code3' => 'JPN', 'latitude' => '35.689', 'longitude' => '139.692', 'postal_code' => '100-0001', 'continent_code' => 'AS'],
    'BR' => ['city' => 'sao paulo', 'region' => 'SP', 'country_name' => 'brazil', 'country_code3' => 'BRA', 'latitude' => '-23.550', 'longitude' => '-46.633', 'postal_code' => '01000', 'continent_code' => 'SA'],
    'MX' => ['city' => 'mexico city', 'region' => 'CMX', 'country_name' => 'mexico', 'country_code3' => 'MEX', 'latitude' => '19.433', 'longitude' => '-99.133', 'postal_code' => '06000', 'continent_code' => 'NA'],
    'ES' => ['city' => 'madrid', 'region' => 'MD', 'country_name' => 'spain', 'country_code3' => 'ESP', 'latitude' => '40.416', 'longitude' => '-3.703', 'postal_code' => '28001', 'continent_code' => 'EU'],
    'IT' => ['city' => 'rome', 'region' => 'RM', 'country_name' => 'italy', 'country_code3' => 'ITA', 'latitude' => '41.902', 'longitude' => '12.496', 'postal_code' => '00100', 'continent_code' => 'EU'],
    'NL' => ['city' => 'amsterdam', 'region' => 'NH', 'country_name' => 'netherlands', 'country_code3' => 'NLD', 'latitude' => '52.370', 'longitude' => '4.895', 'postal_code' => '1012', 'continent_code' => 'EU'],
    'SE' => ['city' => 'stockholm', 'region' => 'AB', 'country_name' => 'sweden', 'country_code3' => 'SWE', 'latitude' => '59.329', 'longitude' => '18.068', 'postal_code' => '11120', 'continent_code' => 'EU'],
    'CH' => ['city' => 'zurich', 'region' => 'ZH', 'country_name' => 'switzerland', 'country_code3' => 'CHE', 'latitude' => '47.376', 'longitude' => '8.541', 'postal_code' => '8001', 'continent_code' => 'EU'],
    'PL' => ['city' => 'warsaw', 'region' => 'MZ', 'country_name' => 'poland', 'country_code3' => 'POL', 'latitude' => '52.229', 'longitude' => '21.012', 'postal_code' => '00-001', 'continent_code' => 'EU'],
    'BE' => ['city' => 'brussels', 'region' => 'BRU', 'country_name' => 'belgium', 'country_code3' => 'BEL', 'latitude' => '50.850', 'longitude' => '4.351', 'postal_code' => '1000', 'continent_code' => 'EU'],
    'AT' => ['city' => 'vienna', 'region' => 'WI', 'country_name' => 'austria', 'country_code3' => 'AUT', 'latitude' => '48.208', 'longitude' => '16.373', 'postal_code' => '1010', 'continent_code' => 'EU'],
    'NO' => ['city' => 'oslo', 'region' => 'OS', 'country_name' => 'norway', 'country_code3' => 'NOR', 'latitude' => '59.913', 'longitude' => '10.752', 'postal_code' => '0150', 'continent_code' => 'EU'],
    'DK' => ['city' => 'copenhagen', 'region' => 'CA', 'country_name' => 'denmark', 'country_code3' => 'DNK', 'latitude' => '55.676', 'longitude' => '12.568', 'postal_code' => '1050', 'continent_code' => 'EU'],
    'FI' => ['city' => 'helsinki', 'region' => 'HE', 'country_name' => 'finland', 'country_code3' => 'FIN', 'latitude' => '60.169', 'longitude' => '24.938', 'postal_code' => '00100', 'continent_code' => 'EU'],
    'IE' => ['city' => 'dublin', 'region' => 'D', 'country_name' => 'ireland', 'country_code3' => 'IRL', 'latitude' => '53.349', 'longitude' => '-6.260', 'postal_code' => 'D01', 'continent_code' => 'EU'],
    'PT' => ['city' => 'lisbon', 'region' => 'LI', 'country_name' => 'portugal', 'country_code3' => 'PRT', 'latitude' => '38.722', 'longitude' => '-9.139', 'postal_code' => '1000', 'continent_code' => 'EU'],
    'GR' => ['city' => 'athens', 'region' => 'AT', 'country_name' => 'greece', 'country_code3' => 'GRC', 'latitude' => '37.983', 'longitude' => '23.727', 'postal_code' => '10431', 'continent_code' => 'EU'],
    'CZ' => ['city' => 'prague', 'region' => 'PR', 'country_name' => 'czech republic', 'country_code3' => 'CZE', 'latitude' => '50.075', 'longitude' => '14.437', 'postal_code' => '110 00', 'continent_code' => 'EU'],
    'RO' => ['city' => 'bucharest', 'region' => 'B', 'country_name' => 'romania', 'country_code3' => 'ROU', 'latitude' => '44.426', 'longitude' => '26.102', 'postal_code' => '010001', 'continent_code' => 'EU'],
    'HU' => ['city' => 'budapest', 'region' => 'BU', 'country_name' => 'hungary', 'country_code3' => 'HUN', 'latitude' => '47.497', 'longitude' => '19.040', 'postal_code' => '1011', 'continent_code' => 'EU'],
    'NZ' => ['city' => 'auckland', 'region' => 'AUK', 'country_name' => 'new zealand', 'country_code3' => 'NZL', 'latitude' => '-36.848', 'longitude' => '174.763', 'postal_code' => '1010', 'continent_code' => 'OC'],
    'KR' => ['city' => 'seoul', 'region' => 'SE', 'country_name' => 'south korea', 'country_code3' => 'KOR', 'latitude' => '37.566', 'longitude' => '126.977', 'postal_code' => '04524', 'continent_code' => 'AS'],
    'SG' => ['city' => 'singapore', 'region' => 'SG', 'country_name' => 'singapore', 'country_code3' => 'SGP', 'latitude' => '1.352', 'longitude' => '103.819', 'postal_code' => '018960', 'continent_code' => 'AS'],
    'ZA' => ['city' => 'johannesburg', 'region' => 'GT', 'country_name' => 'south africa', 'country_code3' => 'ZAF', 'latitude' => '-26.204', 'longitude' => '28.045', 'postal_code' => '2000', 'continent_code' => 'AF'],
    'AR' => ['city' => 'buenos aires', 'region' => 'BA', 'country_name' => 'argentina', 'country_code3' => 'ARG', 'latitude' => '-34.603', 'longitude' => '-58.381', 'postal_code' => 'C1000', 'continent_code' => 'SA'],
    'IN' => ['city' => 'mumbai', 'region' => 'MH', 'country_name' => 'india', 'country_code3' => 'IND', 'latitude' => '19.075', 'longitude' => '72.877', 'postal_code' => '400001', 'continent_code' => 'AS'],
    'CN' => ['city' => 'beijing', 'region' => 'BJ', 'country_name' => 'china', 'country_code3' => 'CHN', 'latitude' => '39.904', 'longitude' => '116.407', 'postal_code' => '100000', 'continent_code' => 'AS'],
  ];

  /**
   * Returns mock CDN location data.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response mimicking Pantheon's /cdn-loc endpoint.
   */
  public function mock() {
    $config = $this->config('az_gdpr_consent.settings');

    // Only serve mock data if test mode is enabled.
    $test_mode = $config->get('test_mode') ?? FALSE;

    if (!$test_mode) {
      // If test mode is not enabled, return 404 to let Pantheon's real endpoint handle it.
      return new JsonResponse(['error' => 'Mock endpoint only available in test mode'], 404);
    }

    // Get configured test country code (default to US).
    $country_code = strtoupper($config->get('test_country_code') ?? 'US');

    // Get country-specific data or use generic data.
    $country_data = self::COUNTRY_DATA[$country_code] ?? [
      'city' => 'unknown',
      'region' => 'XX',
      'country_name' => strtolower($country_code),
      'country_code3' => $country_code,
      'latitude' => '0.000',
      'longitude' => '0.000',
      'postal_code' => '00000',
      'continent_code' => 'XX',
    ];

    // Build response matching Pantheon's format.
    $response_data = [
      'client.geo.area_code' => '520',
      'client.geo.city' => $country_data['city'],
      'client.geo.conn_speed' => 'cable',
      'client.geo.conn_type' => 'wired',
      'client.geo.continent_code' => $country_data['continent_code'],
      'client.geo.country_code' => $country_code,
      'client.geo.country_code3' => $country_data['country_code3'],
      'client.geo.country_name' => $country_data['country_name'],
      'client.geo.gmt_offset' => '-700',
      'client.geo.latitude' => $country_data['latitude'],
      'client.geo.longitude' => $country_data['longitude'],
      'client.geo.metro_code' => '789',
      'client.geo.postal_code' => $country_data['postal_code'],
      'client.geo.proxy_description' => '?',
      'client.geo.proxy_type' => '?',
      'client.geo.region' => $country_data['region'],
      'client.geo.utc_offset' => '-700',
    ];

    return new JsonResponse($response_data);
  }

}