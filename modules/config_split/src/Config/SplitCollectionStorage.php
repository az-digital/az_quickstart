<?php

namespace Drupal\config_split\Config;

use Drupal\Core\Config\NullStorage;
use Drupal\Core\Config\StorageInterface;

/**
 * A config storage that lives in a collection of another config storage.
 */
class SplitCollectionStorage implements StorageInterface {

  const PREFIX = 'split.';

  /**
   * The storage into which the config is saved.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $storage;

  /**
   * The name of the storage.
   *
   * This corresponds to the prefixed name of the collection in the storage.
   *
   * @var string
   */
  protected $name;

  /**
   * The storage collection.
   *
   * @var string
   */
  protected $collection;

  /**
   * SplitCollectionStorage constructor.
   *
   * @param \Drupal\Core\Config\StorageInterface $storage
   *   The storage onto which the collection is grafted.
   * @param string $name
   *   The name of storage, must be a valid collection name.
   * @param string $collection
   *   The collection name.
   */
  public function __construct(StorageInterface $storage, string $name, string $collection = StorageInterface::DEFAULT_COLLECTION) {
    $this->name = $name;
    $this->collection = $collection;
    $this->storage = $storage->createCollection(static::PREFIX . $name . ($collection !== StorageInterface::DEFAULT_COLLECTION ? '.' . $collection : ''));
  }

  /**
   * {@inheritdoc}
   */
  public function exists($name) {
    return $this->getStorage()->exists($name);
  }

  /**
   * {@inheritdoc}
   */
  public function read($name) {
    return $this->getStorage()->read($name);
  }

  /**
   * {@inheritdoc}
   */
  public function readMultiple(array $names) {
    return $this->getStorage()->readMultiple($names);
  }

  /**
   * {@inheritdoc}
   */
  public function write($name, array $data) {
    return $this->getStorage()->write($name, $data);
  }

  /**
   * {@inheritdoc}
   */
  public function delete($name) {
    return $this->getStorage()->delete($name);
  }

  /**
   * {@inheritdoc}
   */
  public function rename($name, $new_name) {
    return $this->getStorage()->rename($name, $new_name);
  }

  /**
   * {@inheritdoc}
   */
  public function encode($data) {
    return $this->getStorage()->encode($data);
  }

  /**
   * {@inheritdoc}
   */
  public function decode($raw) {
    return $this->getStorage()->decode($raw);
  }

  /**
   * {@inheritdoc}
   */
  public function listAll($prefix = '') {
    return $this->getStorage()->listAll($prefix);
  }

  /**
   * {@inheritdoc}
   */
  public function deleteAll($prefix = '') {
    return $this->getStorage()->deleteAll($prefix);
  }

  /**
   * {@inheritdoc}
   */
  public function createCollection($collection) {
    return new static($this->storage, $this->name, $collection);
  }

  /**
   * {@inheritdoc}
   */
  public function getAllCollectionNames() {
    return array_values(array_filter(array_map(function ($c) {
      $prefix = static::PREFIX . $this->name . '.';
      if (strpos($c, $prefix) !== 0) {
        // If the underlying storage has collections that don't concern us,
        // they will be filtered out, including the default collection.
        return FALSE;
      }
      return substr($c, strlen($prefix));
    }, $this->storage->getAllCollectionNames())));
  }

  /**
   * {@inheritdoc}
   */
  public function getCollectionName() {
    return $this->collection;
  }

  /**
   * Get the storage to interact with.
   *
   * @return \Drupal\Core\Config\StorageInterface
   *   The config storage.
   */
  protected function getStorage() {
    if (strpos($this->collection, static::PREFIX) !== FALSE) {
      // Avoid recursion.
      // This means this storage can not live inside another one of this type.
      return new NullStorage();
    }

    return $this->storage;
  }

}
