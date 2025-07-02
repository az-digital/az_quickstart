<?php

namespace Drupal\config_split;

use Drupal\Component\FileSecurity\FileSecurity;
use Drupal\config_split\Config\ConfigPatch;
use Drupal\config_split\Config\ConfigPatchMerge;
use Drupal\config_split\Config\EphemeralConfigFactory;
use Drupal\config_split\Config\SplitCollectionStorage;
use Drupal\config_split\Entity\ConfigSplitEntity;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ConfigManagerInterface;
use Drupal\Core\Config\DatabaseStorage;
use Drupal\Core\Config\Entity\ConfigDependencyManager;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Config\FileStorage;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Config\MemoryStorage;
use Drupal\Core\Config\StorageCopyTrait;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Config\StorageTransformEvent;
use Drupal\Core\Database\Connection;

/**
 * The manager to split and merge.
 *
 * @internal This is not an API, it is code for config splits internal code, it
 *   may change without notice. You have been warned!
 */
final class ConfigSplitManager {

  use StorageCopyTrait;

  const SPLIT_PARTIAL_PREFIX = 'config_split.patch.';

  /**
   * The config factory to load config.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  private $factory;

  /**
   * The database connection to set up database storages.
   *
   * @var \Drupal\Core\Database\Connection
   */
  private $connection;

  /**
   * The active config store to do single import.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  private $active;

  /**
   * The sync storage for checking conditional split.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  private $sync;

  /**
   * The export storage to do single export.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  private $export;

  /**
   * The config manager to calculate dependencies.
   *
   * @var \Drupal\Core\Config\ConfigManagerInterface
   */
  private $manager;

  /**
   * The config array sorter.
   *
   * @var \Drupal\config_split\Config\ConfigPatchMerge
   */
  private $patchMerge;

  /**
   * ConfigSplitManager constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $factory
   *   The config factory.
   * @param \Drupal\Core\Config\ConfigManagerInterface $manager
   *   The config manager.
   * @param \Drupal\Core\Config\StorageInterface $active
   *   The active config store.
   * @param \Drupal\Core\Config\StorageInterface $sync
   *   The sync config store.
   * @param \Drupal\Core\Config\StorageInterface $export
   *   The export config store.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param \Drupal\config_split\Config\ConfigPatchMerge $patchMerge
   *   The patch-merge service.
   */
  public function __construct(
    ConfigFactoryInterface $factory,
    ConfigManagerInterface $manager,
    StorageInterface $active,
    StorageInterface $sync,
    StorageInterface $export,
    Connection $connection,
    ConfigPatchMerge $patchMerge,
  ) {
    $this->factory = $factory;
    $this->sync = $sync;
    $this->active = $active;
    $this->export = $export;
    $this->connection = $connection;
    $this->manager = $manager;
    $this->patchMerge = $patchMerge;
  }

  /**
   * Get a split from a name.
   *
   * @param string $name
   *   The name of the split.
   * @param \Drupal\Core\Config\StorageInterface $storage
   *   The storage to get a split from if not the active one.
   *
   * @return \Drupal\Core\Config\ImmutableConfig|null
   *   The split config.
   */
  public function getSplitConfig(string $name, ?StorageInterface $storage = NULL): ?ImmutableConfig {
    if (strpos($name, 'config_split.config_split.') !== 0) {
      $name = 'config_split.config_split.' . $name;
    }
    // Get the split from the storage passed as an argument.
    if ($storage instanceof StorageInterface && $this->factory instanceof ConfigFactory) {
      $factory = EphemeralConfigFactory::fromService($this->factory, $storage);
      if (in_array($name, $factory->listAll('config_split.config_split.'), TRUE)) {
        return $factory->get($name);
      }
    }
    // Use the config factory service as a fallback.
    if (in_array($name, $this->factory->listAll('config_split.config_split.'), TRUE)) {
      return $this->factory->get($name);
    }

    return NULL;
  }

  /**
   * Get a split entity.
   *
   * @param string $name
   *   The split name.
   *
   * @return \Drupal\config_split\Entity\ConfigSplitEntity|null
   *   The config entity.
   */
  public function getSplitEntity(string $name): ?ConfigSplitEntity {
    $config = $this->getSplitConfig($name);
    if ($config === NULL) {
      return NULL;
    }
    $entity = $this->manager->loadConfigEntityByName($config->getName());
    if ($entity instanceof ConfigSplitEntity) {
      return $entity;
    }
    // Do we throw an exception? Do we return null?
    // @todo find out in what legitimate case this could possibly happen.
    throw new \RuntimeException('A split config does not load a split entity? something is very wrong.');
  }

