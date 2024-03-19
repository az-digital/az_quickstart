<?php

namespace Drupal\az_http;

use Drupal\Core\Cache\CacheBackendInterface;
use Kevinrob\GuzzleCache\CacheEntry;
use Kevinrob\GuzzleCache\Storage\CacheStorageInterface;

/**
 * Provides a Drupal cache backend for the Guzzle caching middleware.
 */
class AZHttpGuzzleCacheStorage implements CacheStorageInterface {

  /**
   * The Drupal cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * Constructs a new AZHttpGuzzleCacheStorage.
   *
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The Drupal cache backend.
   */
  public function __construct(CacheBackendInterface $cache) {
    $this->cache = $cache;
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
