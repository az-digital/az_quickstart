<?php

namespace Drupal\az_http\Strategy;

use Kevinrob\GuzzleCache\CacheEntry;
use Kevinrob\GuzzleCache\KeyValueHttpHeader;
use Kevinrob\GuzzleCache\Storage\CacheStorageInterface;
use Kevinrob\GuzzleCache\Strategy\PrivateCacheStrategy;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * This strategy respects cache headers but has a default fallback.
 */
class AZPoliteCacheStrategy extends PrivateCacheStrategy {

  /**
   * @var int
   */
  protected $defaultTtl;

  /**
   * Construct a cache strategy with a default TTL.
   *
   * @param \Kevinrob\GuzzleCache\Storage\CacheStorageInterface $cache
   *   The cache backend to use.
   * @param int $defaultTtl
   *   A default TTL in absence of actual cache headers.
   */
  public function __construct(CacheStorageInterface $cache = NULL, $defaultTtl = 600) {
    $this->defaultTtl = $defaultTtl;
    parent::__construct($cache);
  }

  /**
   * Get the cache object for the given request and response.
   *
   * @param \Psr\Http\Message\RequestInterface $request
   *   Request object that initated the request.
   * @param \Psr\Http\Message\ResponseInterface $response
   *   The response to the request.
   *
   * @return \Kevinrob\GuzzleCache\CacheEntry|null
   *   Entry to save, null if can't cache it
   */
  protected function getCacheObject(RequestInterface $request, ResponseInterface $response) {

    if (!isset($this->statusAccepted[$response->getStatusCode()])) {
      // Don't cache it for invalid return codes.
      return;
    }

    $cacheControl = new KeyValueHttpHeader($response->getHeader('Cache-Control'));
    $varyHeader = new KeyValueHttpHeader($response->getHeader('Vary'));

    if ($varyHeader->has('*')) {
      // This will never match with a request.
      return;
    }

    // @todo Determine our policy on other headers to avoid caching, such as Authorization.
    if ($cacheControl->has('no-store')) {
      // No store allowed.
      return;
    }

    if ($cacheControl->has('no-cache')) {
      // Stale response see RFC7234 section 5.2.1.4.
      $entry = new CacheEntry($request, $response, new \DateTime('-1 seconds'));

      return $entry->hasValidationInformation() ? $entry : NULL;
    }

    foreach ($this->ageKey as $key) {
      if ($cacheControl->has($key)) {
        return new CacheEntry(
          $request,
          $response,
          new \DateTime('+' . (int) $cacheControl->get($key) . 'seconds')
        );
      }
    }

    if ($response->hasHeader('Expires')) {
      $expireAt = \DateTime::createFromFormat(\DateTime::RFC1123, $response->getHeaderLine('Expires'));
      if ($expireAt !== FALSE) {
        return new CacheEntry(
          $request,
          $response,
          $expireAt
        );
      }
    }

    $ttl = $this->defaultTtl;
    return new CacheEntry($request, $response, new \DateTime(sprintf('+%d seconds', $ttl)));
  }

}