  /**
   * Get all splits from the active storage plus the given storage.
   *
   * @param \Drupal\Core\Config\StorageInterface|null $storage
   *   The storage to consider when listing splits.
   *
   * @return string[]
   *   The split names from the active storage and the given storage.
   */
  public function listAll(?StorageInterface $storage = NULL): array {
    $names = [];
    if ($storage instanceof StorageInterface && $this->factory instanceof ConfigFactory) {
      $factory = EphemeralConfigFactory::fromService($this->factory, $storage);
      $names = $factory->listAll('config_split.config_split.');
    }

    return array_unique(array_merge($names, $this->factory->listAll('config_split.config_split.')));
  }

  /**
   * Load multiple splits and prefer loading it from the given storage.
   *
   * @param array $names
   *   The names to load.
   * @param \Drupal\Core\Config\StorageInterface|null $storage
   *   The storage to check.
   *
   * @return \Drupal\Core\Config\ImmutableConfig[]
   *   Loaded splits (with config overrides).
   */
  public function loadMultiple(array $names, ?StorageInterface $storage = NULL): array {
    $configs = [];
    if ($storage instanceof StorageInterface && $this->factory instanceof ConfigFactory) {
      $factory = EphemeralConfigFactory::fromService($this->factory, $storage);
      $configs = $factory->loadMultiple($names);
    }

    return $configs + $this->factory->loadMultiple($names);
  }

  /**
   * Process the export of a split.
   *
   * @param string $name
   *   The name of the split.
   * @param \Drupal\Core\Config\StorageTransformEvent $event
   *   The transformation event.
   */
  public function exportTransform(string $name, StorageTransformEvent $event): void {
    $split = $this->getSplitConfig($name);
    if ($split === NULL) {
      return;
    }
    if (!$split->get('status')) {
      return;
    }
    $storage = $event->getStorage();
    $preview = $this->getPreviewStorage($split, $storage);
    if ($preview !== NULL) {
      // Without a storage there is no splitting.
      $this->splitPreview($split, $storage, $preview);
    }
  }

  /**
   * Process the import of a split.
   *
   * @param string $name
   *   The name of the split.
   * @param \Drupal\Core\Config\StorageTransformEvent $event
   *   The transformation event.
   */
  public function importTransform(string $name, StorageTransformEvent $event): void {
    $split = $this->getSplitConfig($name, $event->getStorage());
    if ($split === NULL) {
      return;
    }
    if (!$split->get('status')) {
      return;
    }
    $storage = $event->getStorage();
    $secondary = $this->getSplitStorage($split, $storage);
    if ($secondary !== NULL) {
      $this->mergeSplit($split, $storage, $secondary);
    }
  }

  /**
   * Make the split permanent by copying the preview to the split storage.
   */
  public function commitAll(): void {
    $splits = $this->factory->loadMultiple($this->factory->listAll('config_split'));

    $splits = array_filter($splits, function (ImmutableConfig $config) {
      return $config->get('status');
    });

    // Copy the preview to the permanent place.
    foreach ($splits as $split) {
      $preview = $this->getPreviewStorage($split);
      $permanent = $this->getSplitStorage($split);
      if ($preview !== NULL && $permanent !== NULL) {
        self::replaceStorageContents($preview, $permanent);
      }
    }
  }

