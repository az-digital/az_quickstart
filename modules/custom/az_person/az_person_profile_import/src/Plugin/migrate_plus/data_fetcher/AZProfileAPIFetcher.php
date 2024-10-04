<?php

declare(strict_types=1);

namespace Drupal\az_person_profile_import\Plugin\migrate_plus\data_fetcher;

use Drupal\Component\Utility\Crypt;
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
 *   data_fetcher_plugin: az_profile_api_fetcher
 *   headers:
 *     Accept: application/json
 *     User-Agent: Internet Explorer 6
 *     Authorization-Key: secret
 *     Arbitrary-Header: foobarbaz
 * @endcode
 *
 * @DataFetcher(
 *   id = "az_profile_api_fetcher",
 *   title = @Translation("Quickstart HTTP Fetcher with optional cache")
 * )
 */
class AZProfileAPIFetcher extends Http {

  const PROFILE_API_CACHE_KEY = 'az_profile_api_fetcher_netid_cache:';
  const PROFILE_API_WARNING_EXPIRY = 1800;

  /**
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * @var \Drupal\Core\Messenger\Messenger
   */
  protected $messenger;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    $instance = new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
    );

    $instance->cache = $container->get('cache.default');
    $instance->messenger = $container->get('messenger');
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
    $config = $this->configFactory->get('az_person_profile_import.settings');
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
      $body = '{}';
      // Generate a cache key for this netid.
      // We don't want to constantly warn in the batch iterator.
      $cache_key = self::PROFILE_API_CACHE_KEY . Crypt::hashBase64($netid);
      $cached = $this->cache->get($cache_key);
      // Emit a message only if we haven't seen this warning yet.
      if ($cached === FALSE) {
        $this->messenger->addWarning($this->t('NetID %netid was not found in the Profiles API.', ['%netid' => $netid]));
      }
      // Mark that we've seen a failure for this netid recently.
      $this->cache->set($cache_key, TRUE, time() + self::PROFILE_API_WARNING_EXPIRY);
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
