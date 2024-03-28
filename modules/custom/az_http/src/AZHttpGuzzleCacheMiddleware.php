<?php

namespace Drupal\az_http;

use Kevinrob\GuzzleCache\CacheMiddleware;
use Kevinrob\GuzzleCache\Strategy\CacheStrategyInterface;

/**
 * Provides a Drupal cache backend for the Guzzle caching middleware.
 */
class AZHttpGuzzleCacheMiddleware {

  /**
   * The GuzzleCache strategy.
   *
   * @var \Kevinrob\GuzzleCache\Strategy\CacheStrategyInterface
   */
  protected $cacheStrategy;

  /**
   * Constructs a new AZHttpGuzzleCacheMiddleware.
   *
   * @param \Kevinrob\GuzzleCache\Strategy\CacheStrategyInterface $cacheStrategy
   *   The caching strategy to use.
   */
  public function __construct(CacheStrategyInterface $cacheStrategy) {
    $this->cacheStrategy = $cacheStrategy;
  }

  /**
   * Provide Middleware for a polite TTL cache or no cache, based on config.
   *
   * @return \Kevinrob\GuzzleCache\CacheMiddleware
   *   A Middleware to add to the stack.
   */
  public function __invoke(): CacheMiddleware {

    return new CacheMiddleware($this->cacheStrategy);
  }

}
