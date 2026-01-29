<?php

namespace Drupal\az_opportunity_trellis;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\az_opportunity_trellis\Plugin\views\filter\AZOpportunityTrellisViewsAttributeFilter;
use Drupal\views\Views;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Contains constants and helpers for Trellis Opportunity.
 */
final class TrellisHelper {

  /**
   * API base path.
   *
   * @var string
   */
  public static $apiBasePath = '/ws/rest/getopportunities/v3/opportunityinfo/';

  /**
   * API search path.
   *
   * @var string
   */
  public static $apiSearchPath = '/ws/rest/getopportunities/v3/searchopportunities/';

  /**
   * Trellis Opprtunities view URL prefix.
   *
   * @var string
   */
  public static $opportunityViewBasePath = 'https://ua-trellis.lightning.force.com/lightning/r/conference360__Opportunities__c/';

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
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new TrellisHelper object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The Drupal config factory.
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The HTTP client service.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache backend.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ClientInterface $http_client, CacheBackendInterface $cache, EntityTypeManagerInterface $entity_type_manager) {
    $this->configFactory = $config_factory;
    $this->httpClient = $http_client;
    $this->cache = $cache;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Search based on parameters for opportunities.
   *
   * @param array $query
   *   Query parameters to use in search.
   *
   * @return array
   *   Array of trellis opportunity ids.
   */
  public function searchOpportunities(array $query) {
    $ids = [];
    // Compute cache key of query.
    $key = 'az_trellis_opportunity.search:' . Crypt::hashBase64(serialize($query));
    $cached = $this->cache->get($key);
    // If we have this search cached, return it.
    if ($cached !== FALSE) {
      return $cached->data;
    }

    $url = $this->getOpportunitiesSearchEndpoint();
    try {
      // Run search request.
      $response = $this->httpClient->request('GET', $url, ['query' => $query]);
      if ($response->getStatusCode() === 200) {
        $json = (string) $response->getBody();
        $json = json_decode($json, TRUE);
        if ($json !== NULL) {
          $ids = $json['data']['Opportunities_IDs'] ?? [];
          // Ensure opportunities are in Id order.
          sort($ids);
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
   * Returns opportunity data for an array of ids.
   *
   * @param array $trellis_ids
   *   Trellis Opportunities IDs in an array.
   *
   * @return array
   *   Opportunities data.
   */
  public function getOpportunities(array $trellis_ids) {
    $opportunities = [];
    $fetch = [];
    $url = $this->getOpportunityEndpoint();
    // Remove any duplicate ids to mimic remote API.
    $trellis_ids = array_unique($trellis_ids);
    // Grab opportunities that are in cache.
    foreach ($trellis_ids as $trellis_id) {
      $cached = $this->getTrellisCache($trellis_id);
      if ($cached === FALSE) {
        $fetch[] = $trellis_id;
      }
      else {
        $opportunities[] = $cached;
      }
    }
    // Fetch opportunities we did not have cached.
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
              // Cache the opportunity and add it to the list.
              $opportunities[] = $this->setTrellisCache($result);
            }
          }
        }
      }
      catch (GuzzleException $e) {
      }
    }
    // Make sure opportunities are in Id order regardless of cached/fetched.
    usort($opportunities, function ($a, $b) {
      return strcmp($a['Id'], $b['Id']);
    });
    return $opportunities;
  }

  /**
   * Fetch the list of trellis opportunity ids currently imported.
   *
   * @return array
   *   Returns an array of opportunity ids.
   */
  public function getImportedOpportunityIds() {
    $nodeStorage = $this->entityTypeManager->getStorage('node');
    // Check for opportunities that have trellis ids.
    $query = $nodeStorage->getQuery()
      ->accessCheck(FALSE)
      ->condition('type', 'az_opportunity')
      ->exists('field_az_trellis_id');
    $nids = $query->execute();
    $nodes = $nodeStorage->loadMultiple($nids);

    $opportunity_api_ids = [];
    foreach ($nodes as $n) {
      $opportunity_api_ids[] = $n->get('field_az_trellis_id')->getString();
    }
    return $opportunity_api_ids;
  }

  /**
   * Fetch the recurring search list of ids to import.
   *
   * @return array
   *   Returns an array of opportunity ids.
   */
  public function getRecurringOpportunityIds() {
    // Find enabled import configurations.
    $imports = $this->entityTypeManager->getStorage('az_recurring_import_rule')->loadByProperties([
      'status' => [1, TRUE],
    ]);

    $opportunity_api_ids = [];
    foreach ($imports as $import) {
      /** @var \Drupal\az_opportunity_trellis\Entity\AZRecurringImportRule $import */
      $opportunity_api_ids += $import->getOpportunityIds();
    }
    // Remove duplicates in case searches overlapped.
    $opportunity_api_ids = array_unique($opportunity_api_ids);
    return $opportunity_api_ids;
  }

  /**
   * Return mapped array of api names of attributes.
   *
   * @return array
   *   The array of attribute ids mapped to API names.
   */
  public function getAttributeMappings() {
    $mappings = [];
    $view = Views::getView('az_opportunity_trellis_import');
    $display = $view->getDisplay() ?? NULL;
    $filters = $display->getHandlers('filter');
    foreach ($filters as $filter) {
      if ($filter instanceof AZOpportunityTrellisViewsAttributeFilter) {
        $mappings += $filter->getApiMapping();
      }
    }
    return $mappings;
  }

  /**
   * Returns the cache for an opportunity if possible.
   *
   * @param string $trellis_id
   *   The trellis id of the opportunity.
   *
   * @return mixed
   *   The cached data for the opportunity, or FALSE.
   */
  protected function getTrellisCache(string $trellis_id) {
    $key = 'az_trellis_opportunity:' . $trellis_id;
    return $this->cache->get($key)->data ?? FALSE;
  }

  /**
   * Sets the cache for an opportunity if possible.
   *
   * @param array $opportunity
   *   The opportunity data result.
   *
   * @return array
   *   The data for the opportunity.
   */
  protected function setTrellisCache(array $opportunity) {
    if (!empty($opportunity['Id'])) {
      $key = 'az_trellis_opportunity:' . $opportunity['Id'];
      // @todo determine cache expiration.
      $expire = time() + 1800;
      $this->cache->set($key, $opportunity, $expire);
    }
    return $opportunity;
  }

  /**
   * Returns API URL for given Trellis Opportunity ID.
   *
   * @param string $trellis_id
   *   Trellis Opportunity ID.
   *
   * @return string
   *   Opportunity API URL.
   */
  public function getOpportunityUrl($trellis_id) {
    $hostname = $this->configFactory->get('az_opportunity_trellis.settings')->get('api_hostname');
    return 'https://' . $hostname . self::$apiBasePath . $trellis_id;
  }

  /**
   * Returns API URL.
   *
   * @return string
   *   Opportunity API URL.
   */
  public function getOpportunityEndpoint() {
    $hostname = $this->configFactory->get('az_opportunity_trellis.settings')->get('api_hostname');
    return 'https://' . $hostname . self::$apiBasePath;
  }

  /**
   * Returns API search URL.
   *
   * @return string
   *   Opportunity API search URL.
   */
  public function getOpportunitySearchEndpoint() {
    $hostname = $this->configFactory->get('az_opportunity_trellis.settings')->get('api_hostname');
    return 'https://' . $hostname . self::$apiSearchPath;
  }

}
