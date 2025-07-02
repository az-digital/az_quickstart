<?php

namespace Drupal\config_sync\Config;

use Drupal\Core\Config\CachedStorage;
use Drupal\Core\Config\StorageComparer;
use Drupal\Core\Config\StorageInterface;

/**
 * Defines a settable config storage comparer.
 */
class SettableStorageComparer extends StorageComparer {

  /**
   * Sets the source storage used to discover configuration changes.
   *
   * @param \Drupal\Core\Config\StorageInterface $storage
   *   The storage to set as the source.
   *
   * @return $this
   */
  public function setSourceStorage(StorageInterface $storage) {
    // Reset the static configuration data cache.
    $this->sourceCacheStorage->deleteAll();
    $this->sourceNames = [];

    $this->sourceStorage = new CachedStorage(
      $storage,
      $this->sourceCacheStorage
    );

    $this->changelist = [StorageInterface::DEFAULT_COLLECTION => $this->getEmptyChangelist()];

    return $this;
  }

  /**
   * Sets the target storage used to discover configuration changes.
   *
   * @param \Drupal\Core\Config\StorageInterface $storage
   *   The storage to set as the target.
   *
   * @return $this
   */
  public function setTargetStorage(StorageInterface $storage) {
    // Reset the static configuration data cache.
    $this->targetCacheStorage->deleteAll();
    $this->targetNames = [];

    $this->targetStorage = new CachedStorage(
      $storage,
      $this->targetCacheStorage
    );

    $this->changelist = [StorageInterface::DEFAULT_COLLECTION => $this->getEmptyChangelist()];

    return $this;
  }

}
