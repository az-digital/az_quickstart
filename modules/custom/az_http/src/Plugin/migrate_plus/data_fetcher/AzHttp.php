<?php

declare(strict_types=1);

namespace Drupal\az_http\Plugin\migrate_plus\data_fetcher;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\migrate\MigrateException;
use Drupal\migrate_plus\Plugin\migrate_plus\data_fetcher\Http;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Retrieve data over an HTTP connection for migration.
 *
 * Example:
 *
 * @code
 * source:
 *   plugin: url
 *   data_fetcher_plugin: az_http
 *   headers:
 *     Accept: application/json
 *     User-Agent: Internet Explorer 6
 *     Authorization-Key: secret
 *     Arbitrary-Header: foobarbaz
 * @endcode
 *
 * @DataFetcher(
 *   id = "az_http",
 *   title = @Translation("Quickstart HTTP Fetcher with optional cache")
 * )
 */
class AzHttp extends Http {

  /**
   * Provides cached HTTP requests via guzzle middlewear.
   *
   * @var \GuzzleHttp\Client
   */
  protected Client $cachedHttpClient;

  /**
   * Allows reading quickstart configuration settings.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    $instance = new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('http_client'),
      $container->get('plugin.manager.migrate_plus.authentication'),
    );

    // This plugin can use a cache-based HTTP client dependent on settings.
    $instance->cachedHttpClient = $container->get('az_http.http_client');
    // This plugin uses configuration to know whether to cache requests.
    $instance->configFactory = $container->get('config.factory');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getResponse($url): ResponseInterface {
    $client = $this->httpClient;
    $enabled = $this->configFactory->get('az_http.settings')->get('migrations.http_cached');
    if ($enabled) {
      // Only if the http cache is enabled, swap out for the middleware client.
      $client = $this->cachedHttpClient;
    }

    try {
      $options = ['headers' => $this->getRequestHeaders()];
      if (!empty($this->configuration['authentication'])) {
        $options = NestedArray::mergeDeep($options, $this->getAuthenticationPlugin()->getAuthenticationOptions($url));
      }
      if (!empty($this->configuration['request_options'])) {
        $options = NestedArray::mergeDeep($options, $this->configuration['request_options']);
      }
      $method = $this->configuration['method'] ?? 'GET';
      $response = $client->request($method, $url, $options);
    }
    catch (RequestException $e) {
      throw new MigrateException('Error message: ' . $e->getMessage() . ' at ' . $url . '.');
    }
    return $response;
  }

}
