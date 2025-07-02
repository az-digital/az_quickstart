<?php

namespace Drupal\config_sync\Plugin;

use Drupal\config_provider\Plugin\ConfigCollector;
use Drupal\config_snapshot\ConfigSnapshotStorageTrait;
use Drupal\config_sync\ConfigSyncExtensionsTrait;
use Drupal\config_sync\ConfigSyncSnapshotterInterface;

/**
 * Class for invoking configuration providers.
 */
class SyncConfigCollector extends ConfigCollector implements SyncConfigCollectorInterface {

  use ConfigSnapshotStorageTrait;
  use ConfigSyncExtensionsTrait;

  /**
   * {@inheritdoc}
   */
  public function addConfigForSnapshotting(array $extensions = []) {
    // Start with an empty storage.
    $this->providerStorage->deleteAll();
    foreach ($this->providerStorage->getAllCollectionNames() as $collection) {
      $provider_collection = $this->providerStorage->createCollection($collection);
      $provider_collection->deleteAll();
    }

    /** @var \Drupal\config_provider\Plugin\ConfigProviderInterface[] $providers */
    $providers = $this->getConfigProviders();

    foreach ($providers as $provider) {
      if (!$provider instanceof SyncConfigProviderInterface) {
        $provider->addInstallableConfig($extensions);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function alterConfigSnapshots(array $extensions = []) {
    /** @var \Drupal\config_provider\Plugin\ConfigProviderInterface[] $providers */
    $providers = $this->getConfigProviders();

    // Iterate through all extensions.
    $extension_names = $this->getSyncExtensions();

    foreach ($extension_names as $type => $names) {
      foreach ($names as $name) {
        $snapshot_storage = $this->getConfigSnapshotStorage(ConfigSyncSnapshotterInterface::CONFIG_SNAPSHOT_SET, $type, $name);

        // Pass the storage to each provider.
        foreach ($providers as $provider) {
          if ($provider instanceof SyncConfigProviderInterface) {
            $provider->alterConfigSnapshot($snapshot_storage, $extensions);
          }
        }
      }
    }
  }

}
