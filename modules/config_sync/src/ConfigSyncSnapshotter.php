<?php

namespace Drupal\config_sync;

use Drupal\config_snapshot\ConfigSnapshotStorageTrait;
use Drupal\config_sync\Plugin\SyncConfigCollectorInterface;
use Drupal\Core\Config\ConfigManagerInterface;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Extension\Extension;
use Drupal\Core\Extension\ExtensionPathResolver;
use Drupal\Core\Extension\ModuleExtensionList;

/**
 * ConfigSyncSnapshotter.
 *
 * Provides helper functions for taking snapshots of extension-provided
 * configuration.
 */
class ConfigSyncSnapshotter implements ConfigSyncSnapshotterInterface {

  use ConfigSnapshotStorageTrait;
  use ConfigSyncActiveStoragesTrait;
  use ConfigSyncExtensionsTrait;

  /**
   * The app root for the current operation.
   *
   * @var string
   */
  protected $root;

  /**
   * The configuration collector.
   *
   * @var \Drupal\config_sync\Plugin\SyncConfigCollectorInterface
   */
  protected $configCollector;

  /**
   * The provider configuration storage.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $providerStorage;

  /**
   * The configuration manager.
   *
   * @var \Drupal\Core\Config\ConfigManagerInterface
   */
  protected $configManager;

  /**
   * The configuration synchronizer lister.
   *
   * @var \Drupal\config_sync\configSyncListerInterface
   */
  protected $configSyncLister;

  /**
   * The module extension list.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected $moduleExtensionList;

  /**
   * The extension path resolver.
   *
   * @var \Drupal\Core\Extension\ExtensionPathResolver
   */
  protected $extensionPathResolver;

  /**
   * Constructs a ConfigSyncSnapshotter object.
   *
   * @param string $root
   *   The app root.
   * @param \Drupal\config_provider\Plugin\SyncConfigCollectorInterface $config_collector
   *   The config collector.
   * @param \Drupal\Core\Config\StorageInterface $provider_storage
   *   The provider configuration storage.
   * @param \Drupal\Core\Config\ConfigManagerInterface $config_manager
   *   The configuration manager.
   * @param \Drupal\Core\Config\StorageInterface $active_storage
   *   The active configuration store where the list of enabled modules and
   *   themes is stored.
   * @param \Drupal\config_sync\ConfigSyncListerInterface $config_sync_lister
   *   The configuration synchronizer lister.
   * @param \Drupal\Core\Extension\ModuleExtensionList $module_extension_list
   *   The module extension list.
   * @param \Drupal\Core\Extension\ExtensionPathResolver $extension_path_resolver
   *   The extension path resolver.
   */
  public function __construct($root, SyncConfigCollectorInterface $config_collector, StorageInterface $provider_storage, ConfigManagerInterface $config_manager, StorageInterface $active_storage, ConfigSyncListerInterface $config_sync_lister, ModuleExtensionList $module_extension_list, ExtensionPathResolver $extension_path_resolver) {
    $this->root = $root;
    $this->configCollector = $config_collector;
    $this->providerStorage = $provider_storage;
    $this->configManager = $config_manager;
    $this->activeStorages[$active_storage
      ->getCollectionName()] = $active_storage;
    $this->configSyncLister = $config_sync_lister;
    $this->moduleExtensionList = $module_extension_list;
    $this->extensionPathResolver = $extension_path_resolver;
  }

  /**
   * {@inheritdoc}
   */
  public function refreshExtensionSnapshot($type, array $names, $mode) {
    foreach ($names as $name) {
      $extensions = [];
      $pathname = $this->extensionPathResolver->getPathname($type, $name);
      $extensions[$name] = new Extension($this->root, $type, $pathname);
      $snapshot_storage = $this->getConfigSnapshotStorage(ConfigSyncSnapshotterInterface::CONFIG_SNAPSHOT_SET, $type, $name);

      switch ($mode) {
        // On install, snapshot configuration in two stages. First, snapshot
        // unaltered configuration. Then, below, apply alters.
        case ConfigSyncSnapshotterInterface::SNAPSHOT_MODE_INSTALL:
          $this->configCollector->addConfigForSnapshotting($extensions);
          break;

        // On import, snapshot fully altered configuration.
        case ConfigSyncSnapshotterInterface::SNAPSHOT_MODE_IMPORT:
          $this->configCollector->addInstallableConfig($extensions);
          break;
      }

      // Create the snapshot.
      $this->configManager->createSnapshot($this->providerStorage, $snapshot_storage);

      // Conditionally alter the previously added configuration.
      if ($mode === ConfigSyncSnapshotterInterface::SNAPSHOT_MODE_INSTALL) {
        $this->configCollector->alterConfigSnapshots($extensions);
      }
    }

    $this->snapshotNewItems();
  }

  /**
   * Snapshot items that are installed but haven't yet been snapshotted.
   *
   * Certain configuration items are installed subsequent to the installation
   * of the modules that provide them. This is true of optional configuration
   * when the conditions for installation are met subsequent to the initial
   * installation of the providing module.
   *
   * To cover these cases, we detect and snapshot such items.
   */
  protected function snapshotNewItems() {
    $extension_changelists = $this->configSyncLister->getExtensionChangelists();
    // Populate the provider storage with all available configuration.
    $this->configCollector->addInstallableConfig();
    foreach ($extension_changelists as $type => $extensions) {
      foreach ($extensions as $name => $collection_changelists) {
        $snapshot_storage = $this->getConfigSnapshotStorage(ConfigSyncSnapshotterInterface::CONFIG_SNAPSHOT_SET, $type, $name);
        foreach ($collection_changelists as $collection => $operation_types) {
          // Switch collection storages if necessary.
          if ($collection !== $snapshot_storage->getCollectionName()) {
            $snapshot_storage = $snapshot_storage->createCollection($collection);
          }

          // Create operations indicate the configuration is provided but
          // hasn't been snapshotted.
          if (isset($operation_types['create'])) {
            foreach (array_keys($operation_types['create']) as $config_id) {
              // If the item exists but there's no snapshot, create one.
              if ($this->getActiveStorages($collection)->exists($config_id)) {
                $snapshot_storage->write($config_id, $this->providerStorage->read($config_id));
              }
            }
          }
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function createSnapshot() {
    $extension_names = $this->getSyncExtensions();

    foreach ($extension_names as $type => $names) {
      if ($type === 'module') {
        $names = $this->listModulesInDependencyOrder($names);
      }
      $this->refreshExtensionSnapshot($type, $names, ConfigSyncSnapshotterInterface::SNAPSHOT_MODE_INSTALL);
    }
  }

  /**
   * Returns a list of specified modules sorted in order of dependency.
   *
   * @param string[] $module_list
   *   An array of module names.
   *
   * @return string[]
   *   An array of module names.
   */
  protected function listModulesInDependencyOrder(array $module_list) {
    $module_list = array_combine($module_list, $module_list);

    // Get a list of modules with dependency weights as values.
    $module_data = $this->moduleExtensionList->getList();
    // Set the actual module weights.
    $module_list = array_map(function ($module) use ($module_data) {
      return $module_data[$module]->sort;
    }, $module_list);

    // Sort the module list by their weights (reverse).
    arsort($module_list);
    return array_keys($module_list);
  }

}
