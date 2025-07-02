<?php

namespace Drupal\config_sync;

use Drupal\config_normalizer\Config\NormalizedReadOnlyStorage;
use Drupal\config_normalizer\Config\NormalizedReadOnlyStorageInterface;
use Drupal\config_normalizer\Config\NormalizedStorageComparerTrait;
use Drupal\config_normalizer\Plugin\ConfigNormalizerManager;
use Drupal\config_snapshot\ConfigSnapshotStorageTrait;
use Drupal\config_sync\Config\SettableStorageComparer;
use Drupal\config_sync\Plugin\SyncConfigCollectorInterface;
use Drupal\config_update\ConfigListInterface as ConfigUpdateListerInterface;
use Drupal\Core\Config\ConfigManagerInterface;
use Drupal\Core\Config\NullStorage;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Extension\Extension;
use Drupal\Core\Extension\ExtensionPathResolver;
use Drupal\Core\State\StateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Provides methods related to listing configuration changes.
 */
class ConfigSyncLister implements ConfigSyncListerInterface {

  use ConfigSnapshotStorageTrait;
  use ConfigSyncActiveStoragesTrait;
  use ConfigSyncExtensionsTrait;
  use NormalizedStorageComparerTrait;
  use StringTranslationTrait;

  /**
   * The configuration collector.
   *
   * @var \Drupal\config_sync\Plugin\SyncConfigCollectorInterface
   */
  protected $configCollector;

  /**
   * The configuration update lister.
   *
   * @var \Drupal\config_update\ConfigListInterface
   */
  protected $configUpdateLister;

  /**
   * The provider configuration storage.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $providerStorage;

  /**
   * The normalized active storage.
   *
   * @var \Drupal\config_normalizer\Config\NormalizedReadOnlyStorageInterface
   */
  protected $normalizedActiveStorage;

  /**
   * The state storage object.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * List of current config entity type labels, keyed by entity type.
   *
   * This is not set up until ::listConfigTypes() has been called.
   *
   * @var array
   */
  protected $configTypes = [];

  /**
   * A storage comparer with the active storage as target.
   *
   * @var \Drupal\Core\Config\StorageComparerInterface
   */
  protected $activeStorageComparer;

  /**
   * The extension path resolver.
   *
   * @var \Drupal\Core\Extension\ExtensionPathResolver
   */
  protected $extensionPathResolver;

  /**
   * The app root for the current operation.
   *
   * @var string
   */
  protected $root;

  /**
   * Constructs a ConfigSyncLister object.
   *
   * @param \Drupal\config_sync\Plugin\SyncConfigCollectorInterface $config_collector
   *   The config collector.
   * @param \Drupal\config_update\ConfigListInterface $config_update_lister
   *   The configuration update lister.
   * @param \Drupal\config_normalizer\Plugin\ConfigNormalizerManager $normalizer_manager
   *   The normalizer plugin manager.
   * @param \Drupal\Core\Config\StorageInterface $active_storage
   *   The active storage.
   * @param \Drupal\Core\Config\StorageInterface $provider_storage
   *   The provider configuration storage.
   * @param \Drupal\Core\Config\ConfigManagerInterface $config_manager
   *   The configuration manager.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state storage object.
   * @param \Drupal\Core\Extension\ExtensionPathResolver $extension_path_resolver
   *   The extension path resolver.
   * @param string $root
   *   The app root.
   */
  public function __construct(SyncConfigCollectorInterface $config_collector, ConfigUpdateListerInterface $config_update_lister, ConfigNormalizerManager $normalizer_manager, StorageInterface $active_storage, StorageInterface $provider_storage, ConfigManagerInterface $config_manager, StateInterface $state, ExtensionPathResolver $extension_path_resolver, $root) {
    $this->configCollector = $config_collector;
    $this->configUpdateLister = $config_update_lister;
    $this->setNormalizerManager($normalizer_manager);
    $this->activeStorages[$active_storage
      ->getCollectionName()] = $active_storage;
    $this->providerStorage = $provider_storage;
    $this->setConfigManager($config_manager);
    $this->state = $state;
    $this->normalizedActiveStorage = new NormalizedReadOnlyStorage($active_storage, $normalizer_manager);
    // Set up a storage comparer to be used by each extension. Use a null
    // storage as a placeholder that we'll reset. Using a single storage
    // comparer rather than one per extension provides important optimization
    // since each storage comparer will load all records into a memory cache
    // and by setting a single source we can limit this to a single read.
    $this->activeStorageComparer = new SettableStorageComparer(
      new NormalizedReadOnlyStorage(new NullStorage(), $normalizer_manager),
      $this->normalizedActiveStorage
    );
    $this->extensionPathResolver = $extension_path_resolver;
    $this->root = $root;
  }

