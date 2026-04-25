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
 * Contains constants and helpers for Trellis Opportunities.
 */
final class TrellisHelper {

  /**
   * API base path.
   *
   * @var string
   */
  public static $apiBasePath = '/ws/rest/programs/v1/programinfo/';

  /**
   * API search path.
   *
   * @var string
   */
  public static $apiSearchPath = '/ws/rest/programs/v1/searchprogram/';

  /**
   * Trellis Opportunity view URL prefix.
   *
   * @var string
   */
  public static $opportunityViewBasePath = 'https://ua-trellis.lightning.force.com/lightning/r/conference360__Event__c/';

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
    // TEMP: caching disabled for debugging — re-enable by restoring the block below.
    $key = 'az_trellis_opportunities.search:' . Crypt::hashBase64(serialize($query));
    // $cached = $this->cache->get($key);
    // if ($cached !== FALSE) {
    //   return $cached->data;
    // }

    $url = $this->getOpportunitySearchEndpoint();
    // Boomi's load balancer intermittently routes to a backend that returns
    // 404 "No such path". Retry up to 3 times with brief backoff so a single
    // bad-route doesn't surface as "No results" to the user.
    $maxAttempts = 3;
    $succeeded = FALSE;
    for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
      try {
        $response = $this->httpClient->request('GET', $url, [
          'query' => $query,
          'timeout' => 10,
        ]);
        $status = $response->getStatusCode();
        if ($status === 200) {
          $json = (string) $response->getBody();
          $json = json_decode($json, TRUE);
          if ($json !== NULL) {
            $ids = $json['data']['Program_Cycle_IDs'] ?? [];
            sort($ids);
            // Only cache non-empty results — caching [] locks out a working
            // search for the full 30-minute TTL if the API had a hiccup.
            if (!empty($ids)) {
              $expire = time() + 1800;
              $this->cache->set($key, $ids, $expire);
            }
            $succeeded = TRUE;
            if ($attempt > 1) {
              \Drupal::logger('az_opportunity_trellis')->info('Trellis search succeeded on attempt @attempt of @max. Query: @query', [
                '@attempt' => $attempt,
                '@max' => $maxAttempts,
                '@query' => http_build_query($query),
              ]);
            }
            break;
          }
        }
        else {
          \Drupal::logger('az_opportunity_trellis')->warning('Trellis search returned non-200 status @status on attempt @attempt of @max. Query: @query', [
            '@status' => $status,
            '@attempt' => $attempt,
            '@max' => $maxAttempts,
            '@query' => http_build_query($query),
          ]);
        }
      }
      catch (GuzzleException $e) {
        \Drupal::logger('az_opportunity_trellis')->warning('Trellis search attempt @attempt of @max failed: @message. Query: @query', [
          '@attempt' => $attempt,
          '@max' => $maxAttempts,
          '@message' => $e->getMessage(),
          '@query' => http_build_query($query),
        ]);
      }
      if ($attempt < $maxAttempts) {
        usleep(200000);
      }
    }
    if (!$succeeded) {
      \Drupal::logger('az_opportunity_trellis')->error('Trellis search failed after @max attempts. Query: @query', [
        '@max' => $maxAttempts,
        '@query' => http_build_query($query),
      ]);
    }
    return $ids;
  }

  /**
   * Returns opportunity data for an array of ids.
   *
   * @param array $trellis_ids
   *   Trellis Opportunity IDs in an array.
   *
   * @return array
   *   Opportunity data.
   */
  public function getOpportunities(array $trellis_ids) {
    $opportunities = [];
    $fetch = [];
    $url = $this->getOpportunityEndpoint();
    // Remove any duplicate ids to mimic remote API.
    $trellis_ids = array_unique($trellis_ids);
    // TEMP: caching disabled for debugging — re-enable by restoring the block below.
    // foreach ($trellis_ids as $trellis_id) {
    //   $cached = $this->getTrellisCache($trellis_id);
    //   if ($cached === FALSE) {
    //     $fetch[] = $trellis_id;
    //   }
    //   else {
    //     $opportunities[] = $cached;
    //   }
    // }
    $fetch = array_values($trellis_ids);
    // Fetch opportunities we did not have cached. The Trellis/Boomi data POST
    // is intermittently flaky — retry up to 3 times with brief backoff before
    // giving up so a transient hiccup doesn't surface as "No results".
    if (!empty($fetch)) {
      $data = ['ids' => implode(',', $fetch)];
      $maxAttempts = 3;
      $succeeded = FALSE;
      for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
        try {
          $response = $this->httpClient->request('POST', $url, [
            'json' => $data,
            'timeout' => 10,
          ]);
          $status = $response->getStatusCode();
          if ($status === 200) {
            $json = (string) $response->getBody();
            $json = json_decode($json, TRUE);
            if ($json !== NULL) {
              $results = $json['data'] ?? [];
              foreach ($results as $result) {
                // Cache the opportunity and add it to the list.
                $opportunities[] = $this->setTrellisCache($result);
              }
              $succeeded = TRUE;
              if ($attempt > 1) {
                \Drupal::logger('az_opportunity_trellis')->info('Trellis data fetch succeeded on attempt @attempt of @max for @count ids.', [
                  '@attempt' => $attempt,
                  '@max' => $maxAttempts,
                  '@count' => count($fetch),
                ]);
              }
              break;
            }
          }
          else {
            \Drupal::logger('az_opportunity_trellis')->warning('Trellis data fetch returned non-200 status @status on attempt @attempt of @max.', [
              '@status' => $status,
              '@attempt' => $attempt,
              '@max' => $maxAttempts,
            ]);
          }
        }
        catch (GuzzleException $e) {
          \Drupal::logger('az_opportunity_trellis')->warning('Trellis data fetch attempt @attempt of @max failed: @message', [
            '@attempt' => $attempt,
            '@max' => $maxAttempts,
            '@message' => $e->getMessage(),
          ]);
        }
        if ($attempt < $maxAttempts) {
          // 200ms backoff between retries.
          usleep(200000);
        }
      }
      if (!$succeeded) {
        \Drupal::logger('az_opportunity_trellis')->error('Trellis data fetch failed after @max attempts for @count ids.', [
          '@max' => $maxAttempts,
          '@count' => count($fetch),
        ]);
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
    $imports = $this->entityTypeManager->getStorage('az_opp_recurring_import_rule')->loadByProperties([
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
    if (!$view) {
      return $mappings;
    }

    // Ensure we are on a display that has filter handlers.
    if (!$view->getDisplay()) {
      $display_id = $view->current_display ?? 'default';
      if (!$display_id) {
        $display_id = 'default';
      }
      $view->setDisplay($display_id);
    }

    $display = $view->getDisplay();
    if (!$display) {
      return $mappings;
    }

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
    $key = 'az_trellis_opportunities:' . $trellis_id;
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
      $key = 'az_trellis_opportunities:' . $opportunity['Id'];
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
