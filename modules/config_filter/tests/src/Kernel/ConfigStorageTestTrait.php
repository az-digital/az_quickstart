<?php

namespace Drupal\Tests\config_filter\Kernel;

use Drupal\Core\Config\ExportStorageManager;
use Drupal\Core\Config\FileStorage;
use Drupal\Core\Config\ReadOnlyStorage;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Lock\NullLockBackend;
use Drupal\Core\Site\Settings;

/**
 * Trait to easily test import and export of config by comparing storages.
 *
 * The "new" config storage transformation API which was added to drupal 8.8
 * transforms the storage before importing and exporting via the event system.
 * This is a very different API from Config Filter 1.x but they work together.
 *
 * This test trait aims to provide methods to guide developers writing tests
 * that work independently of whether the module uses Config Filter plugins or
 * the new core events. You do this by comparing what you expect to be imported
 * or exported.
 *
 * Drupal core <8.8 with config filter:
 * export:
 * [active] ----------------------------------------> [filter wrapping [sync]]
 * import:
 * [filter wrapping [sync]] ----------------------------------------> [active]
 *
 * Drupal core >=8.8:
 * export:
 * [active] ---------------> [export transformation] -----------------> [sync]
 * import:
 * [sync] -----------------> [import transformation] ---------------> [active]
 *
 * The test for exporting should set up the active store by saving config and
 * what is expected to be exported and then compare the export storage with it.
 * The test for importing should set up the sync store by writing files (or by
 * saving arrays to the FileStorage) and then comparing the expectations with
 * the import storage.
 *
 * Tests using this trait will work with Config Filter 1.x and 2.x and they will
 * also work when using the core api directly provided the module has the same
 * behaviour with the event listener as with the config filter plugin.
 *
 * @see \Drupal\Tests\config_filter\Kernel\ExampleStorageKernelTest
 */
trait ConfigStorageTestTrait {

  /**
   * Copies configuration objects from source storage to target storage.
   *
   * This method is defined in a trait used in KernelTestBase.
   */
  abstract protected function copyConfig(StorageInterface $source_storage, StorageInterface $target_storage);

  /**
   * The active config store, read only.
   *
   * @return \Drupal\Core\Config\StorageInterface
   *   The storage with the active config.
   */
  protected function getActiveStorage(): StorageInterface {
    // Return the active storage in read-only mode. Config should be properly
    // saved and the active storage should not be directly manipulated.
    return new ReadOnlyStorage($this->container->get('config.storage'));
  }

  /**
   * The file storage associated with the sync storage.
   *
   * Use this to manipulate the files in the sync folder. In order to export the
   * configuration to the sync storage use the following:
   * @code
   * $this->copyConfig($this->getExportStorage(), $this->getSyncFileStorage());
   * @endcode
   *
   * @return \Drupal\Core\Config\StorageInterface
   *   The storage with the files from the sync folder.
   */
  protected function getSyncFileStorage(): StorageInterface {
    // We do not return the config.storage.sync service so that we can bypass
    // config filter.
    return new FileStorage(Settings::get('config_sync_directory'));
  }

  /**
   * Trigger the export transformation and return its result.
   *
   * @return \Drupal\Core\Config\StorageInterface
   *   The storage with the config to be exported.
   */
  protected function getExportStorage(): StorageInterface {
    $manager = new ExportStorageManager(
      $this->container->get('config.storage'),
      $this->container->get('database'),
      $this->container->get('event_dispatcher'),
      // We use the null lock because these tests can anyway not be run in
      // parallel. We would have to lock around accessing the storage and
      // copying the content to a new memory storage.
      new NullLockBackend()
    );
    // This is the same essentially as the config.storage.export service
    // but the container doesn't cache it so we can access it several times
    // with updated config in the same test and trigger the transformation anew.
    return $manager->getStorage();
  }

  /**
   * Trigger the import transformation and return its result.
   *
   * @return \Drupal\Core\Config\StorageInterface
   *   The storage with the config to be imported.
   */
  protected function getImportStorage(): StorageInterface {
    // Transform the sync storage for import.
    return $this->container->get('config.import_transformer')->transform($this->container->get('config.storage.sync'));
  }

  /**
   * Asserts that two config storage objects have the same content.
   *
   * @param \Drupal\Core\Config\StorageInterface $expected
   *   The storage with the expected data.
   * @param \Drupal\Core\Config\StorageInterface $actual
   *   The storage with the actual data.
   * @param string $message
   *   The message to add to the assertion.
   */
  protected static function assertStorageEquals(StorageInterface $expected, StorageInterface $actual, string $message = '') {
    // The same collections have to exist.
    static::assertEqualsCanonicalizing($expected->getAllCollectionNames(), $actual->getAllCollectionNames(), $message);
    // Now loop over all collections and assert the data to be equal.
    foreach (array_merge([StorageInterface::DEFAULT_COLLECTION], $expected->getAllCollectionNames()) as $collection) {
      $expected_collection = $expected->createCollection($collection);
      $actual_collection = $actual->createCollection($collection);
      // The same names are present in both.
      static::assertEqualsCanonicalizing($expected_collection->listAll(), $actual_collection->listAll(), $message);
      foreach ($expected_collection->listAll() as $name) {
        // The same data can be read from both.
        static::assertEquals($expected_collection->read($name), $actual_collection->read($name), $message . ' ' . $name);
      }
    }
  }

}
