<?php

namespace Drupal\az_core;

use Drupal\Core\Config\ConfigFactoryInterface;
use Kevinrob\GuzzleCache\CacheMiddleware;
use Kevinrob\GuzzleCache\Strategy\CacheStrategyInterface;
use Kevinrob\GuzzleCache\Strategy\NullCacheStrategy;

/**
 * Provides a Drupal cache backend for the Guzzle caching middleware.
 */
class AZCoreGuzzleCacheMiddleware {

  /**
   * The GuzzleCache strategy.
   *
   * @var \Kevinrob\GuzzleCache\Strategy\CacheStrategyInterface
   */
  protected $politeCacheStrategy;

  /**
   * Drupal\Core\Config\ConfigFactoryInterface definition.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a new AZCoreGuzzleCacheMiddleware.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The Drupal config factory.
   * @param \Drupal\az_core\Strategy\AZPoliteCacheStrategy $polite_cache_strategy
   *   The GuzzleCache storage.
   */
  public function __construct(ConfigFactoryInterface $config_factory, CacheStrategyInterface $polite_cache_strategy) {
    $this->configFactory = $config_factory;
    $this->politeCacheStrategy = $polite_cache_strategy;
  }

  /**
   * Provide Middleware for a polite TTL cache or no cache, based on config.
   *
   * @return \Kevinrob\GuzzleCache\CacheMiddleware
   *   A Middleware to add to the stack.
   */
  public function __invoke(): CacheMiddleware {

    // Only use the cache if the settings allow for it.
    if (!empty($this->configFactory->get('az_core.settings')->get('migrations.http_cached'))) {
      $middleware = $this->politeCacheStrategy;
    }
    else {
      $middleware = new CacheMiddleware(new NullCacheStrategy());
    }
    return $middleware;
  }

}
