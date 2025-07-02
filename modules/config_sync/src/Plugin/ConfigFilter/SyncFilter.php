<?php

namespace Drupal\config_sync\Plugin\ConfigFilter;

use Drupal\config_filter\Plugin\ConfigFilterBase;
use Drupal\config_merge\ConfigMerger;
use Drupal\config_merge\Event\ConfigMergeEvent;
use Drupal\config_merge\Event\ConfigMergeEvents;
use Drupal\config_normalizer\Config\NormalizedReadOnlyStorage;
use Drupal\config_normalizer\Config\NormalizedReadOnlyStorageInterface;
use Drupal\config_normalizer\Plugin\ConfigNormalizerManager;
use Drupal\config_snapshot\ConfigSnapshotStorage;
use Drupal\config_sync\ConfigSyncListerInterface;
use Drupal\config_sync\ConfigSyncSnapshotterInterface;
use Drupal\config_sync\Plugin\SyncConfigCollectorInterface;
use Drupal\Core\Config\ConfigManagerInterface;
use Drupal\Core\Config\MemoryStorage;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Extension\Extension;
use Drupal\Core\Extension\ExtensionPathResolver;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\State\StateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Provides a sync filter that brings in updates from installed extensions.
 *
 * @ConfigFilter(
 *   id = "config_sync",
 *   label = "Config Sync",
 *   storages = {"config_distro.storage.distro"},
 *   weight = 10,
 *   deriver = "\Drupal\config_sync\Plugin\ConfigFilter\SyncFilterDeriver"
 * )
 */
class SyncFilter extends ConfigFilterBase implements ContainerFactoryPluginInterface {

  /**
   * The sync source configuration storage.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $syncSourceStorage;

  /**
   * Constructs a new SyncFilter.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param array $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Config\StorageInterface $sync_source_storage
   *   The sync source configuration storage.
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition, StorageInterface $sync_source_storage) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->syncSourceStorage = $sync_source_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $sync_source_storage = self::initSyncSourceStorage(
      $configuration,
      $container->getParameter('app.root'),
      $container->get('config_sync.collector'),
      $container->get('config_sync.lister'),
      $container->get('plugin.manager.config_normalizer'),
      $container->get('config_provider.storage'),
      $container->get('config.storage'),
      $container->get('config.manager'),
      $container->get('state'),
      $container->get('event_dispatcher'),
      $container->get('extension.path.resolver')
    );
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $sync_source_storage
    );
  }

  /**
   * Initializes the sync source storage.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $root
   *   The app root.
   * @param \Drupal\config_sync\Plugin\SyncConfigCollectorInterface $config_collector
   *   The config collector.
   * @param \Drupal\config_sync\ConfigSyncListerInterface $config_sync_lister
   *   The config sync lister.
   * @param \Drupal\config_normalizer\Plugin\ConfigNormalizerManager $normalizer_manager
   *   The normalizer plugin manager.
   * @param \Drupal\Core\Config\StorageInterface $provider_storage
   *   The configuration provider storage.
   * @param \Drupal\Core\Config\StorageInterface $active_storage
   *   The active configuration storage.
   * @param \Drupal\Core\Config\ConfigManagerInterface $config_manager
   *   The configuration manager.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state.
   * @param \Symfony\Contracts\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   * @param \Drupal\Core\Extension\ExtensionPathResolver $extension_path_resolver
   *   The extension path resolver.
   *
   * @return \Drupal\Core\Config\StorageInterface
   *   The initialized sync source storage
   *
   * @throws \Exception
   */
  protected static function initSyncSourceStorage(array $configuration, $root, SyncConfigCollectorInterface $config_collector, ConfigSyncListerInterface $config_sync_lister, ConfigNormalizerManager $normalizer_manager, StorageInterface $provider_storage, StorageInterface $active_storage, ConfigManagerInterface $config_manager, StateInterface $state, EventDispatcherInterface $event_dispatcher, ExtensionPathResolver $extension_path_resolver) {
    $sync_source_storage = new MemoryStorage();

    $update_mode = $state->get('config_sync.update_mode', ConfigSyncListerInterface::DEFAULT_UPDATE_MODE);

    // For a full reset, write all provided configuration to the sync source
    // storage.
    if ($update_mode === ConfigSyncListerInterface::UPDATE_MODE_FULL_RESET) {
      // Create a storage with configuration installable from this extension.
      $pathname = $extension_path_resolver->getPathName($configuration['extension_type'], $configuration['extension_name']);
      $extension = new Extension($root, $configuration['extension_type'], $pathname);
      $extensions = [
        $configuration['extension_name'] => $extension,
      ];
      $config_collector->addInstallableConfig($extensions);

      $config_manager->createSnapshot($provider_storage, $sync_source_storage);
    }
    // For remaining update modes, compare the currently provided configuration
    // to snapshots and act only where there are changes.
    else {
      // Can't use Drupal\config_snapshot\ConfigSnapshotStorageTrait because
      // we're in a static method.
      $service_id = "config_snapshot.{ConfigSyncSnapshotterInterface::CONFIG_SNAPSHOT_SET}.{$configuration['extension_type']}.{$configuration['extension_name']}";
      if (\Drupal::getContainer() && \Drupal::hasService($service_id)) {
        $snapshot_storage = \Drupal::service($service_id);
      }
      else {
        $snapshot_storage = new ConfigSnapshotStorage(ConfigSyncSnapshotterInterface::CONFIG_SNAPSHOT_SET, $configuration['extension_type'], $configuration['extension_name']);
      }

      $changelists = $config_sync_lister->getExtensionChangelist($configuration['extension_type'], $configuration['extension_name']);

      foreach ($changelists as $collection => $changelist) {
        // Ensure storages are using the specified collection.
        foreach (['snapshot', 'provider', 'active', 'sync_source'] as $storage_prefix) {
          if ($collection !== ${$storage_prefix . '_storage'}->getCollectionName()) {
            ${$storage_prefix . '_storage'} = ${$storage_prefix . '_storage'}->createCollection($collection);
          }
        }
        // Process changes.
        if (!empty($changelist['create'])) {
          // To create, we simply save the new item to the merge storage.
          foreach (array_keys($changelist['create']) as $item_name) {
            $sync_source_storage->write($item_name, $provider_storage->read($item_name));
          }
        }
        // Process update changes.
        if (!empty($changelist['update'])) {
          $config_merger = new ConfigMerger();

          foreach (array_keys($changelist['update']) as $item_name) {
            $current = $provider_storage->read($item_name);
            switch ($update_mode) {
              // Merge the value into that of the active storage.
              case ConfigSyncListerInterface::UPDATE_MODE_MERGE:
                $previous = $snapshot_storage->read($item_name);
                $active = $active_storage->read($item_name);
                $updated = $config_merger->mergeConfigItemStates($previous, $current, $active);
                $logs = $config_merger->getLogs();
                $event = new ConfigMergeEvent($item_name, $logs, $configuration['extension_type'], $configuration['extension_name']);
                $event_dispatcher->dispatch($event, ConfigMergeEvents::POST_MERGE);
                break;

              // Reset to the currently provided value.
              case ConfigSyncListerInterface::UPDATE_MODE_PARTIAL_RESET:
                $updated = $current;
                break;

              default:
                throw new \Exception('Invalid state value for config_sync.update_mode.');
            }

            $sync_source_storage->write($item_name, $updated);
          }
        }
      }
    }

    // Normalize the sync source storage to get changes matching those used
    // in comparison.
    $context = [
      'normalization_mode' => NormalizedReadOnlyStorageInterface::NORMALIZATION_MODE_PROVIDE,
      'reference_storage_service' => $active_storage,
    ];

    $normalized_storage = new NormalizedReadOnlyStorage($sync_source_storage, $normalizer_manager, $context);

    return $normalized_storage;
  }

