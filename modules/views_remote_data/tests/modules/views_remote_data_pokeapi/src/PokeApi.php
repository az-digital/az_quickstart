<?php

declare(strict_types=1);

namespace Drupal\views_remote_data_pokeapi;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Cache\CacheBackendInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

/**
 * The PokeApi client.
 */
final class PokeApi {

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\Client
   */
  private Client $client;

  /**
   * The cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  private CacheBackendInterface $cache;

  /**
   * Constructs a new PokeApi object.
   *
   * @param \GuzzleHttp\Client $client
   *   The HTTP client.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache backend.
   */
  public function __construct(Client $client, CacheBackendInterface $cache) {
    $this->client = $client;
    $this->cache = $cache;
  }

  /**
   * List species from the API by name.
   *
   * @param int $offset
   *   The offset.
   * @param int $limit
   *   The offset.
   *
   * @return array
   *   The species.
   */
  public function listSpecies(int $offset, int $limit): array {
    return $this->get("https://pokeapi.co/api/v2/pokemon-species/?offset=$offset&limit=$limit");
  }

  /**
   * Get a species from the API by name.
   *
   * @param string $name
   *   The species name.
   *
   * @return array
   *   The species.
   */
  public function getSpecies(string $name) {
    return $this->get("https://pokeapi.co/api/v2/pokemon-species/$name");
  }

  /**
   * Gets results from the API.
   *
   * @param string $uri
   *   The uri.
   *
   * @return array
   *   The result.
   */
  public function get(string $uri): array {
    $cache_key = "pokeapi:$uri";
    if ($cache = $this->cache->get($cache_key)) {
      return $cache->data;
    }
    try {
      $response = $this->client->get($uri);
      $data = Json::decode((string) $response->getBody());
      $this->cache->set($cache_key, $data);
      return $data;
    }
    catch (RequestException $e) {
      return [];
    }
  }

}
