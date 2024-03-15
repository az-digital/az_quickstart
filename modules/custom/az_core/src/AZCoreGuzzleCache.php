<?php

namespace Drupal\az_core;

use Drupal\az_core\Strategy\AZPoliteCacheStrategy;
use Drupal\Core\Cache\BackendChain;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Cache\MemoryBackend;
use Drupal\Core\Config\ConfigFactoryInterface;
use Kevinrob\GuzzleCache\CacheEntry;
use Kevinrob\GuzzleCache\CacheMiddleware;
use Kevinrob\GuzzleCache\Storage\CacheStorageInterface;
use Kevinrob\GuzzleCache\Strategy\NullCacheStrategy;

/**
 * Provides a Drupal cache backend for the Guzzle caching middleware.
 */
class AZCoreGuzzleCache implements CacheStorageInterface {

  /**
   * The Drupal cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * Drupal\Core\Config\ConfigFactoryInterface definition.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Cache default TTL in the case where there are no cache headers.
   *
   * @var int
   */
  protected $ttl;

  /**
   * Constructs a new AZCoreGuzzleCache.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The Drupal config factory.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The Drupal cache backend.
   * @param int $ttl
   *   Default cache TTL when there are no cache headers.
   */
  public function __construct(ConfigFactoryInterface $config_factory, CacheBackendInterface $cache, $ttl) {
    $this->configFactory = $config_factory;
    $this->cache = $cache;
    $this->ttl = $ttl;
  }

  /**
   * {@inheritdoc}
   */
  public function __invoke(): CacheMiddleware {

    // Only use the cache if the settings allow for it.
    if (!empty($this->configFactory->get('az_core.settings')->get('migrations.http_cached'))) {
      $cache = new BackendChain();
      $cache->appendBackend(new MemoryBackend());
      $cache->appendBackend($this->cache);
      $cache = new AZCoreGuzzleCache($this->configFactory, $cache, $this->ttl);
      $middleware = new CacheMiddleware(new AZPoliteCacheStrategy($cache, $this->ttl));
    }
    else {
      $middleware = new CacheMiddleware(new NullCacheStrategy());
    }
    return $middleware;
  }

  /**
   * {@inheritdoc}
   */
  public function fetch($key) {
    $item = $this->cache->get($key);
    return $item ? $item->data : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function save($key, CacheEntry $data) {
    $expires = $data->getStaleAt()->getTimestamp();
    $this->cache->set($key, $data, $expires);
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function delete($key) {
    $this->cache->delete($key);
    return TRUE;
  }

}