  /**
   * Split the config of a split to the preview storage.
   *
   * @param \Drupal\Core\Config\ImmutableConfig $config
   *   The split config.
   * @param \Drupal\Core\Config\StorageInterface $transforming
   *   The transforming storage.
   * @param \Drupal\Core\Config\StorageInterface $splitStorage
   *   The splits preview storage.
   */
  public function splitPreview(ImmutableConfig $config, StorageInterface $transforming, StorageInterface $splitStorage): void {

    // Opt to use V1 no patching.
    if ($config->get('no_patching')) {
      $this->splitPreviewNoPatching($config, $transforming, $splitStorage);
      return;
    }

    // Empty the split storage.
    foreach (array_merge([StorageInterface::DEFAULT_COLLECTION], $splitStorage->getAllCollectionNames()) as $collection) {
      $splitStorage->createCollection($collection)->deleteAll();
    }
    $transforming = $transforming->createCollection(StorageInterface::DEFAULT_COLLECTION);
    $splitStorage = $splitStorage->createCollection(StorageInterface::DEFAULT_COLLECTION);
    $source = $this->active->createCollection(StorageInterface::DEFAULT_COLLECTION);
    if ($config->get('stackable')) {
      // We need to copy the transforming storage so that we don't change what
      // we later compare with.
      $source = new MemoryStorage();
      self::replaceStorageContents($transforming, $source);
    }

    $modules = array_keys($config->get('module'));
    $changes = $this->manager->getConfigEntitiesToChangeOnDependencyRemoval('module', $modules, TRUE);

    $this->processEntitiesToChangeOnDependencyRemoval($changes, $source, $transforming, $splitStorage);

    $completelySplit = array_map(function (ConfigEntityInterface $entity) {
      return $entity->getConfigDependencyName();
    }, $changes['delete']);

    // Process all simple config objects which implicitly depend on modules.
    foreach ($modules as $module) {
      $keys = $source->listAll($module . '.');
      $keys = array_diff($keys, $completelySplit);
      foreach ($keys as $name) {
        self::moveConfigToSplit($name, $source, $splitStorage, $transforming);
        $completelySplit[] = $name;
      }
    }

    // Get explicitly split config.
    $completeSplitList = $config->get('complete_list');
    if (!empty($completeSplitList)) {
      // For the complete split we use the active storage config. This way two
      // splits can split the same config and both will have them. But also
      // because we use the config manager service to get entities to change
      // based on the modules which are configured to be split.
      $completeList = array_filter($source->listAll(), function ($name) use ($completeSplitList) {
        // Check for wildcards.
        return self::inFilterList($name, $completeSplitList);
      });
      // Check what is not processed already.
      $completeList = array_diff($completeList, $completelySplit);

      // Process also the config being removed.
      $changes = $this->manager->getConfigEntitiesToChangeOnDependencyRemoval('config', $completeList, TRUE);
      $this->processEntitiesToChangeOnDependencyRemoval($changes, $source, $transforming, $splitStorage);

      // Split all the config which was specified but not processed yet.
      $processed = array_map(function (ConfigEntityInterface $entity) {
        return $entity->getConfigDependencyName();
      }, $changes['delete']);
      $unprocessed = array_diff($completeList, $processed);
      foreach ($unprocessed as $name) {
        self::moveConfigToSplit($name, $source, $splitStorage, $transforming);
        $completelySplit[] = $name;
      }
    }

    // Split from collections what was split from the default collection.
    if (!empty($completelySplit) || !empty($completeSplitList)) {
      foreach ($source->getAllCollectionNames() as $collection) {
        $storageCollection = $transforming->createCollection($collection);
        $splitCollection = $splitStorage->createCollection($collection);
        $sourceCollection = $source->createCollection($collection);

        $removeList = array_filter($sourceCollection->listAll(), function ($name) use ($completeSplitList, $completelySplit) {
          // Check for wildcards.
          return in_array($name, $completelySplit) || self::inFilterList($name, $completeSplitList);
        });
        foreach ($removeList as $name) {
          // Split collections.
          self::moveConfigToSplit($name, $sourceCollection, $splitCollection, $storageCollection);
        }
      }
    }

    // Process partial config.
    $partialSplitList = $config->get('partial_list');
    if (!empty($partialSplitList)) {
      $preparedSync = $this->prepareSyncForPartialComparison($config);
      foreach (array_merge([StorageInterface::DEFAULT_COLLECTION], $source->getAllCollectionNames()) as $collection) {
        $syncCollection = $preparedSync->createCollection($collection);
        $sourceCollection = $source->createCollection($collection);
        $storageCollection = $transforming->createCollection($collection);
        $splitCollection = $splitStorage->createCollection($collection);

        $partialList = array_filter($sourceCollection->listAll(), function ($name) use ($partialSplitList, $completelySplit) {
          // Check for wildcards. But skip config which is already split.
          return !in_array($name, $completelySplit) && self::inFilterList($name, $partialSplitList);
        });

        foreach ($partialList as $name) {
          if ($syncCollection->exists($name)) {
            $sync = $syncCollection->read($name);
            $active = $sourceCollection->read($name);

            $patch = $this->createPatch($name, $active, $sync, $splitCollection);
            if (!$patch->isEmpty()) {
              // If the diff is empty then sync already contains the data.
              $storageCollection->write($name, $sync);
            }
          }
          else {
            // Split the config completely if it was not in the sync storage.
            self::moveConfigToSplit($name, $sourceCollection, $splitCollection, $storageCollection);
          }
        }
      }
    }

    // Now special case the extensions.
    $extensions = $transforming->read('core.extension');
    if ($extensions === FALSE) {
      return;
    }
    // Split off the extensions.
    $extensions['module'] = array_diff_key($extensions['module'], $config->get('module') ?? []);
    $extensions['theme'] = array_diff_key($extensions['theme'], $config->get('theme') ?? []);

    $transforming->write('core.extension', $extensions);
  }

