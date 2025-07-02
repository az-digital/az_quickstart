<?php

namespace Drupal\config_filter\Tests;

use Drupal\Core\Config\MemoryStorage;
use Drupal\Core\Config\StorageInterface;
use Drupal\KernelTests\Core\Config\Storage\MemoryStorageTest;
use Drupal\config_filter\Config\FilteredStorage;
use Drupal\config_filter\Config\FilteredStorageInterface;
use Drupal\config_filter\Config\ReadOnlyStorage;
use Drupal\config_filter\Config\StorageFilterInterface;
use Drupal\config_filter\Exception\InvalidStorageFilterException;
use Prophecy\Argument;

/**
 * Tests StorageWrapper operations using the CachedStorage.
 *
 * @group config_filter
 */
class FilteredStorageTest extends MemoryStorageTest {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // The storage is a wrapper with a transparent filter.
    // So all inherited tests should still pass.
    $this->storage = new FilteredStorage($this->storage, [new TransparentFilter()]);
  }

  /**
   * Test that the storage is set on the filters.
   */
  public function testSettingStorages() {
    /** @var \Drupal\config_filter\Tests\TransparentFilter[] $filters */
    $filters = static::getProtectedFilters($this->storage);
    foreach ($filters as $filter) {
      // Test that the source storage is a ReadonlyStorage and wraps the cached
      // storage from the inherited test.
      $readonly = $filter->getPrivateSourceStorage();
      $this->assertInstanceOf(ReadOnlyStorage::class, $readonly);
      $readonlyReflection = new \ReflectionClass(ReadOnlyStorage::class);
      $storageProperty = $readonlyReflection->getProperty('storage');
      $storageProperty->setAccessible(TRUE);
      $source = $storageProperty->getValue($readonly);
      $this->assertInstanceOf(MemoryStorage::class, $source);

      // Assert that the filter gets the storage.
      $this->assertEquals($this->storage, $filter->getPrivateFilteredStorage());
    }
  }

  /**
   * Test that creating collections keeps filters set to the correct storages.
   */
  public function testCollectionStorages() {
    $collection = $this->randomString();

    // The storage is in its default state.
    $this->assertEquals(StorageInterface::DEFAULT_COLLECTION, $this->storage->getCollectionName());

    /** @var \Drupal\config_filter\Tests\TransparentFilter[] $filters */
    $filters = static::getProtectedFilters($this->storage);
    foreach ($filters as $filter) {
      // Test that the filters have the correct storage set.
      $this->assertEquals($this->storage, $filter->getPrivateFilteredStorage());
      $this->assertEquals(StorageInterface::DEFAULT_COLLECTION, $filter->getPrivateSourceStorage()->getCollectionName());
    }

    // Create a collection which creates a clone of the storage and filters.
    $collectionStorage = $this->storage->createCollection($collection);
    $this->assertInstanceOf(FilteredStorageInterface::class, $collectionStorage);

    /** @var \Drupal\config_filter\Tests\TransparentFilter[] $collectionFilters */
    $collectionFilters = static::getProtectedFilters($collectionStorage);
    foreach ($collectionFilters as $filter) {
      // Test that the cloned filter has the correct storage set.
      $this->assertEquals($collectionStorage, $filter->getPrivateFilteredStorage());
      $this->assertEquals($collection, $filter->getPrivateSourceStorage()->getCollectionName());
    }

    /** @var \Drupal\config_filter\Tests\TransparentFilter[] $filters */
    $filters = static::getProtectedFilters($this->storage);
    foreach ($filters as $filter) {
      // Test that the filters on the original storage are still correctly set.
      $this->assertEquals($this->storage, $filter->getPrivateFilteredStorage());
      $this->assertEquals(StorageInterface::DEFAULT_COLLECTION, $filter->getPrivateSourceStorage()->getCollectionName());
    }
  }

  /**
   * Test setting up filters in FilteredStorage::createCollection().
   */
  public function testCreateCollectionFilter() {
    $collection = $this->randomString();
    $filteredCollection = $this->randomString();

    $filter = $this->prophesizeFilter();
    $filterC = $this->prophesizeFilter();
    $filterC->filterGetCollectionName($collection)->willReturn($filteredCollection);
    $filter->filterCreateCollection($collection)->willReturn($filterC->reveal());

    $source = $this->prophesize(StorageInterface::class);
    $sourceC = $this->prophesize(StorageInterface::class);
    $sourceC->getCollectionName()->willReturn($collection);
    $source->createCollection($collection)->willReturn($sourceC->reveal());

    $storage = new FilteredStorage($source->reveal(), [$filter->reveal()]);
    // Creating a collection makes sure the filters were correctly set up.
    $storageC = $storage->createCollection($collection);

    // Test that the collection is filtered in the collection storage.
    $this->assertEquals($filteredCollection, $storageC->getCollectionName());
  }

  /**
   * Test collection names from FilteredStorage::getAllCollectionNames().
   */
  public function testGetAllCollectionNamesFilter() {
    $source = $this->prophesize(StorageInterface::class);
    $source->getAllCollectionNames()->willReturn(['a', 'b']);

    $filter = $this->prophesizeFilter();
    $filter->filterGetAllCollectionNames(['a', 'b'])->willReturn(['b', 'b', 'c']);

    $storage = new FilteredStorage($source->reveal(), [$filter->reveal()]);

    $this->assertEquals(['b', 'c'], $storage->getAllCollectionNames());
  }

  /**
   * Test the read methods invokes the correct filter methods.
   *
   * @dataProvider readFilterProvider
   */
  public function testReadFilter($name, $storageMethod, $filterMethod, $data, $expected) {
    $source = $this->prophesize(StorageInterface::class);
    $filterA = $this->prophesizeFilter();
    $filterB = $this->prophesizeFilter();

    $source->$storageMethod($name)->willReturn($data);
    $interim = $this->randomArray();
    $filterA->$filterMethod($name, $data)->willReturn($interim);
    $filterB->$filterMethod($name, $interim)->willReturn($expected);

    $storage = new FilteredStorage($source->reveal(), [$filterA->reveal(), $filterB->reveal()]);
    $this->assertEquals($expected, $storage->$storageMethod($name));
  }

  /**
   * Data provider for testReadFilter.
   */
  public static function readFilterProvider() {
    $instance = new self("test");
    // @codingStandardsIgnoreStart
    return [
      [$instance->randomString(), 'exists', 'filterExists', TRUE, TRUE],
      [$instance->randomString(), 'exists', 'filterExists', TRUE, FALSE],
      [$instance->randomString(), 'exists', 'filterExists', FALSE, TRUE],
      [$instance->randomString(), 'exists', 'filterExists', FALSE, FALSE],

      [$instance->randomString(), 'read', 'filterRead', $instance->randomArray(), $instance->randomArray()],
      [$instance->randomString(), 'read', 'filterRead', NULL, $instance->randomArray()],
      [$instance->randomString(), 'read', 'filterRead', $instance->randomArray(), NULL],

      [
        [$instance->randomString(), $instance->randomString()],
        'readMultiple',
        'filterReadMultiple',
        [$instance->randomArray(), $instance->randomArray()],
        [$instance->randomArray(), $instance->randomArray()],
      ],
      [
        [$instance->randomString(), $instance->randomString()],
        'readMultiple',
        'filterReadMultiple',
        [$instance->randomArray(), FALSE],
        [$instance->randomArray(), $instance->randomArray()],
      ],
    ];
    // @codingStandardsIgnoreEnd
  }

  /**
   * Test that when a filter removes config on a readMultiple it is not set.
   */
  public function testReadMultipleWithEmptyResults() {
    $names = [$this->randomString(), $this->randomString()];
    $source = $this->prophesize(StorageInterface::class);
    $data = [$this->randomArray(), $this->randomArray()];
    $source->readMultiple($names)->willReturn($data);
    $source = $source->reveal();

    foreach ([0, [], NULL] as $none) {
      $filtered = $data;
      $filtered[1] = $none;
      $filter = $this->prophesizeFilter();
      $filter->filterReadMultiple($names, $data)->willReturn($filtered);

      $storage = new FilteredStorage($source, [$filter->reveal()]);
      $this->assertEquals([$data[0]], $storage->readMultiple($names));
    }
  }

  /**
   * Test the write method invokes the filterWrite in filters.
   *
   * @dataProvider writeFilterProvider
   */
  public function testWriteFilter($interim, $expected, $exists = TRUE) {
    $name = $this->randomString();
    $data = $this->randomArray();
    $source = $this->prophesize(StorageInterface::class);
    $filterA = $this->prophesizeFilter();
    $filterB = $this->prophesizeFilter();

    $filterA->filterWrite($name, $data)->willReturn($interim);
    $interim = is_array($interim) ? $interim : [];
    $filterB->filterWrite($name, $interim)->willReturn($expected);

    if (is_array($expected)) {
      $source->write($name, $expected)->willReturn(TRUE);
    }
    else {
      $source->write(Argument::any())->shouldNotBeCalled();
      $source->exists($name)->willReturn($exists);
      if ($exists) {
        $filterA->filterWriteEmptyIsDelete($name)->willReturn(TRUE);
        $source->delete($name)->willReturn(TRUE);
      }
    }

    $storage = new FilteredStorage($source->reveal(), [$filterA->reveal(), $filterB->reveal()]);
    $this->assertTrue($storage->write($name, $data));
  }

  /**
   * Data provider for testWriteFilter.
   */
  public static function writeFilterProvider() {
    $instance = new self("test");
    return [
      [$instance->randomArray(), $instance->randomArray()],
      [NULL, $instance->randomArray()],
      [[], $instance->randomArray()],
      [$instance->randomArray(), []],
      [$instance->randomArray(), NULL, FALSE],
      [$instance->randomArray(), FALSE, FALSE],
      [$instance->randomArray(), NULL, TRUE],
    ];
  }

  /**
   * Test the write method invokes the filterWrite in filters.
   */
  public function testWriteFilterDeleting() {
    $name = $this->randomString();
    $data = $this->randomArray();
    $source = $this->prophesize(StorageInterface::class);
    $filterA = $this->prophesizeFilter();
    $filterB = new TransparentFilter();

    $filterA->filterWrite($name, $data)->willReturn(FALSE);

    $source->write(Argument::any())->shouldNotBeCalled();
    $source->exists($name)->willReturn(TRUE);

    $filterA->filterWriteEmptyIsDelete($name)->willReturn(TRUE);
    $source->delete($name)->willReturn(TRUE);

    $storage = new FilteredStorage($source->reveal(), [$filterA->reveal(), $filterB]);
    $this->assertTrue($storage->write($name, $data));
  }

  /**
   * Test the delete method invokes the filterDelete in filters.
   *
   * @dataProvider deleteFilterProvider
   */
  public function testDeleteFilter($interim, $expected) {
    $name = $this->randomString();
    $source = $this->prophesize(StorageInterface::class);
    $filterA = $this->prophesizeFilter();
    $filterB = $this->prophesizeFilter();

    $filterA->filterDelete($name, TRUE)->willReturn($interim);
    $filterB->filterDelete($name, $interim)->willReturn($expected);

    if ($expected) {
      $source->delete($name)->willReturn(TRUE);
    }
    else {
      $source->delete(Argument::any())->shouldNotBeCalled();
    }

    $storage = new FilteredStorage($source->reveal(), [$filterA->reveal(), $filterB->reveal()]);
    $this->assertEquals($expected, $storage->delete($name));
  }

  /**
   * Data provider for testDeleteFilter.
   */
  public static function deleteFilterProvider() {
    return [
      [TRUE, TRUE],
      [FALSE, TRUE],
      [TRUE, FALSE],
      [FALSE, FALSE],
    ];
  }

  /**
   * Test the rename method invokes the filterRename in filters.
   *
   * @dataProvider renameFilterProvider
   */
  public function testRenameFilter($interim, $expected) {
    $name = $this->randomString();
    $name2 = $this->randomString();
    $source = $this->prophesize(StorageInterface::class);
    $filterA = $this->prophesizeFilter();
    $filterB = $this->prophesizeFilter();

    $filterA->filterRename($name, $name2, TRUE)->willReturn($interim);
    $filterB->filterRename($name, $name2, $interim)->willReturn($expected);

    if ($expected) {
      $source->rename($name, $name2)->willReturn(TRUE);
    }
    else {
      $source->rename(Argument::any())->shouldNotBeCalled();
    }

    $storage = new FilteredStorage($source->reveal(), [$filterA->reveal(), $filterB->reveal()]);
    $this->assertEquals($expected, $storage->rename($name, $name2));
  }

  /**
   * Data provider for testRenameFilter.
   */
  public static function renameFilterProvider() {
    return [
      [TRUE, TRUE],
      [FALSE, TRUE],
      [TRUE, FALSE],
      [FALSE, FALSE],
    ];
  }

  /**
   * Test the deleteAll method invokes the filterDeleteAll in filters.
   *
   * @dataProvider deleteAllFilterProvider
   */
  public function testDeleteAllFilter($interim, $expected) {
    $name = $this->randomString();
    $source = $this->prophesize(StorageInterface::class);
    $filterA = $this->prophesizeFilter();
    $filterB = $this->prophesizeFilter();

    $filterA->filterDeleteAll($name, TRUE)->willReturn($interim);
    $filterB->filterDeleteAll($name, $interim)->willReturn($expected);

    if ($expected) {
      $source->deleteAll($name)->willReturn(TRUE);
    }
    else {
      $source->deleteAll(Argument::any())->shouldNotBeCalled();
      $all = [$this->randomString(), $this->randomString()];
      $source->listAll($name)->willReturn($all);

      foreach ($all as $item) {
        $filterA->filterDelete($item, TRUE)->willReturn(TRUE);
        $filterB->filterDelete($item, TRUE)->willReturn(FALSE);
      }
    }

    $storage = new FilteredStorage($source->reveal(), [$filterA->reveal(), $filterB->reveal()]);
    $this->assertTrue($storage->deleteAll($name));
  }

  /**
   * Data provider for testDeleteAllFilter.
   */
  public static function deleteAllFilterProvider() {
    return [
      [TRUE, TRUE],
      [FALSE, TRUE],
      [TRUE, FALSE],
      [FALSE, FALSE],
    ];
  }

  /**
   * Test that an exception is thrown when invalid arguments are passed.
   */
  public function testInvalidStorageFilterArgument() {
    $source = $this->prophesize(StorageInterface::class);

    // We would do this with $this->expectException but alas drupal is stuck on
    // phpunit 4 and we try not to add deprecated code.
    try {
      // @phpstan-ignore-next-line Wrong arguments is what we test here.
      new FilteredStorage($source->reveal(), [new \stdClass()]);
      $this->fail('An exception should have been thrown.');
    }
    catch (InvalidStorageFilterException $exception) {
      $this->assertTrue(TRUE);
    }
  }

  /**
   * Prophesize a StorageFilter.
   */
  protected function prophesizeFilter() {
    $filter = $this->prophesize(StorageFilterInterface::class);
    $filter->setSourceStorage(Argument::type(ReadOnlyStorage::class))->shouldBeCalledTimes(1);
    $filter->setFilteredStorage(Argument::type(FilteredStorage::class))->shouldBeCalledTimes(1);
    return $filter;
  }

  /**
   * Get the filters from a FilteredStorageInterface.
   *
   * @param \Drupal\Core\Config\StorageInterface $storage
   *   The storage with the protected filters property.
   *
   * @return \Drupal\config_filter\Config\StorageFilterInterface[]
   *   The array of filters.
   */
  protected static function getProtectedFilters(StorageInterface $storage) {
    $filterReflection = new \ReflectionClass(FilteredStorage::class);
    $filtersProperty = $filterReflection->getProperty('filters');
    $filtersProperty->setAccessible(TRUE);

    return $filtersProperty->getValue($storage);
  }

  /**
   * Create a random array.
   */
  protected function randomArray($size = 4) {
    return (array) $this->randomObject($size);
  }

}