  /**
   * Reads from the sync source configuration.
   *
   * @param string $name
   *   The name of the configuration to read.
   * @param mixed $data
   *   The data to be filtered.
   *
   * @return mixed
   *   The data filtered or read from the sync source storage.
   */
  protected function syncSourceStorageRead($name, $data) {
    if ($sync = $this->syncSourceStorage->read($name)) {
      return $sync;
    }

    return $data;
  }

  /**
   * Reads multiple from the sync source storage.
   *
   * @param array $names
   *   The names of the configuration to read.
   * @param array $data
   *   The data to filter.
   *
   * @return array
   *   The new data.
   */
  protected function syncSourceStorageReadMultiple(array $names, array $data) {
    $filtered_data = [];

    foreach ($names as $name) {
      $filtered_data[$name] = $this->syncSourceStorageRead($name, $data[$name] ?? []);
    }

    return $filtered_data;
  }

  /**
   * {@inheritdoc}
   */
  public function filterRead($name, $data) {
    return $this->syncSourceStorageRead($name, $data);
  }

  /**
   * {@inheritdoc}
   */
  public function filterExists($name, $exists) {
    return $exists || $this->syncSourceStorage->exists($name);
  }

  /**
   * {@inheritdoc}
   */
  public function filterReadMultiple(array $names, array $data) {
    $sync_data = $this->syncSourceStorageReadMultiple($names, $data);

    // Return the data with merged in sync source data.
    return array_merge($data, $sync_data);
  }

  /**
   * {@inheritdoc}
   */
  public function filterListAll($prefix, array $data) {
    $sync_names = $this->syncSourceStorage->listAll($prefix);

    return array_unique(array_merge($data, $sync_names));
  }

  /**
   * {@inheritdoc}
   */
  public function filterCreateCollection($collection) {
    return new static($this->configuration, $this->pluginId, $this->pluginDefinition, $this->syncSourceStorage->createCollection($collection));
  }

  /**
   * {@inheritdoc}
   */
  public function filterGetAllCollectionNames(array $collections) {
    return array_merge($collections, $this->syncSourceStorage->getAllCollectionNames());
  }

}