  /**
   * Merge the config of a split to the transformation storage.
   *
   * @param \Drupal\Core\Config\ImmutableConfig $config
   *   The split config.
   * @param \Drupal\Core\Config\StorageInterface $transforming
   *   The transforming storage.
   * @param \Drupal\Core\Config\StorageInterface $splitStorage
   *   The split storage.
   */
  public function mergeSplit(ImmutableConfig $config, StorageInterface $transforming, StorageInterface $splitStorage): void {
    $transforming = $transforming->createCollection(StorageInterface::DEFAULT_COLLECTION);
    $splitStorage = $splitStorage->createCollection(StorageInterface::DEFAULT_COLLECTION);

    // Merge all the configuration from all collections.
    foreach (array_merge([StorageInterface::DEFAULT_COLLECTION], $splitStorage->getAllCollectionNames()) as $collection) {
      $split = $splitStorage->createCollection($collection);
      $storage = $transforming->createCollection($collection);
      foreach ($split->listAll() as $name) {
        $data = $split->read($name);
        if ($data !== FALSE) {
          if (strpos($name, self::SPLIT_PARTIAL_PREFIX) === 0) {
            $name = substr($name, strlen(self::SPLIT_PARTIAL_PREFIX));
            $diff = ConfigPatch::fromArray($data);
            if ($storage->exists($name)) {
              // Skip patches for config that doesn't exist in the storage.
              $data = $storage->read($name);
              $data = $this->patchMerge->mergePatch($data, $diff->invert(), $name);
              $storage->write($name, $data);
            }
          }
          else {
            $storage->write($name, $data);
          }
        }
      }
    }

    // When merging a split with the collection storage we delete all in it.
    if ($config->get('storage') === 'collection') {
      // We can not assume $splitStorage is grafted onto $transforming.
      $collectionStorage = new SplitCollectionStorage($transforming, $config->get('id'));
      foreach (array_merge([StorageInterface::DEFAULT_COLLECTION], $collectionStorage->getAllCollectionNames()) as $collection) {
        $collectionStorage->createCollection($collection)->deleteAll();
      }
    }

    // Now special case the extensions.
    $extensions = $transforming->read('core.extension');
    if ($extensions === FALSE) {
      return;
    }

    $updated = $transforming->read($config->getName());
    if ($updated === FALSE) {
      return;
    }

    $extensions['theme'] = array_merge($extensions['theme'] ?? [], $updated['theme'] ?? []);
    $sorted = array_merge($extensions['module'] ?? [], $updated['module'] ?? []);
    // Sort the modules.
    uksort($sorted, function ($a, $b) use ($sorted) {
      // Sort by module weight, this assumes the schema of core.extensions.
      if ($sorted[$a] != $sorted[$b]) {
        return $sorted[$a] > $sorted[$b] ? 1 : -1;
      }
      // Or sort by module name.
      return $a > $b ? 1 : -1;
    });

    $extensions['module'] = $sorted;

    $transforming->write('core.extension', $extensions);
  }

  /**
   * Get the split storage.
   *
   * @param \Drupal\Core\Config\ImmutableConfig $config
   *   The split config.
   * @param \Drupal\Core\Config\StorageInterface|null $transforming
   *   The transforming storage.
   *
   * @return \Drupal\Core\Config\StorageInterface|null
   *   The split storage.
   */
  protected function getSplitStorage(ImmutableConfig $config, ?StorageInterface $transforming = NULL): ?StorageInterface {
    $storage = $config->get('storage');
    if ('collection' === $storage) {
      if ($transforming instanceof StorageInterface) {
        return new SplitCollectionStorage($transforming, $config->get('id'));
      }

      return NULL;
    }
    if ('folder' === $storage) {
      // Here we could determine to use relative paths etc.
      $directory = $config->get('folder');
      assert(!empty($directory), sprintf('The %s split has been configured to use folder storage, but the folder path is empty.', $config->get('id')));
      if (!is_dir($directory)) {
        // If the directory doesn't exist, attempt to create it.
        // This might have some negative consequences, but we trust the user to
        // have properly configured their site.
        /* @noinspection MkdirRaceConditionInspection */
        @mkdir($directory, 0777, TRUE);
      }
      // The following is roughly: file_save_htaccess($directory, TRUE, TRUE);
      // But we can't use global drupal functions, and we want to write the
      // .htaccess file to ensure the configuration is protected and the
      // directory not empty.
      if (file_exists($directory) && is_writable($directory)) {
        $htaccess_path = rtrim($directory, '/\\') . '/.htaccess';
        if (!file_exists($htaccess_path)) {
          file_put_contents($htaccess_path, FileSecurity::htaccessLines(TRUE));
          @chmod($htaccess_path, 0444);
        }
      }

      if (file_exists($directory) || strpos($directory, 'vfs://') === 0) {
        // Allow virtual file systems even if file_exists is false.
        return new FileStorage($directory);
      }

      return NULL;
    }

    // When the folder is not set use a database.
    return new DatabaseStorage($this->connection, $this->connection->escapeTable(strtr($config->getName(), ['.' => '_'])));
  }

