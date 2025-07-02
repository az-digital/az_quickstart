<?php

namespace Drupal\config_filter\Config;

use Drupal\Core\Config\StorageInterface;
use Drupal\config_filter\Exception\InvalidStorageFilterException;

/**
 * Class FilteredStorage.
 *
 * This class wraps another storage.
 * It filters the arguments before passing them on to the storage for write
 * operations and filters the result of read operations before returning them.
 *
 * @package Drupal\config_filter\Config
 */
class FilteredStorage implements FilteredStorageInterface {

  /**
   * The storage container that we are wrapping.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $storage;

  /**
   * The storage filters.
   *
   * @var \Drupal\config_filter\Config\StorageFilterInterface[]
   */
  protected $filters;

  /**
   * Create a FilteredStorage with some storage and a filter.
   *
   * @param \Drupal\Core\Config\StorageInterface $storage
   *   The decorated storage.
   * @param \Drupal\config_filter\Config\StorageFilterInterface[] $filters
   *   The filters to apply in the given order.
   */
  public function __construct(StorageInterface $storage, array $filters) {
    $this->storage = $storage;
    $this->filters = $filters;

    // Set the storage to all the filters.
    foreach ($this->filters as $filter) {
      if (!$filter instanceof StorageFilterInterface) {
        throw new InvalidStorageFilterException();
      }
      $filter->setSourceStorage(new ReadOnlyStorage($storage));
      $filter->setFilteredStorage($this);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function exists($name) {
    $exists = $this->storage->exists($name);
    foreach ($this->filters as $filter) {
      $exists = $filter->filterExists($name, $exists);
    }

    return $exists;
  }

  /**
   * {@inheritdoc}
   */
  public function read($name) {
    $data = $this->storage->read($name);
    foreach ($this->filters as $filter) {
      $data = $filter->filterRead($name, $data);
    }

    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function readMultiple(array $names) {
    $data = $this->storage->readMultiple($names);
    foreach ($this->filters as $filter) {
      $data = $filter->filterReadMultiple($names, $data);
    }
    ksort($data);
    return array_filter($data);
  }

  /**
   * {@inheritdoc}
   */
  public function write($name, array $data) {
    foreach ($this->filters as $filter) {
      if (is_array($data)) {
        $data = $filter->filterWrite($name, $data);
      }
      else {
        // The filterWrite has an array type hint in the interface.
        $data = $filter->filterWrite($name, []);
        if ($data === []) {
          // If the filter didn't fill it with data then set it to FALSE.
          $data = FALSE;
        }
      }
    }

    if (is_array($data)) {
      return $this->storage->write($name, $data);
    }

    // The data has been unset, check if it should be deleted.
    if ($this->storage->exists($name)) {
      foreach ($this->filters as $filter) {
        if ($filter->filterWriteEmptyIsDelete($name)) {
          return $this->storage->delete($name);
        }
      }
    }

    // The data was not written, but it is not an error.
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function delete($name) {
    $success = TRUE;
    foreach ($this->filters as $filter) {
      $success = $filter->filterDelete($name, $success);
    }

    if ($success) {
      $success = $this->storage->delete($name);
    }

    return $success;
  }

  /**
   * {@inheritdoc}
   */
  public function rename($name, $new_name) {
    $success = TRUE;
    foreach ($this->filters as $filter) {
      $success = $filter->filterRename($name, $new_name, $success);
    }

    if ($success) {
      $success = $this->storage->rename($name, $new_name);
    }

    return $success;
  }

  /**
   * {@inheritdoc}
   */
  public function encode($data) {
    return $this->storage->encode($data);
  }

  /**
   * {@inheritdoc}
   */
  public function decode($raw) {
    return $this->storage->decode($raw);
  }

  /**
   * {@inheritdoc}
   */
  public function listAll($prefix = '') {
    $data = $this->storage->listAll($prefix);
    foreach ($this->filters as $filter) {
      $data = $filter->filterListAll($prefix, $data);
    }
    // Make sure that listAll does not return config names that don't exist.
    $data = array_filter($data, function ($name) {
      return $this->exists($name) && $this->read($name) !== FALSE;
    });
    sort($data);
    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function deleteAll($prefix = '') {
    $delete = TRUE;
    foreach ($this->filters as $filter) {
      $delete = $filter->filterDeleteAll($prefix, $delete);
    }

    if ($delete) {
      return $this->storage->deleteAll($prefix);
    }

    // The filters returned FALSE for $delete, so we delete the names
    // individually and allow filters to prevent deleting the config.
    foreach ($this->storage->listAll($prefix) as $name) {
      $this->delete($name);
    }

    // The filters wanted to prevent deleting all and were called to delete the
    // individual config name, is this a success? Let us say it is.
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function createCollection($collection) {
    $filters = [];
    foreach ($this->filters as $key => $filter) {
      $filter = $filter->filterCreateCollection($collection);
      if ($filter) {
        $filters[$key] = $filter;
      }
    }

    return new static($this->storage->createCollection($collection), $filters);
  }

  /**
   * {@inheritdoc}
   */
  public function getAllCollectionNames() {
    $collections = $this->storage->getAllCollectionNames();
    foreach ($this->filters as $filter) {
      $collections = $filter->filterGetAllCollectionNames($collections);
    }
    $collections = array_unique($collections);
    sort($collections);

    return $collections;
  }

  /**
   * {@inheritdoc}
   */
  public function getCollectionName() {
    $collection = $this->storage->getCollectionName();
    foreach ($this->filters as $filter) {
      $collection = $filter->filterGetCollectionName($collection);
    }

    return $collection;
  }

}
