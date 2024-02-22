<?php

declare(strict_types = 1);

namespace Drupal\az_migration_http_cache\Plugin\migrate_plus\data_fetcher;

use Drupal\guzzle_cache\DrupalGuzzleCache;
use Drupal\migrate_plus\Plugin\migrate_plus\data_fetcher\Http;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use Kevinrob\GuzzleCache\CacheMiddleware;
use Kevinrob\GuzzleCache\Strategy\GreedyCacheStrategy;

/**
 * Retrieve data over an HTTP connection for migration.
 *
 * Example:
 *
 * @code
 * source:
 *   plugin: url
 *   data_fetcher_plugin: az_http_cached
 *   headers:
 *     Accept: application/json
 *     User-Agent: Internet Explorer 6
 *     Authorization-Key: secret
 *     Arbitrary-Header: foobarbaz
 * @endcode
 *
 * @DataFetcher(
 *   id = "az_http_cached",
 *   title = @Translation("Quickstart HTTP Fetcher with cache")
 * )
 */
class AzHttpCache extends Http {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
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
    // Initialize the client with the handler.
    $this->httpClient = new Client(['handler' => $stack]);
  }

}