  /**
   * Get the preview storage.
   *
   * @param \Drupal\Core\Config\ImmutableConfig $config
   *   The split config.
   * @param \Drupal\Core\Config\StorageInterface|null $transforming
   *   The transforming storage.
   *
   * @return \Drupal\Core\Config\StorageInterface|null
   *   The preview storage.
   */
  public function getPreviewStorage(ImmutableConfig $config, ?StorageInterface $transforming = NULL): ?StorageInterface {
    if ('collection' === $config->get('storage')) {
      if ($transforming instanceof StorageInterface) {
        return new SplitCollectionStorage($transforming, $config->get('id'));
      }

      return NULL;
    }

    $name = substr($config->getName(), strlen('config_split.config_split.'));
    $name = 'config_split_preview_' . strtr($name, ['.' => '_']);
    // Use the database for everything.
    return new DatabaseStorage($this->connection, $this->connection->escapeTable($name));
  }

  /**
   * Get the single export preview.
   *
   * @param \Drupal\Core\Config\ImmutableConfig $split
   *   The split config.
   *
   * @return \Drupal\Core\Config\StorageInterface
   *   The single export preview.
   */
  public function singleExportPreview(ImmutableConfig $split): StorageInterface {

    // Force the transformation.
    $this->export->listAll();
    $preview = $this->getPreviewStorage($split, $this->export);

    if (!$split->get('status') && $preview !== NULL) {
      // @todo decide if splitting an inactive split is wise.
      $transforming = new MemoryStorage();
      self::replaceStorageContents($this->export, $transforming);
      $this->splitPreview($split, $transforming, $preview);
    }

    if ($preview === NULL) {
      throw new \RuntimeException();
    }
    return $preview;
  }

  /**
   * Get the single export target.
   *
   * @param \Drupal\Core\Config\ImmutableConfig $split
   *   The split config.
   *
   * @return \Drupal\Core\Config\StorageInterface
   *   The single export target.
   */
  public function singleExportTarget(ImmutableConfig $split): StorageInterface {
    $permanent = $this->getSplitStorage($split, $this->sync);
    if ($permanent === NULL) {
      throw new \RuntimeException();
    }
    return $permanent;
  }

  /**
   * Import the config of a single split.
   *
   * @param \Drupal\Core\Config\ImmutableConfig $split
   *   The split config.
   * @param bool $activate
   *   Whether to activate the split as well.
   *
   * @return \Drupal\Core\Config\StorageInterface
   *   The storage to pass to a ConfigImporter to do the actual importing.
   */
  public function singleImport(ImmutableConfig $split, bool $activate): StorageInterface {
    $storage = $this->getSplitStorage($split, $this->sync);
    return $this->singleImportOrActivate($split, $storage, $activate);
  }

  /**
   * Import the config of a single split.
   *
   * @param \Drupal\Core\Config\ImmutableConfig $split
   *   The split config.
   * @param bool $activate
   *   Whether to activate the split as well.
   *
   * @return \Drupal\Core\Config\StorageInterface
   *   The storage to pass to a ConfigImporter to do the actual importing.
   */
  public function singleActivate(ImmutableConfig $split, bool $activate): StorageInterface {
    $storage = $this->getSplitStorage($split, $this->active);
    return $this->singleImportOrActivate($split, $storage, $activate);
  }

  /**
   * Deactivate a split.
   *
   * @param \Drupal\Core\Config\ImmutableConfig $split
   *   The split config.
   * @param bool $exportSplit
   *   Whether to export the split config first.
   * @param bool $override
   *   Allows the deactivation via override.
   *
   * @return \Drupal\Core\Config\StorageInterface
   *   The storage to pass to a ConfigImporter to do the config changes.
   */
  public function singleDeactivate(ImmutableConfig $split, bool $exportSplit = FALSE, $override = FALSE): StorageInterface {
    if (!$split->get('status') && !$override) {
      throw new \InvalidArgumentException('Split is already not active.');
    }

    $transformation = new MemoryStorage();
    static::replaceStorageContents($this->active, $transformation);

    $preview = $this->getPreviewStorage($split, $transformation);
    if ($preview === NULL) {
      throw new \RuntimeException();
    }
    $this->splitPreview($split, $transformation, $preview);

    if ($exportSplit) {
      $permanent = $this->getSplitStorage($split, $this->sync);
      if ($permanent === NULL) {
        throw new \RuntimeException();
      }
      static::replaceStorageContents($preview, $permanent);
    }

    // Deactivate the split in the transformation so that the importer does it.
    $config = $transformation->read($split->getName());
    if ($config !== FALSE && !$override) {
      $config['status'] = FALSE;
      $transformation->write($split->getName(), $config);
    }

    return $transformation;
  }

