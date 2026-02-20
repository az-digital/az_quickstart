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
   * The secrets checker service (if available).
   *
   * @var \Drupal\az_secrets\Service\SecretsChecker|null
   */
  protected $secretsChecker;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    /** @var \Drupal\az_person_profiles_import\Plugin\migrate_plus\data_fetcher\AZProfilesAPIFetcher $instance */
    $instance = parent::create(
      $container,
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

    // Try to get the secrets checker service if available.
    try {
      $instance->secretsChecker = $container->get('az_secrets.checker');
    }
    catch (ServiceNotFoundException $e) {
      // az_secrets module not enabled, will use config values.
      $instance->secretsChecker = NULL;
    }

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

    // Check if we should use secrets instead.
    if ($this->secretsChecker &&
        $this->secretsChecker->hasKeys(['az_profiles_api_endpoint', 'az_profiles_api_key'])) {
      $endpoint = $this->secretsChecker->getKeyValue('az_profiles_api_endpoint');
      $apikey = $this->secretsChecker->getKeyValue('az_profiles_api_key');
    }

    // For this fetcher, the supplied URL is the netid.
    $netid = $url;
    // Construct the API call.
    $url = $endpoint . '/get/' . urlencode($netid) . '?apikey=' . urlencode($apikey);
    try {
      $body = (string) $this->getResponse($url)->getBody();
    }
    catch (RequestException $e) {
      // Response from API had no data.
      $json = ['Person' => ['netid' => $netid]];
      $body = json_encode($json);
    }
    catch (MigrateException $e) {
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
