<?php

namespace Drupal\Tests\config_snapshot\Kernel;

use Drupal\Core\Config\StorageInterface;
use Drupal\KernelTests\Core\Config\Storage\ConfigStorageTestBase;
use Drupal\config_snapshot\ConfigSnapshotStorage;
use Drupal\config_snapshot\Entity\ConfigSnapshot;

/**
 * Tests ConfigSnapshotStorage operations.
 *
 * @group config_snapshot
 */
class ConfigSnapshotStorageTest extends ConfigStorageTestBase {

  /**
   * Nested config items break schema checking.
   *
   * @var bool
   */
  protected $strictConfigSchema = FALSE;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['config_snapshot'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() : void {
    parent::setUp();
    $this->installEntitySchema('config_snapshot');
    $this->storage = new ConfigSnapshotStorage('example', 'module', 'example_module');

    // ::listAll() verifications require other configuration data to exist.
    $this->storage->write('system.performance', []);
  }

  /**
   * {@inheritdoc}
   */
  protected function read($name) {
    /** @var \Drupal\config_snapshot\Entity\ConfigSnapshot $config_snapshot */
    $config_snapshot = ConfigSnapshot::load('example.module.example_module');
    if ($item = $config_snapshot->getItem($this->storage->getCollectionName(), $name)) {
      return $item['data'];
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  protected function insert($name, $data) {
    /** @var \Drupal\config_snapshot\Entity\ConfigSnapshot $config_snapshot */
    $config_snapshot = ConfigSnapshot::load('example.module.example_module');
    $config_snapshot
      ->setItem($this->storage->getCollectionName(), $name, $data)
      ->save();

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  protected function update($name, $data) {
    /** @var \Drupal\config_snapshot\Entity\ConfigSnapshot $config_snapshot */
    $config_snapshot = ConfigSnapshot::load('example.module.example_module');
    $config_snapshot
      ->setItem($this->storage->getCollectionName(), $name, $data)
      ->save();

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  protected function delete($name) {
    /** @var \Drupal\config_snapshot\Entity\ConfigSnapshot $config_snapshot */
    $config_snapshot = ConfigSnapshot::load('example.module.example_module');
    // Check first if the name to delete exists.
    if ($this->storage->exists($name)) {
      $config_snapshot
        ->clearItem($this->storage->getCollectionName(), $name)
        ->save();

      return TRUE;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function testInvalidStorage(): void {
    $this->markTestSkipped('ConfigSnapshotStorage cannot be invalid.');
  }

  /**
   * Tests if new collections created correctly.
   *
   * @param string $collection
   *   The collection name.
   *
   * @dataProvider providerCollections
   */
  public function testCreateCollection($collection) {
    $initial_collection_name = $this->storage->getCollectionName();

    // Create new storage with given collection and check it is set correctly.
    $new_storage = $this->storage->createCollection($collection);
    $this->assertSame($collection, $new_storage->getCollectionName());

    // Check collection not changed in the current storage instance.
    $this->assertSame($initial_collection_name, $this->storage->getCollectionName());
  }

  /**
   * Data provider for testing different collections.
   *
   * @return array
   *   Returns an array of collection names.
   */
  public static function providerCollections() {
    return [
      [StorageInterface::DEFAULT_COLLECTION],
      ['foo.bar'],
    ];
  }

}