  /**
   * Split to the preview storage using the No Patching (v1) method.
   *
   * @param \Drupal\Core\Config\ImmutableConfig $config
   *   The split config.
   * @param \Drupal\Core\Config\StorageInterface $transforming
   *   The transforming storage.
   * @param \Drupal\Core\Config\StorageInterface $splitStorage
   *   The splits preview storage.
   */
  public function splitPreviewNoPatching(ImmutableConfig $config, StorageInterface $transforming, StorageInterface $splitStorage): void {
    // Empty the split storage.
    foreach (array_merge([StorageInterface::DEFAULT_COLLECTION], $splitStorage->getAllCollectionNames()) as $collection) {
      $splitStorage->createCollection($collection)->deleteAll();
    }
    $transforming = $transforming->createCollection(StorageInterface::DEFAULT_COLLECTION);
    $splitStorage = $splitStorage->createCollection(StorageInterface::DEFAULT_COLLECTION);
    $source = $this->active->createCollection(StorageInterface::DEFAULT_COLLECTION);
    if ($config->get('stackable')) {
      // We need to copy the transforming storage so that we don't change what
      // we later compare with.
      $source = new MemoryStorage();
      self::replaceStorageContents($transforming, $source);
    }

    // Get complete / partial lists.
    $complete_split_list = $this->calculateCompleteSplitList($config, $source);
    $conditional_split_list = $this->calculateConditionalSplitList($config, $source);

    // Split the configuration that needs to be split.
    foreach (array_merge([StorageInterface::DEFAULT_COLLECTION], $transforming->getAllCollectionNames()) as $collection) {
      $storage = $transforming->createCollection($collection);
      $split = $splitStorage->createCollection($collection);
      $sync = $this->sync->createCollection($collection);
      foreach ($storage->listAll() as $name) {
        $data = $storage->read($name);
        if ($data === FALSE) {
          continue;
        }

        if (in_array($name, $complete_split_list)) {
          if ($data) {
            $split->write($name, $data);
          }

          // Remove it from the transforming storage.
          $storage->delete($name);
        }
        if (in_array($name, $conditional_split_list)) {
          $syncData = $sync->read($name);
          if ($syncData !== $data) {
            // The source does not have the same data, so write to secondary and
            // return source data or null if it doesn't exist in the source.
            $split->write($name, $data);

            // If it is in the sync config write that to transforming storage.
            if ($syncData !== FALSE) {
              $storage->write($name, $syncData);
            }
            else {
              $storage->delete($name);
            }
          }
        }
      }
    }

    // Now special case the extensions.
    $extensions = $transforming->read('core.extension');
    if ($extensions === FALSE) {
      return;
    }
    // Split off the extensions.
    $extensions['module'] = array_diff_key($extensions['module'], $config->get('module') ?? []);
    $extensions['theme'] = array_diff_key($extensions['theme'], $config->get('theme') ?? []);

    $transforming->write('core.extension', $extensions);
  }

  /**
   * Importing and activating are almost the same.
   *
   * @param \Drupal\Core\Config\ImmutableConfig $split
   *   The split.
   * @param \Drupal\Core\Config\StorageInterface $storage
   *   The storage.
   * @param bool $activate
   *   Whether to activate the split in the transformation.
   *
   * @return \Drupal\Core\Config\StorageInterface
   *   The storage to pass to a ConfigImporter to do the config changes.
   */
  protected function singleImportOrActivate(ImmutableConfig $split, StorageInterface $storage, bool $activate): StorageInterface {
    $transformation = new MemoryStorage();
    static::replaceStorageContents($this->active, $transformation);

    $this->mergeSplit($split, $transformation, $storage);

    // Activate the split in the transformation so that the importer does it.
    $config = $transformation->read($split->getName());
    if ($activate && $config !== FALSE) {
      $config['status'] = TRUE;
      $transformation->write($split->getName(), $config);
    }

    return $transformation;
  }

