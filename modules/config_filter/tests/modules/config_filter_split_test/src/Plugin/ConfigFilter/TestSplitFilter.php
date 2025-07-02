<?php

namespace Drupal\config_filter_split_test\Plugin\ConfigFilter;

use Drupal\Core\Config\DatabaseStorage;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\config_filter\Plugin\ConfigFilterBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a TestSplitFilter.
 *
 * This is a very basic split filter to test that config_filter applies the
 * filters correctly. For more advanced and configurable split filters use the
 * Configuration Split (config_split) module.
 *
 * @ConfigFilter(
 *   id = "config_filter_split_test",
 *   label = @Translation("Filter Split test"),
 *   storages = {"test_storage"},
 * )
 */
class TestSplitFilter extends ConfigFilterBase implements ContainerFactoryPluginInterface {

  /**
   * The File storage to read the migrations from.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $storage;

  /**
   * The name prefix to split.
   *
   * @var string
   */
  protected $name;

  /**
   * Constructs a new TestSplitFilter.
   *
   * @param \Drupal\Core\Config\StorageInterface $storage
   *   The migrate storage.
   * @param string $name
   *   The config name prefix to split.
   */
  public function __construct(StorageInterface $storage, $name) {
    parent::__construct([], 'config_filter_split_test', []);
    $this->storage = $storage;
    $this->name = $name;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(new DatabaseStorage($container->get('database'), 'config_filter_split_test'), 'core.');
  }

  /**
   * Decide to split the config off or not.
   *
   * @param string $name
   *   The name of the configuration to check.
   *
   * @return bool
   *   Whether the configuration is supposed to be split.
   */
  protected function isSplitConfig($name) {
    return (strpos($name, $this->name) === 0);
  }

  /**
   * {@inheritdoc}
   */
  public function filterRead($name, $data) {
    if ($this->isSplitConfig($name)) {
      if ($this->storage->exists($name)) {
        $data = $this->storage->read($name);
      }
    }

    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function filterExists($name, $exists) {
    if ($this->isSplitConfig($name) && !$exists) {
      $exists = $this->storage->exists($name);
    }

    return $exists;
  }

  /**
   * {@inheritdoc}
   */
  public function filterReadMultiple(array $names, array $data) {
    return array_merge($data, $this->storage->readMultiple($names));
  }

  /**
   * {@inheritdoc}
   */
  public function filterListAll($prefix, array $data) {
    return array_unique(array_merge($data, $this->storage->listAll($prefix)));
  }

  /**
   * {@inheritdoc}
   */
  public function filterWrite($name, array $data) {
    if ($this->isSplitConfig($name)) {
      $this->storage->write($name, $data);
      return NULL;
    }

    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function filterWriteEmptyIsDelete($name) {
    return ($this->isSplitConfig($name) ? TRUE : NULL);
  }

  /**
   * {@inheritdoc}
   */
  public function filterDelete($name, $delete) {
    if ($delete && $this->storage->exists($name)) {
      // Call delete on the secondary storage anyway.
      $this->storage->delete($name);
    }

    return $delete;
  }

  /**
   * {@inheritdoc}
   */
  public function filterDeleteAll($prefix, $delete) {
    if ($delete) {
      try {
        $this->storage->deleteAll($prefix);
      }
      catch (\UnexpectedValueException $exception) {
        // The file storage tries to remove directories of collections. But this
        // fails if the directory doesn't exist. So everything is actually fine.
      }
    }

    return $delete;
  }

  /**
   * {@inheritdoc}
   */
  public function filterCreateCollection($collection) {
    return new static($this->storage->createCollection($collection), $this->name);
  }

}
