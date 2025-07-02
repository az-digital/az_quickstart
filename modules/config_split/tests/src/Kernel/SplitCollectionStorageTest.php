<?php

namespace Drupal\Tests\config_split\Kernel;

use Drupal\config_split\Config\SplitCollectionStorage;
use Drupal\Core\Config\NullStorage;
use Drupal\KernelTests\Core\Config\Storage\MemoryStorageTest;

/**
 * Test the SplitCollectionStorage.
 *
 * @group config_split
 */
class SplitCollectionStorageTest extends MemoryStorageTest {

  /**
   * The inner storage.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $inner;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Keep the inner storage handy.
    $this->inner = $this->storage;
    $this->storage = new SplitCollectionStorage($this->inner, 'graft');
    $this->storage->write('system.performance', []);
  }

  /**
   * Test that the inner storage contains the grafted data.
   */
  public function testGraft() {
    // Empty out all of the underlying storage.
    $this->copyConfig(new NullStorage(), $this->inner);
    // Add some data.
    $this->inner->write('inner', ['test data']);
    $this->inner->createCollection('collection')->write('collection', ['collection data']);

    $collection = $this->storage->createCollection('test');
    $collection->write('random', ['more']);
    // Copy all of what is in the inner one.
    $this->copyConfig($this->inner, $this->storage);

    self::assertEquals(['inner'], $this->storage->listAll());
    self::assertEquals(['collection'], $this->storage->getAllCollectionNames());
    self::assertEquals(['test data'], $this->storage->read('inner'));
    self::assertEquals(['collection data'], $this->storage->createCollection('collection')->read('collection'));

    self::assertEquals(['collection', 'split.graft', 'split.graft.collection'], $this->inner->getAllCollectionNames());
    $collection->write('graft', ['grafted']);
    self::assertEquals(['collection', 'split.graft', 'split.graft.collection', 'split.graft.test'], $this->inner->getAllCollectionNames());
    self::assertEquals(['grafted'], $this->inner->createCollection('split.graft.test')->read('graft'));
  }

  /**
   * Test two storages grafted onto the same one.
   */
  public function testSiblings() {
    $sibling = new SplitCollectionStorage($this->inner, 'sibling');

    // Empty out all of the underlying storage.
    $this->copyConfig(new NullStorage(), $this->inner);
    // Add some data.
    $this->inner->write('inner', ['test data']);
    $this->inner->createCollection('collection')->write('collection', ['collection data']);

    $this->copyConfig($this->inner, $this->storage);
    $this->copyConfig($this->inner, $sibling);
    $this->copyConfig($this->inner, $this->storage);

    self::assertEquals(['inner'], $this->storage->listAll());
    self::assertEquals(['collection'], $this->storage->getAllCollectionNames());
    self::assertEquals(['test data'], $this->storage->read('inner'));
    self::assertEquals(['collection data'], $this->storage->createCollection('collection')->read('collection'));

    self::assertEquals(['inner'], $sibling->listAll());
    self::assertEquals(['collection'], $sibling->getAllCollectionNames());
    self::assertEquals(['test data'], $sibling->read('inner'));
    self::assertEquals(['collection data'], $sibling->createCollection('collection')->read('collection'));

    self::assertEquals([
      'collection',
      'split.graft',
      'split.graft.collection',
      'split.sibling',
      'split.sibling.collection',
    ], $this->inner->getAllCollectionNames());
  }

  /**
   * Test grafting storages that use different prefixes but same concept.
   *
   * This test simulates using config_split together with the planned core
   * module config_environment. It will not use the same code but likely do its
   * transformations with the same effect.
   */
  public function testCousins() {
    $cousin = new TestSplitCollectionStorage($this->inner, 'cousin');

    // Empty out all of the underlying storage.
    $this->copyConfig(new NullStorage(), $this->inner);
    // Add some data.
    $this->inner->write('inner', ['test data']);
    $this->inner->createCollection('collection')->write('collection', ['collection data']);

    // Do a couple of copies to make sure all collections are created.
    $this->copyConfig($this->inner, $this->storage);
    $this->copyConfig($this->inner, $cousin);
    $this->copyConfig($this->inner, $this->storage);
    $this->copyConfig($this->inner, $cousin);

    self::assertEquals(['inner'], $this->storage->listAll());
    self::assertEquals([
      'collection',
      'test.cousin',
      'test.cousin.collection',
    ], $this->storage->getAllCollectionNames());
    self::assertEquals(['test data'], $this->storage->read('inner'));
    self::assertEquals(['collection data'], $this->storage->createCollection('collection')->read('collection'));

    self::assertEquals(['inner'], $cousin->listAll());
    self::assertEquals([
      'collection',
      'split.graft',
      'split.graft.collection',
    ], $cousin->getAllCollectionNames());
    self::assertEquals(['test data'], $cousin->read('inner'));
    self::assertEquals(['collection data'], $cousin->createCollection('collection')->read('collection'));

    $all = [
      'collection',
      'split.graft',
      'split.graft.collection',
      'split.graft.test.cousin',
      'split.graft.test.cousin.collection',
      'test.cousin',
      'test.cousin.collection',
      'test.cousin.split.graft',
      'test.cousin.split.graft.collection',
    ];
    self::assertEquals($all, $this->inner->getAllCollectionNames());

    // Further copying collections will not create new ones.
    $this->copyConfig($this->inner, $this->storage);
    $this->copyConfig($this->inner, $cousin);
    self::assertEquals($all, $this->inner->getAllCollectionNames());
  }

}

/**
 * A new storage with a different prefix but same grafting concept.
 */
class TestSplitCollectionStorage extends SplitCollectionStorage {
  const PREFIX = 'test.';

}
