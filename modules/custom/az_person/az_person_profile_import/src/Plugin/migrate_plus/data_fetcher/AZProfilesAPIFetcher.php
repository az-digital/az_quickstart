<?php

declare(strict_types=1);

namespace Drupal\az_person_profiles_import\Plugin\migrate_plus\data_fetcher;

use Drupal\migrate\MigrateException;
use Drupal\migrate_plus\Plugin\migrate_plus\data_fetcher\Http;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

/**
 * Retrieve data from the profiles API.
 *
 * Example:
 *
 * @code
 * source:
 *   plugin: url
 *   data_fetcher_plugin: az_profiles_api_fetcher
 *   headers:
 *     Accept: application/json
 *     User-Agent: Internet Explorer 6
 *     Authorization-Key: secret
 *     Arbitrary-Header: foobarbaz
 * @endcode
 *
 * @DataFetcher(
 *   id = "az_profiles_api_fetcher",
 *   title = @Translation("Quickstart HTTP Fetcher with optional cache")
 * )
 */
class AZProfilesAPIFetcher extends Http {

  /**
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    $instance = new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
    );

    try {
      // Use the distribution cached http client if it is available.
      $instance->httpClient = $container->get('az_http.http_client');
    }
    catch (ServiceNotFoundException $e) {
      // Otherwise, fall back on the Drupal core guzzle client.
      $instance->httpClient = $container->get('http_client');
    }
    $instance->configFactory = $container->get('config.factory');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getResponseContent(string $url): string {
    // Grab the profiles API settings from configuration.
    $config = $this->configFactory->get('az_person_profiles_import.settings');
    $endpoint = $config->get('endpoint');
    $apikey = $config->get('apikey');

    // For this fetcher, the supplied URL is the netid.
    $netid = $url;
    // Construct the API call.
    $url = $endpoint . '/get/' . urlencode($netid) . '?apikey=' . urlencode($apikey);
    try {
      $body = (string) $this->getResponse($url)->getBody();
    }
    catch (MigrateException | RequestException $e) {
      // Response from API had no data.
      $json = ['Person' => ['netid' => $netid]];
      $body = json_encode($json);
    }
    return $body;
  }

  /**
   * {@inheritdoc}
   */
  public function getNextUrls(string $url): array {
    return [];
  }

}
