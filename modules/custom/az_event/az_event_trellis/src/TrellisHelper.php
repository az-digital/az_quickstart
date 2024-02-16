<?php

namespace Drupal\az_event_trellis;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Contains constants and helpers for Trellis Events.
 */
final class TrellisHelper {

  /**
   * API base path.
   *
   * @var string
   */
  public static $apiBasePath = '/ws/rest/getevents/v2/eventinfo/';

  /**
   * API search path.
   *
   * @var string
   */
  public static $apiSearchPath = '/ws/rest/getevents/v2/searchevents/';

  /**
   * Trellis Event view URL prefix.
   *
   * @var string
   */
  public static $eventViewBasePath = 'https://ua-trellis.lightning.force.com/lightning/r/conference360__Event__c/';

  /**
   * The Drupal cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * An http client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * The node storage.
   *
   * @var \Drupal\node\NodeStorageInterface
   */
  protected $nodeStorage;

  /**
   * Constructs a new TrellisHelper object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The Drupal config factory.
   * @param \GuzzleHttp\ClientInterface $httpClient
   *   The HTTP client service.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache backend.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ClientInterface $httpClient, CacheBackendInterface $cache, EntityTypeManagerInterface $entityTypeManager) {
    $this->configFactory = $config_factory;
    $this->httpClient = $httpClient;
    $this->cache = $cache;
    $this->nodeStorage = $entityTypeManager->getStorage('node');
  }

  /**
   * Search based on parameters for events.
   *
   * @param array $query
   *   Query parameters to use in search.
   *
   * @return array
   *   Array of trellis event ids.
   */
  public function searchEvents(array $query) {
    $ids = [];
    // Compute cache key of query.
    $key = 'az_trellis_event.search:' . Crypt::hashBase64(serialize($query));
    $cached = $this->cache->get($key);
    // If we have this search cached, return it.
    if ($cached !== FALSE) {
      return $cached->data;
    }

    $url = $this->getEventSearchEndpoint();
    try {
      // Run search request.
      $response = $this->httpClient->request('GET', $url, ['query' => $query]);
      if ($response->getStatusCode() === 200) {
        $json = (string) $response->getBody();
        $json = json_decode($json, TRUE);
        if ($json !== NULL) {
          $ids = $json['data']['Event_IDs'] ?? [];
          // @todo determine cache expiration.
          $expire = time() + 1800;
          // Cache search result.
          $this->cache->set($key, $ids, $expire);
        }
      }
    }
    catch (GuzzleException $e) {
    }
    return $ids;
  }

  /**
   * Returns event data for an array of ids.
   *
   * @param array $trellis_ids
   *   Trellis Event IDs in an array.
   *
   * @return array
   *   Event data.
   */
  public function getEvents(array $trellis_ids) {
    $events = [];
    $fetch = [];
    $url = $this->getEventEndpoint();
    // Grab events that are in cache.
    foreach ($trellis_ids as $trellis_id) {
      $cached = $this->getTrellisCache($trellis_id);
      if ($cached === FALSE) {
        $fetch[] = $trellis_id;
      }
      else {
        $events[] = $cached;
      }
    }
    // Fetch events we did not have cached.
    if (!empty($fetch)) {
      try {
        $data = ['ids' => implode(',', $fetch)];
        $response = $this->httpClient->request('POST', $url, ['json' => $data]);
        if ($response->getStatusCode() === 200) {
          $json = (string) $response->getBody();
          $json = json_decode($json, TRUE);
          if ($json !== NULL) {
            $results = $json['data'] ?? [];
            foreach ($results as $result) {
              // Cache the event and add it to the list.
              $events[] = $this->setTrellisCache($result);
            }
          }
        }
      }
      catch (GuzzleException $e) {
      }
    }
    return $events;
  }

  /**
   * Fetch the list of trellis event ids currently imported.
   *
   * @return array
   *   Returns an array of event ids.
   */
  public function getImportedEventIds() {
    // Check for events that have trellis ids.
    $query = \Drupal::entityQuery('node')
      ->accessCheck(FALSE)
      ->condition('type', 'az_event')
      ->exists('field_az_trellis_id');
    $nids = $query->execute();
    $node_storage = \Drupal::entityTypeManager()->getStorage('node');
    $nodes = $this->nodeStorage->loadMultiple($nids);

    $event_api_ids = [];
    foreach ($nodes as $n) {
      $event_api_ids[] = $n->get('field_az_trellis_id')->getString();
    }
    return $event_api_ids;
  }

  /**
   * Returns the cache for an event if possible.
   *
   * @param string $trellis_id
   *   The trellis id of the event.
   *
   * @return mixed
   *   The cached data for the event, or FALSE.
   */
  protected function getTrellisCache(string $trellis_id) {
    $key = 'az_trellis_event:' . $trellis_id;
    return $this->cache->get($key)->data ?? FALSE;
  }

  /**
   * Sets the cache for an event if possible.
   *
   * @param array $event
   *   The event data result.
   *
   * @return array
   *   The data for the event.
   */
  protected function setTrellisCache(array $event) {
    if (!empty($event['Id'])) {
      $key = 'az_trellis_event:' . $event['Id'];
      // @todo determine cache expiration.
      $expire = time() + 1800;
      $this->cache->set($key, $event, $expire);
    }
    return $event;
  }

  /**
   * Returns API URL for given Trellis Event ID.
   *
   * @param string $trellis_id
   *   Trellis Event ID.
   *
   * @return string
   *   Event API URL.
   */
  public function getEventUrl($trellis_id) {
    $hostname = $this->configFactory->get('az_event_trellis.settings')->get('api_hostname');
    return 'https://' . $hostname . self::$apiBasePath . $trellis_id;
  }

  /**
   * Returns API URL.
   *
   * @return string
   *   Event API URL.
   */
  public function getEventEndpoint() {
    $hostname = $this->configFactory->get('az_event_trellis.settings')->get('api_hostname');
    return 'https://' . $hostname . self::$apiBasePath;
  }

  /**
   * Returns API search URL.
   *
   * @return string
   *   Event API search URL.
   */
  public function getEventSearchEndpoint() {
    $hostname = $this->configFactory->get('az_event_trellis.settings')->get('api_hostname');
    return 'https://' . $hostname . self::$apiSearchPath;
  }

}
