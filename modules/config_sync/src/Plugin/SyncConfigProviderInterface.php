<?php

namespace Drupal\config_sync\Plugin;

use Drupal\config_provider\Plugin\ConfigProviderInterface;
use Drupal\Core\Config\StorageInterface;

/**
 * Class for invoking configuration providers for snapshotting.
 */
interface SyncConfigProviderInterface extends ConfigProviderInterface {

  /**
   * Alters a configuration snapshot.
   *
   * Not intended to be called an install time, this method instead facilitates
   * determining what configuration updates are available.
   *
   * Implementing plugins should write configuration as appropriate to the
   * $snapshot_storage storage.
   *
   * @param \Drupal\Core\Config\StorageInterface $snapshot_storage
   *   The snapshot configuration storage.
   * @param \Drupal\Core\Extension\Extension[] $extensions
   *   (Optional) An associative array of Extension objects, keyed by extension
   *   name. If provided, data loaded will be limited to these extensions.
   *
   * @see \Drupal\config_provider\Plugin\ConfigProviderInterface\addInstallableConfig()
   */
  public function alterConfigSnapshot(StorageInterface $snapshot_storage, array $extensions = []);

}
