<?php

namespace Drupal\config_snapshot;

use Drupal\Core\Config\StorageInterface;
use Drupal\config_snapshot\Entity\ConfigSnapshot;

/**
 * Provides a configuration storage saved as simple configuration.
 */
class ConfigSnapshotStorage implements StorageInterface {

  /**
   * The snapshot set.
   *
   * A set is a group of snapshots used for a particular purpose. A set should
   * be namespaced for the module introducing it.
   *
   * @var string
   */
  protected $snapshotSet;

  /**
   * The extension type.
   *
   * @var string
   */
  protected $extensionType;

  /**
   * The extension name.
   *
   * @var string
   */
  protected $extensionName;

  /**
   * The storage collection.
   *
   * @var string
   */
  protected $collection;

  /**
   * The configuration snapshot.
   *
   * @var \Drupal\config_snapshot\Entity\ConfigSnapshot
   */
  protected $configSnapshot;

  /**
   * Constructs a new ConfigSnapshotStorage.
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
   */
  public function __construct($snapshot_set, $extension_type, $extension_name, $collection = StorageInterface::DEFAULT_COLLECTION, ?ConfigSnapshot $config_snapshot = NULL) {
    $this->snapshotSet = $snapshot_set;
    $this->extensionType = $extension_type;
    $this->extensionName = $extension_name;
    $this->collection = $collection;
    $this->setConfigSnapshot($config_snapshot);
  }

  /**
   * Sets the config snapshot object associated with an extension.
   *
   * The function reads the config_snapshot object from the current
   * configuration, or returns a ready-to-use empty one if no configuration
   * entry exists yet for the extension.
   */
  protected function setConfigSnapshot($config_snapshot) {
    if (is_null($config_snapshot)) {
      // Try loading the snapshot from configuration.
      $config_snapshot = ConfigSnapshot::load("{$this->snapshotSet}.{$this->extensionType}.{$this->extensionName}");

      // If not found, create a fresh snapshot object.
      if (!$config_snapshot) {
        $config_snapshot = ConfigSnapshot::create([
          'snapshotSet' => $this->snapshotSet,
          'extensionType' => $this->extensionType,
          'extensionName' => $this->extensionName,
        ]);
      }
    }

    $this->configSnapshot = $config_snapshot;
  }

  /**
   * {@inheritdoc}
   */
  public function exists($name) {
    return !is_null($this->configSnapshot->getItem($this->collection, $name));
  }

  /**
   * {@inheritdoc}
   */
  public function read($name) {
    if ($item = $this->configSnapshot->getItem($this->collection, $name)) {
      return $item['data'];
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function readMultiple(array $names) {
    $data = [];
    foreach ($names as $name) {
      $value = $this->read($name);
      if ($value !== FALSE) {
        $data[$name] = $value;
      }
    }

    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function write($name, array $data) {
    $this->configSnapshot
      ->setItem($this->collection, $name, $data)
      ->save();

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function delete($name) {
    if (!$this->exists($name)) {
      return FALSE;
    }
    $this->configSnapshot
      ->clearItem($this->collection, $name)
      ->save();

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function rename($name, $new_name) {
    if (!$this->exists($name)) {
      return FALSE;
    }
    $this->write($new_name, $this->read($name));

    return $this->delete($name);
  }

  /**
   * {@inheritdoc}
   */
  public function encode($data) {
    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function decode($raw) {
    return $raw;
  }

  /**
   * {@inheritdoc}
   */
  public function listAll($prefix = '') {
    $names = [];
    $items = $this->configSnapshot->getItems();

    // Find the keys of the items in the current collection.
    $collection_keys = array_keys(array_column($items, 'collection'), $this->collection);

    if ($prefix === '') {
      $name_items = array_column($items, 'name');
      // Find all names in the current collection.
      $names = array_values(array_intersect_key($name_items, array_flip($collection_keys)));
    }
    else {
      foreach ($collection_keys as $key) {
        if (strpos($items[$key]['name'], $prefix) === 0) {
          $names[] = $items[$key]['name'];
        }
      }
    }

    return $names;
  }

  /**
   * {@inheritdoc}
   */
  public function deleteAll($prefix = '') {
    $original_items = $items = $this->configSnapshot->getItems();
    $collection = $this->getCollectionName();
    $collection_items = array_filter($items, function ($item) use ($collection) {
      return ($item['collection'] === $collection);
    });
    if ($prefix === '') {
      $items = array_diff_key($items, $collection_items);
    }
    else {
      foreach (array_keys($collection_items) as $key) {
        if (strpos($items[$key]['name'], $prefix) === 0) {
          unset($items[$key]);
        }
      }
    }
    // Determine if any items have changed.
    if ($items !== $original_items) {
      $this->configSnapshot
        ->setItems($items)
        ->save();

      return TRUE;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function createCollection($collection) {
    return new static(
      $this->snapshotSet,
      $this->extensionType,
      $this->extensionName,
      $collection,
      $this->configSnapshot
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getAllCollectionNames() {
    $items = $this->configSnapshot->getItems();
    $collections = array_unique(array_column($items, 'collection'));
    // The default collection is not included here.
    unset($collections[array_search(StorageInterface::DEFAULT_COLLECTION, $collections)]);

    return array_values($collections);
  }

  /**
   * {@inheritdoc}
   */
  public function getCollectionName() {
    return $this->collection;
  }

  /**
   * Writes configuration data to the storage for a collection.
   *
   * @param string $name
   *   The name of a configuration object to save.
   * @param array $data
   *   The configuration data to write.
   * @param string $collection
   *   The collection to store configuration in.
   *
   * @return bool
   *   TRUE on success, FALSE in case of an error.
   */
  public function writeToCollection($name, array $data, $collection) {
    $this->configSnapshot
      ->setItem($collection, $name, $data)
      ->save();

    return TRUE;
  }

}
