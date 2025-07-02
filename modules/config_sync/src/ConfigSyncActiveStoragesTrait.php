<?php

namespace Drupal\config_sync;

use Drupal\Core\Config\StorageInterface;

/**
 * Provides a utility method for fetching the active storage by collection.
 */
trait ConfigSyncActiveStoragesTrait {

  /**
   * The active configuration storages, keyed by collection.
   *
   * @var \Drupal\Core\Config\StorageInterface[]
   */
  protected $activeStorages;

  /**
   * Gets the configuration storage that provides the active configuration.
   *
   * @param string $collection
   *   (optional) The configuration collection. Defaults to the default
   *   collection.
   *
   * @return \Drupal\Core\Config\StorageInterface
   *   The configuration storage that provides the default configuration.
   */
  protected function getActiveStorages($collection = StorageInterface::DEFAULT_COLLECTION) {
    if (!isset($this->activeStorages[$collection])) {
      $this->activeStorages[$collection] = reset($this->activeStorages)
        ->createCollection($collection);
    }
    return $this->activeStorages[$collection];
  }

}
