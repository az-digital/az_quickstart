<?php

namespace Drupal\az_eds;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;

/**
 * Provides overrides for EDS queries.
 */
class AZEDSConfigOverrides implements ConfigFactoryOverrideInterface {

  // Attributes required by EDS import migration.
  const ATTRIBUTES = 'uid,preferredPriorityGivenName,preferredPrioritySn,employeeTitle,pronouns,employeePhone,mail,isMemberOf,studentInfoReleaseCode';

  // Default filters in addition to user specified filters.
  // @todo determine scope.
  const DIRECTORY_FILTER = '(!(isMemberOf=arizona.edu:services:enterprise:ldap.arizona.edu:phonebook-exclude))';

  /**
   * Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cacheBackend;

  /**
   * Creates a new ModuleConfigOverrides instance.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   The cache backend.
   */
  public function __construct(ConfigFactoryInterface $config_factory, CacheBackendInterface $cache_backend) {
    $this->configFactory = $config_factory;
    $this->cacheBackend = $cache_backend;
  }

  /**
   * {@inheritdoc}
   */
  public function loadOverrides($names) {
    $overrides = [];
    // We override queries for the EDS LDAP server.
    foreach ($names as $name) {
      // Check if we're loading a query.
      if (str_starts_with($name, 'ldap_query.ldap_query_entity.')) {
        // Get the real query so we can make some determinations about it.
        $config = $this->configFactory->getEditable($name);

        // See if this is an EDS query. If so, we have overrides.
        if ($config->get('server_id') === 'az_eds') {
          $original_filter = $config->get('filter') ?? '';
          // Concatenate our existing filters.
          $filter = '(&' . $original_filter . self::DIRECTORY_FILTER . ')';
          $overrides[$name]['filter'] = $filter;
          // Add required migration attributes.
          $overrides[$name]['attributes'] = self::ATTRIBUTES;
        }
      }
    }

    // Return overrides for EDS queries.
    return $overrides;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheSuffix() {
    return 'az_eds_config_override';
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata($name) {
    return new CacheableMetadata();
  }

  /**
   * {@inheritdoc}
   */
  public function createConfigObject($name, $collection = StorageInterface::DEFAULT_COLLECTION) {
    return NULL;
  }

}