  /**
   * {@inheritdoc}
   */
  public function getExtensionChangelists(array $extension_names = []) {
    $changelist = [];
    // If no extensions were specified, use all installed extensions.
    if (empty($extension_names)) {
      $extension_names = $this->getSyncExtensions();
    }
    foreach ($extension_names as $type => $names) {
      foreach ($names as $name) {
        if ($extension_changelist = $this->getExtensionChangelist($type, $name)) {
          $changelist[$type][$name] = $extension_changelist;
        }
      }
    }

    return $changelist;
  }

  /**
   * {@inheritdoc}
   */
  public function getExtensionChangelist($type, $name) {
    $update_mode = $this->state->get('config_sync.update_mode', ConfigSyncListerInterface::DEFAULT_UPDATE_MODE);

    // Create a storage with configuration installable from this extension.
    $pathname = $this->extensionPathResolver->getPathname($type, $name);
    $extension = new Extension($this->root, $type, $pathname);
    $extensions = [
      $name => $extension,
    ];
    $this->configCollector->addInstallableConfig($extensions);

    $return = [];

    // Return early if the extension has no installable configuration.
    // @todo remove this early return if we introduce support for deletions.
    // @see https://www.drupal.org/project/config_sync/issues/2914536
    if (empty($this->providerStorage->listAll())) {
      return $return;
    }

    // For a full reset, compare against the active storage.
    if ($update_mode === ConfigSyncListerInterface::UPDATE_MODE_FULL_RESET) {
      // Wrap the provider storage.
      $normalized_provider_storage = new NormalizedReadOnlyStorage(
        $this->providerStorage,
        $this->normalizerManager,
        [
          'normalization_mode' => NormalizedReadOnlyStorageInterface::DEFAULT_NORMALIZATION_MODE,
          'reference_storage_service' => $this->getActiveStorages(),
        ]
      );

      // Set the provider storage as the comparer's source.
      $this->activeStorageComparer->setSourceStorage($normalized_provider_storage);

      // Set the context for the active storage.
      $this->normalizedActiveStorage->setContext([
        'normalization_mode' => NormalizedReadOnlyStorageInterface::DEFAULT_NORMALIZATION_MODE,
        'reference_storage_service' => $this->providerStorage,
      ]);

      $storage_comparer = $this->activeStorageComparer;
    }
    // Otherwise, compare against a snapshot.
    else {
      $snapshot_storage = $this->getConfigSnapshotStorage(ConfigSyncSnapshotterInterface::CONFIG_SNAPSHOT_SET, $type, $name);
      $storage_comparer = $this->createStorageComparer($this->providerStorage, $snapshot_storage);
    }

    if ($storage_comparer->createChangelist()->hasChanges()) {
      foreach ($storage_comparer->getAllCollectionNames() as $collection) {
        $changelist = $storage_comparer->getChangelist(NULL, $collection);
        // We're only concerned with create and update lists.
        unset($changelist['delete']);
        unset($changelist['rename']);
        $changelist = array_filter($changelist);

        // Convert the changelist into a format that includes the item label.
        foreach ($changelist as $change_type => $item_names) {
          foreach ($item_names as $item_name) {
            $adjusted_change_type = $change_type;
            // Detect cases where we're updating but the item doesn't exist.
            // This indicates an item that was installed but later deleted.
            $target_exists = $this->getActiveStorages($collection)->exists($item_name);
            if ($change_type === 'update' && !$target_exists) {
              switch ($update_mode) {
                // When merging, don't restore an item that was deleted from
                // the active storage.
                case ConfigSyncListerInterface::UPDATE_MODE_MERGE:
                  continue 2;

                // When resetting, restore a deleted item.
                case ConfigSyncListerInterface::UPDATE_MODE_PARTIAL_RESET:
                  $adjusted_change_type = 'create';
                  break;
              }
            }

            // Figure out what type of config it is, and get the ID.
            $config_type = $this->configUpdateLister->getTypeNameByConfigName($item_name);

            if (!$config_type) {
              // This is simple config.
              $label = $item_name;
            }
            else {
              $config = $this->providerStorage->read($item_name);
              $definition = $this->configUpdateLister->getType($config_type);
              $key = $definition->getKey('label') ?: $definition->getKey('id');
              $label = $config[$key];
            }
            $return[$collection][$adjusted_change_type][$item_name] = $label;
          }
        }
      }
    }

    return $return;
  }

  /**
   * {@inheritdoc}
   */
  public function listConfigTypes() {
    if (empty($this->configTypes)) {
      $definitions = $this->configUpdateLister->listTypes();
      $config_types = array_map(function (EntityTypeInterface $definition) {
        return $definition->getLabel();
      }, $definitions);
      $config_types['system_simple'] = $this->t('Simple configuration');
      // Sort the entity types by label.
      uasort($config_types, 'strnatcasecmp');
      $this->configTypes = $config_types;
    }
    return $this->configTypes;
  }

}
