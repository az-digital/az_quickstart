<?php

namespace Drupal\config_filter;

use Drupal\Core\Config\StorageInterface;
use Drupal\config_filter\Config\FilteredStorage;

/**
 * Class ConfigFilterFactory.
 */
class ConfigFilterStorageFactory {

  /**
   * The decorated sync config storage.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $sync;

  /**
   * The filter managers to load the filters from.
   *
   * @var \Drupal\config_filter\ConfigFilterManagerInterface[]
   */
  protected $managers = [];

  /**
   * ConfigFilterFactory constructor.
   *
   * @param \Drupal\Core\Config\StorageInterface $sync
   *   The original sync storage which is decorated by our filtered storage.
   */
  public function __construct(StorageInterface $sync) {
    $this->sync = $sync;
  }

  /**
   * Add a config filter manager.
   *
   * @param \Drupal\config_filter\ConfigFilterManagerInterface $manager
   *   The ConfigFilter plugin manager.
   */
  public function addConfigFilterManager(ConfigFilterManagerInterface $manager) {
    $this->managers[] = $manager;
  }

  /**
   * Get the sync storage Drupal uses.
   *
   * @return \Drupal\config_filter\Config\FilteredStorageInterface
   *   The decorated sync config storage.
   */
  public function getSync() {
    return $this->getFilteredStorage($this->sync, ['config.storage.sync']);
  }

  /**
   * Get the sync storage Drupal uses and exclude some plugins.
   *
   * @param string[] $excluded
   *   The ids of filters to exclude.
   *
   * @return \Drupal\config_filter\Config\FilteredStorageInterface
   *   The decorated sync config storage.
   */
  public function getSyncWithoutExcluded(array $excluded) {
    return $this->getFilteredStorage($this->sync, ['config.storage.sync'], $excluded);
  }

  /**
   * Get a decorated storage with filters applied.
   *
   * @param \Drupal\Core\Config\StorageInterface $storage
   *   The storage to decorate.
   * @param string[] $storage_names
   *   The names of the storage, so the correct filters can be applied.
   * @param string[] $excluded
   *   The ids of filters to exclude.
   *
   * @return \Drupal\config_filter\Config\FilteredStorageInterface
   *   The decorated storage with the filters applied.
   */
  public function getFilteredStorage(StorageInterface $storage, array $storage_names, array $excluded = []) {
    $filters = [];
    foreach ($this->managers as $manager) {
      // Filters from managers that come first will not be overwritten by
      // filters from lower priority managers.
      $filters = $filters + $manager->getFiltersForStorages($storage_names, $excluded);
    }
    return new FilteredStorage($storage, $filters);
  }

}