  /**
   * Process changes the config manager calculated into the storages.
   *
   * @param array $changes
   *   The changes from getConfigEntitiesToChangeOnDependencyRemoval().
   * @param \Drupal\Core\Config\StorageInterface $source
   *   The storage to take the config from.
   * @param \Drupal\Core\Config\StorageInterface $storage
   *   The primary config transformation storage.
   * @param \Drupal\Core\Config\StorageInterface $split
   *   The split storage.
   */
  protected function processEntitiesToChangeOnDependencyRemoval(array $changes, StorageInterface $source, StorageInterface $storage, StorageInterface $split) {
    // Process entities that need to be updated.
    /** @var \Drupal\Core\Config\Entity\ConfigEntityInterface $entity */
    foreach ($changes['update'] as $entity) {
      $name = $entity->getConfigDependencyName();
      if ($split->exists($name)) {
        // The config is already completely split.
        continue;
      }

      // We use the active store because we also load the entity from it.
      $original = $this->active->read($name);
      $updated = $entity->toArray();

      $patch = $this->createPatch($name, $original, $updated, $split);
      if (!$patch->isEmpty() && $storage->exists($name)) {
        // We update the data in the transformation storage to apply the
        // combined patch.
        $data = $storage->read($name);
        $data = $this->patchMerge->mergePatch($data, $patch, $name);

        $storage->write($name, $data);
      }
    }

    // Process entities that need to be deleted.
    /** @var \Drupal\Core\Config\Entity\ConfigEntityInterface $entity */
    foreach ($changes['delete'] as $entity) {
      $name = $entity->getConfigDependencyName();
      self::moveConfigToSplit($name, $source, $split, $storage);
    }
  }

  /**
   * Move a config from the source to the split storage.
   *
   * @param string $name
   *   The name of the config.
   * @param \Drupal\Core\Config\StorageInterface $source
   *   The source storage.
   * @param \Drupal\Core\Config\StorageInterface $split
   *   The target storage.
   * @param \Drupal\Core\Config\StorageInterface $transforming
   *   The transforming storage from which to remove the config.
   */
  protected static function moveConfigToSplit(string $name, StorageInterface $source, StorageInterface $split, StorageInterface $transforming) {
    if ($source->exists($name)) {
      // If a partial split has already been written, delete it.
      if ($split->exists(self::SPLIT_PARTIAL_PREFIX . $name)) {
        $split->delete(self::SPLIT_PARTIAL_PREFIX . $name);
      }
      // Write the data to the split.
      $split->write($name, $source->read($name));
    }
    $transforming->delete($name);
  }

  /**
   * Create a patch and write it to the storage.
   *
   * @param string $name
   *   The name of the config.
   * @param array $original
   *   The original value.
   * @param array $updated
   *   The updated value.
   * @param \Drupal\Core\Config\StorageInterface $storage
   *   The storage to save the patch to.
   *
   * @return \Drupal\config_split\Config\ConfigPatch
   *   The created patch.
   */
  protected function createPatch($name, array $original, array $updated, StorageInterface $storage): ConfigPatch {
    if ($storage->exists(self::SPLIT_PARTIAL_PREFIX . $name)) {
      // If the storage already contains a patch for the same config, merge it.
      $existing = ConfigPatch::fromArray($storage->read(self::SPLIT_PARTIAL_PREFIX . $name));
      $updated = $this->patchMerge->mergePatch($updated, $existing, $name);
    }
    $patch = $this->patchMerge->createPatch($original, $updated, $name);
    if (!$patch->isEmpty()) {
      $storage->write(self::SPLIT_PARTIAL_PREFIX . $name, $patch->toArray());
    }

    return $patch;
  }

  /**
   * Prepare a storage to compare partial config with.
   *
   * @param \Drupal\Core\Config\ImmutableConfig $config
   *   The config which we are comparing.
   *
   * @return \Drupal\Core\Config\StorageInterface
   *   The storage to use for comparing the partial split config.
   */
  protected function prepareSyncForPartialComparison(ImmutableConfig $config): StorageInterface {
    if (!$config->get('stackable')) {
      return $this->sync;
    }

    // Create a new storage and fill it with the sync storage contents.
    $composed = new MemoryStorage();
    self::replaceStorageContents($this->sync, $composed);
    // Load all splits and sort them.
    $splits = $this->loadMultiple($this->listAll());
    unset($splits[$config->getName()]);
    uasort($splits, function (ImmutableConfig $a, ImmutableConfig $b) {
      // Sort in reverse order, we need the import order.
      return $b->get('weight') <=> $a->get('weight');
    });

    // Merge all active splits export storage except the one to compare.
    foreach ($splits as $split) {
      if (!$split->get('status') || $split->get('weight') <= $config->get('weight') || !$split->get('stackable')) {
        // Exclude inactive splits and splits that come before on export.
        continue;
      }
      $this->mergeSplit($split, $composed, $this->singleExportTarget($split));
    }

    return $composed;
  }

