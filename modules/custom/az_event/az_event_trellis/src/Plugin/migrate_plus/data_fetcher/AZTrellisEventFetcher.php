<?php

declare(strict_types = 1);

namespace Drupal\az_event_trellis\Plugin\migrate_plus\data_fetcher;

use Psr\Http\Message\ResponseInterface;
use Drupal\migrate\MigrateException;
use GuzzleHttp\Exception\RequestException;
use Drupal\migrate_plus\Plugin\migrate_plus\data_fetcher\Http;

/**
 * Retrieve data over an HTTP connection for migration via an HTTP POST.
 *
 * Example:
 *
 * @code
 * source:
 *   plugin: url
 *   data_fetcher_plugin: az_trellis_http
 *   headers:
 *     Accept: application/json
 *     User-Agent: Internet Explorer 6
 *     Authorization-Key: secret
 *     Arbitrary-Header: foobarbaz
 * @endcode
 *
 * @DataFetcher(
 *   id = "az_trellis_http",
 *   title = @Translation("Trellis Events Fetcher")
 * )
 */
class AZTrellisEventFetcher extends Http {

  /**
   * Trellis ids to fetch.
   *
   * @var array
   */
  protected $trellisIds;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->trellisIds = $configuration['trellis_ids'] ?? [];
  }

  /**
   * {@inheritdoc}
   */
  public function getResponse($url): ResponseInterface {
    try {
      $options = ['headers' => $this->getRequestHeaders()];
      if (!empty($this->configuration['authentication'])) {
        $options = array_merge($options, $this->getAuthenticationPlugin()->getAuthenticationOptions());
      }
      if (!empty($this->configuration['request_options'])) {
        $options = array_merge($options, $this->configuration['request_options']);
      }
      $options['json'] = ['ids' => implode(',', $this->trellisIds)];
      $response = $this->httpClient->post($url, $options);
      // @phpstan-ignore-next-line
      if (empty($response)) {
        throw new MigrateException('No response at ' . $url . '.');
      }
    }
    catch (RequestException $e) {
      throw new MigrateException('Error message: ' . $e->getMessage() . ' at ' . $url . '.');
    }
    return $response;
  }

}
