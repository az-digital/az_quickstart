<?php

declare(strict_types = 1);

namespace Drupal\az_core\Plugin\migrate_plus\data_fetcher;

use Drupal\guzzle_cache\DrupalGuzzleCache;
use Drupal\migrate_plus\Plugin\migrate_plus\data_fetcher\Http;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use Kevinrob\GuzzleCache\CacheMiddleware;
use Kevinrob\GuzzleCache\Strategy\GreedyCacheStrategy;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Retrieve data over an HTTP connection for migration.
 *
 * Example:
 *
 * @code
 * source:
 *   plugin: url
 *   data_fetcher_plugin: az_core_http
 *   headers:
 *     Accept: application/json
 *     User-Agent: Internet Explorer 6
 *     Authorization-Key: secret
 *     Arbitrary-Header: foobarbaz
 * @endcode
 *
 * @DataFetcher(
 *   id = "az_core_http",
 *   title = @Translation("Quickstart HTTP Fetcher with optional cache")
 * )
 */
class AzHttp extends Http {

  const MAX_REQUESTS = 5;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $az_core_config = \Drupal::config('az_core.settings');
    $cache_enabled = $az_core_config->get('migrations.http_cached');
    // If cache is not enabled, skip and fall back on parent implementation.
    if ($cache_enabled) {
      // Grab a new Guzzle HandlerStack.
      $stack = HandlerStack::create();
      // Get our custom cache bin.
      $cache = new DrupalGuzzleCache(\Drupal::service('cache.az_migration_http_cache'));
      // Push our custom cache bin to the stack.
      $stack->push(
        new CacheMiddleware(
          // Greedy implies cache control headers in API responses are ignored.
          // Ideally we should NOT do this. It is preferred to ensure that APIs
          // have reasonable cache control headers.
          new GreedyCacheStrategy($cache, 600)
        ),
        'cache'
      );
      $stack->push(Middleware::retry([__CLASS__, 'decideRetry']));
      // Initialize the client with the handler.
      $this->httpClient = new Client(['handler' => $stack]);
    }
  }

  /**
   * Decide whether to retry a request.
   *
   * @param int $retries
   *   The number of retries that have taken place.
   * @param \Psr\Http\Message\RequestInterface $request
   *   The request that was last made.
   * @param \Psr\Http\Message\ResponseInterface $response
   *   The response, if one occurred.
   * @param \GuzzleHttp\Exception\RequestException $exception
   *   A potential exception that occurred during the request.
   *
   * @return bool
   *   Whether to retry the request or not.
   */
  public static function decideRetry($retries, RequestInterface $request, ResponseInterface $response = NULL, RequestException $exception = NULL) {
    // Abort if we are beyond our limit.
    if ($retries >= self::MAX_REQUESTS) {
      return FALSE;
    }

    // Retry if an exception occurred.
    if (!empty($exception)) {
      return TRUE;
    }

    // Retry if we received a response that indicated unavailability.
    if (!empty($response) && $response->getStatusCode() >= 500) {
      return TRUE;
    }

    return FALSE;
  }

}