  /**
   * Check whether the needle is in the haystack.
   *
   * @param string $name
   *   The needle which is checked.
   * @param string[] $list
   *   The haystack, a list of identifiers to determine whether $name is in it.
   *
   * @return bool
   *   True if the name is considered to be in the list.
   */
  protected static function inFilterList($name, array $list) {
    // Prepare the list for regex matching by quoting all regex symbols and
    // replacing back the original '*' with '.*' to allow it to catch all.
    $list = array_map(function ($line) {
      return str_replace('\*', '.*', preg_quote($line, '/'));
    }, $list);
    foreach ($list as $line) {
      if (preg_match('/^' . $line . '$/', $name)) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * Get the list of completely split config.
   *
   * @param \Drupal\Core\Config\ImmutableConfig $config
   *   The split config.
   * @param \Drupal\Core\Config\StorageInterface $source
   *   The source storage.
   *
   * @return string[]
   *   The list of config names.
   */
  protected function calculateCompleteSplitList(ImmutableConfig $config, StorageInterface $source) {
    $configDependencyManager = $this->getConfigDependencyManager($source);
    $completeList = $config->get('complete_list');
    $modules = array_keys($config->get('module'));
    if ($modules) {
      $completeList = array_merge($completeList, array_keys($this->findConfigEntityDependencies('module', $modules, $configDependencyManager)));
    }

    $themes = array_keys($config->get('theme'));
    if ($themes) {
      $completeList = array_merge($completeList, array_keys($this->findConfigEntityDependencies('theme', $themes, $configDependencyManager)));
    }

    $extensions = array_merge([], $modules, $themes);

    if (empty($completeList) && empty($extensions)) {
      // Early return to short-circuit the expensive calculations.
      return [];
    }

    $completeList = array_filter($source->listAll(), function ($name) use ($extensions, $completeList) {
        // Filter the list of config objects since they are not included in
        // findConfigEntityDependents.
      foreach ($extensions as $extension) {
        if (strpos($name, $extension . '.') === 0) {
          return TRUE;
        }
      }

        // Add the config name to the blacklist if it is in the wildcard list.
        return self::inFilterList($name, $completeList);
    }
    );
    sort($completeList);
    // Finally merge all dependencies of the blacklisted config.
    $completeList = array_unique(array_merge($completeList, array_keys($this->findConfigEntityDependencies('config', $completeList, $configDependencyManager))));
    // Exclude from the complete split what is conditionally split.
    return array_diff($completeList, $this->calculateConditionalSplitList($config, $source));
  }

  /**
   * Get the list of conditionally split config.
   *
   * @param \Drupal\Core\Config\ImmutableConfig $config
   *   The split config.
   * @param \Drupal\Core\Config\StorageInterface $source
   *   The source storage.
   *
   * @return string[]
   *   The list of config names.
   */
  protected function calculateConditionalSplitList(ImmutableConfig $config, StorageInterface $source) {
    $partialList = $config->get('partial_list');

    if (empty($partialList)) {
      // Early return to short-circuit the expensive calculations.
      return [];
    }

    $partialList = array_filter($source->listAll(), function ($name) use ($partialList) {
        // Add to the partial list if it is in the wildcard list.
        return self::inFilterList($name, $partialList);
    }
    );
    sort($partialList);

    return $partialList;
  }

  /**
   * Finds config entities that are dependent on extensions or entities.
   *
   * We fork this from the config manager because we want to pass the dependency
   * manager which is based on the storage and the interface doesn't have that.
   *
   * @param string $type
   *   The type of dependency being checked. Either 'module', 'theme', 'config'
   *   or 'content'.
   * @param array $names
   *   The specific names to check. If $type equals 'module' or 'theme' then it
   *   should be a list of module names or theme names. In the case of 'config'
   *   or 'content' it should be a list of configuration dependency names.
   * @param \Drupal\Core\Config\Entity\ConfigDependencyManager $dependencyManager
   *   The config dependency manager.
   *
   * @return \Drupal\Core\Config\Entity\ConfigEntityDependency[]
   *   An array of configuration entity dependency objects.
   */
  private function findConfigEntityDependencies($type, array $names, ConfigDependencyManager $dependencyManager) {
    $dependencies = [];
    foreach ($names as $name) {
      $dependencies[] = $dependencyManager->getDependentEntities($type, $name);
    }
    return array_merge(...$dependencies);
  }

  /**
   * {@inheritdoc}
   */
  private function getConfigDependencyManager(StorageInterface $storage) {
    $dependency_manager = new ConfigDependencyManager();
    // Contrary to the ConfigManager we do not want to use the active storage.
    // Assume data with UUID is a config entity. Only configuration entities can
    // be depended on, so we can ignore everything else.
    $data = array_map(function ($data) {
      if (isset($data['uuid'])) {
        return $data;
      }
      return FALSE;
    }, $storage->readMultiple($storage->listAll()));
    $dependency_manager->setData(array_filter($data));
    return $dependency_manager;
  }

}
