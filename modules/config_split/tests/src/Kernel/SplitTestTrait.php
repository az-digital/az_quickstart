<?php

namespace Drupal\Tests\config_split\Kernel;

use Drupal\config_split\Config\SplitCollectionStorage;
use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigImporter;
use Drupal\Core\Config\DatabaseStorage;
use Drupal\Core\Config\FileStorage;
use Drupal\Core\Config\MemoryStorage;
use Drupal\Core\Config\StorageComparer;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Site\Settings;

/**
 * Trait to facilitate creating split configurations.
 */
trait SplitTestTrait {

  /**
   * Create a split configuration.
   *
   * @param string $name
   *   The name of the split.
   * @param array $data
   *   The split config data.
   *
   * @return \Drupal\Core\Config\Config
   *   The split config object.
   */
  protected function createSplitConfig(string $name, array $data): Config {
    if (substr($name, 0, strlen('config_split.config_split.')) !== 'config_split.config_split.') {
      // Allow using the id as the config name to keep it short.
      $name = 'config_split.config_split.' . $name;
    }
    // Add default values.
    $data += [
      'storage' => (isset($data['folder']) && $data['folder'] != '') ? 'folder' : 'database',
      'status' => TRUE,
      'stackable' => FALSE,
      'weight' => 0,
      'folder' => (isset($data['storage']) && $data['storage'] == 'folder') ? Settings::get('file_public_path') . "/config/split/$name/" : '',
      'module' => [],
      'theme' => [],
      'complete_list' => [],
      'partial_list' => [],
      'no_patching' => FALSE,
    ];
    // Set the id from the name.
    $data['id'] = substr($name, strlen('config_split.config_split.'));
    // Create the config.
    $config = new Config($name, $this->container->get('config.storage'), $this->container->get('event_dispatcher'), $this->container->get('config.typed'));
    $config->initWithData($data)->save();

    return $config;
  }

  /**
   * Get the storage for a split.
   *
   * @param \Drupal\Core\Config\Config $config
   *   The split config.
   *
   * @return \Drupal\Core\Config\StorageInterface
   *   The storage.
   */
  protected function getSplitSourceStorage(Config $config): StorageInterface {
    switch ($config->get('storage')) {
      case 'folder':
        return new FileStorage($config->get('folder'));

      case 'collection':
        // @phpstan-ignore-next-line
        return new SplitCollectionStorage($this->getSyncFileStorage(), $config->get('id'));

      case 'database':
        // We don't escape the name, it is tests after all.
        return new DatabaseStorage($this->container->get('database'), strtr($config->getName(), ['.' => '_']));
    }
    throw new \LogicException();
  }

  /**
   * Get the preview storage for a split.
   *
   * @param \Drupal\Core\Config\Config $config
   *   The split config.
   * @param \Drupal\Core\Config\StorageInterface $export
   *   The export storage to graft collection storages on.
   *
   * @return \Drupal\Core\Config\StorageInterface
   *   The storage.
   */
  protected function getSplitPreviewStorage(Config $config, ?StorageInterface $export = NULL): StorageInterface {
    if ('collection' === $config->get('storage')) {
      if ($export === NULL) {
        throw new \InvalidArgumentException();
      }
      return new SplitCollectionStorage($export, $config->get('id'));
    }
    $name = substr($config->getName(), strlen('config_split.config_split.'));
    $storage = new DatabaseStorage($this->container->get('database'), 'config_split_preview_' . strtr($name, ['.' => '_']));
    // We cache it in its own memory storage so that it becomes decoupled.
    $memory = new MemoryStorage();
    $this->copyConfig($storage, $memory);
    return $memory;
  }

  /**
   * Merge a split storage into a base storage.
   *
   * This uses the ConfigSplitManager, it should not be used to verify that
   * a split is imported correctly but rather that the merge works as expected.
   *
   * @param \Drupal\Core\Config\Config $config
   *   The config to import.
   * @param \Drupal\Core\Config\StorageInterface $base
   *   The base storage to merge the split into. It will not be modified.
   * @param \Drupal\Core\Config\StorageInterface $splitStorage
   *   The split storage to merge.
   *
   * @return \Drupal\Core\Config\StorageInterface
   *   The merged storage.
   */
  protected function mergeSplit(Config $config, StorageInterface $base, StorageInterface $splitStorage): StorageInterface {
    $storage = new MemoryStorage();
    $this->copyConfig($base, $storage);

    $manager = $this->container->get('config_split.manager');
    $immutable = $manager->getSplitConfig($config->getName());
    $manager->mergeSplit($immutable, $storage, $splitStorage);

    return $storage;
  }

  /**
   * Get the data of a config storage as an array to inspect.
   *
   * This will be added to config filter at some point.
   *
   * @param \Drupal\Core\Config\StorageInterface $storage
   *   The config storage to inspect.
   *
   * @return array
   *   The data in the config storage.
   */
  protected function getStorageData(StorageInterface $storage): array {
    $data = [];
    foreach (array_merge([StorageInterface::DEFAULT_COLLECTION], $storage->getAllCollectionNames()) as $collection) {
      $storage = $storage->createCollection($collection);
      foreach ($storage->listAll() as $name) {
        $data[$collection][$name] = $storage->read($name);
      }
    }

    return $data;
  }

  /**
   * Validate the config to import.
   *
   * This will be added to config filter at some point.
   *
   * @param \Drupal\Core\Config\StorageInterface $storage
   *   Validate if the content of the storage can be imported.
   *
   * @throws \Exception
   *   When something goes wrong.
   */
  protected function validateImport(StorageInterface $storage): void {
    $container = $this->container;
    $importer = new ConfigImporter(
      new StorageComparer($storage, $container->get('config.storage')),
      $container->get('event_dispatcher'),
      $container->get('config.manager'),
      $container->get('lock.persistent'),
      $container->get('config.typed'),
      $container->get('module_handler'),
      $container->get('module_installer'),
      $container->get('theme_handler'),
      $container->get('string_translation'),
      $container->get('extension.list.module'),
      $container->get('extension.list.theme'),
    );

    $importer->validate();
  }

}
