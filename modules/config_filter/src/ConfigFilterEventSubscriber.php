<?php

namespace Drupal\config_filter;

use Drupal\Core\Config\MemoryStorage;
use Drupal\Core\Config\StorageCopyTrait;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Config\StorageTransformEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber bridging the Config Filter and Drupal 8.8 core API.
 */
class ConfigFilterEventSubscriber implements EventSubscriberInterface {

  use StorageCopyTrait;

  /**
   * The filter storage factory.
   *
   * @var \Drupal\config_filter\ConfigFilterStorageFactory
   */
  protected $filterStorageFactory;

  /**
   * The sync storage.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $sync;

  /**
   * ConfigFilterEventSubscriber constructor.
   *
   * @param \Drupal\config_filter\ConfigFilterStorageFactory $filterStorageFactory
   *   The filter storage factory.
   * @param \Drupal\Core\Config\StorageInterface $sync
   *   The sync storage.
   */
  public function __construct(ConfigFilterStorageFactory $filterStorageFactory, StorageInterface $sync) {
    $this->filterStorageFactory = $filterStorageFactory;
    $this->sync = $sync;
  }

  /**
   * The storage is transformed for importing.
   *
   * @param \Drupal\Core\Config\StorageTransformEvent $event
   *   The event for altering configuration of the storage.
   */
  public function onImportTransform(StorageTransformEvent $event) {
    $storage = $event->getStorage();
    // The temporary storage representing the active storage.
    $temp = new MemoryStorage();
    // Get the filtered storage based on the event storage.
    $filtered = $this->filterStorageFactory->getFilteredStorage($storage, ['config.storage.sync']);
    // Simulate the importing of configuration.
    self::replaceAllStorageContents($filtered, $temp);
    // Set the event storage to the one of the simulated import.
    self::replaceStorageContents($temp, $storage);
  }

  /**
   * The storage is transformed for exporting.
   *
   * @param \Drupal\Core\Config\StorageTransformEvent $event
   *   The event for altering configuration of the storage.
   */
  public function onExportTransform(StorageTransformEvent $event) {
    $storage = $event->getStorage();
    // The temporary storage representing the sync storage.
    $temp = new MemoryStorage();
    // Copy the contents of the sync storage to the temporary one.
    self::replaceAllStorageContents($this->sync, $temp);
    // Get the simulated filtered sync storage.
    $filtered = $this->filterStorageFactory->getFilteredStorage($temp, ['config.storage.sync']);
    // Simulate the exporting of the configuration.
    self::replaceAllStorageContents($storage, $filtered);
    // Set the event storage to the inner storage of the simulated sync storage.
    self::replaceStorageContents($temp, $storage);
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // @todo Use class constants when they get added in #2991683
    $events['config.transform.import'][] = ['onImportTransform'];
    $events['config.transform.export'][] = ['onExportTransform'];
    return $events;
  }

  /**
   * Copy the configuration from one storage to another and remove stale items.
   *
   * This method is the copy of how it worked prior to Drupal 9.4.
   * See https://www.drupal.org/node/3273823 for more details.
   *
   * @param \Drupal\Core\Config\StorageInterface $source
   *   The configuration storage to copy from.
   * @param \Drupal\Core\Config\StorageInterface $target
   *   The configuration storage to copy to.
   */
  protected static function replaceAllStorageContents(StorageInterface $source, StorageInterface &$target) {
    // Make sure there is no stale configuration in the target storage.
    foreach (array_merge([StorageInterface::DEFAULT_COLLECTION], $target->getAllCollectionNames()) as $collection) {
      $target->createCollection($collection)->deleteAll();
    }

    // Copy all the configuration from all the collections.
    foreach (array_merge([StorageInterface::DEFAULT_COLLECTION], $source->getAllCollectionNames()) as $collection) {
      $source_collection = $source->createCollection($collection);
      $target_collection = $target->createCollection($collection);
      foreach ($source_collection->listAll() as $name) {
        $data = $source_collection->read($name);
        if ($data !== FALSE) {
          $target_collection->write($name, $data);
        }
        else {
          \Drupal::logger('config')->notice('Missing required data for configuration: %config', [
            '%config' => $name,
          ]);
        }
      }
    }

    // Make sure that the target is set to the same collection as the source.
    $target = $target->createCollection($source->getCollectionName());
  }

}
