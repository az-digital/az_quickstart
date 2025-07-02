<?php

namespace Drupal\config_snapshot;

use Drupal\Core\Config\StorageInterface;
use Drupal\config_snapshot\Entity\ConfigSnapshot;

/**
 * Provides a utility method for working with configuration snapshot services.
 */
trait ConfigSnapshotStorageTrait {

  /**
   * Returns a configuration snapshot storage service.
   *
   * Uses a corresponding service if available while falling back to a new
   * storage object. The fallback ensures a storage is provided for a newly-
   * installed extension before the service container is rebuilt.
   *
   * @param string $snapshot_set
   *   The snapshot set.
   * @param string $extension_type
   *   The extension type.
   * @param string $extension_name
   *   The extension name.
   * @param string $collection
   *   (optional) The collection to store configuration in. Defaults to the
   *   default collection.
   * @param \Drupal\config_snapshot\Entity\ConfigSnapshot $config_snapshot
   *   (optional) The configuration snapshot.
   *
   * @return \Drupal\config_snapshot\ConfigSnapshotStorage
   *   A configuration snapshot storage.
   */
  protected function getConfigSnapshotStorage($snapshot_set, $extension_type, $extension_name, $collection = StorageInterface::DEFAULT_COLLECTION, ?ConfigSnapshot $config_snapshot = NULL) {
    // The service ID is for the default collection.
    $service_id = "config_snapshot.{$snapshot_set}.{$extension_type}.{$extension_name}";

    if (\Drupal::getContainer() && \Drupal::hasService($service_id)) {
      $storage = \Drupal::service($service_id);
      // Switch collections if needed.
      if ($collection !== StorageInterface::DEFAULT_COLLECTION) {
        $storage = $storage->createCollection($collection);
      }
    }
    else {
      $storage = new ConfigSnapshotStorage($snapshot_set, $extension_type, $extension_name, $collection, $config_snapshot);
    }

    return $storage;
  }

}
