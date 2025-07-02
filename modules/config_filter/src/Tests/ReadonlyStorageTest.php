<?php

namespace Drupal\config_filter\Tests;

use Drupal\Core\Config\StorageInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\config_filter\Config\ReadOnlyStorage;
use Drupal\config_filter\Exception\UnsupportedMethod;
use Prophecy\Argument;
use Prophecy\Prophecy\MethodProphecy;

/**
 * Tests ReadonlyStorage operations.
 *
 * @group config_filter
 */
class ReadonlyStorageTest extends UnitTestCase {

  /**
   * Wrap a given storage.
   *
   * This is useful when testing a subclass of ReadonlyStorage.
   *
   * @param \Drupal\Core\Config\StorageInterface $source
   *   The storage to decorate.
   *
   * @return \Drupal\Core\Config\StorageInterface
   *   The storage wrapping the source.
   */
  protected function getStorage(StorageInterface $source) {
    return new ReadOnlyStorage($source);
  }

  /**
   * Test methods that should be transparent.
   *
   * @dataProvider readMethodsProvider
   */
  public function testReadOperations($method, $arguments, $returnValue) {
    $source = $this->prophesize(StorageInterface::class);
    $methodProhecy = new MethodProphecy($source, $method, $arguments);
    $methodProhecy->shouldBeCalledTimes(1);
    $methodProhecy->willReturn($returnValue);
    $source->addMethodProphecy($methodProhecy);

    $storage = $this->getStorage($source->reveal());
    $actual = call_user_func_array([$storage, $method], $arguments);
    $this->assertEquals($actual, $returnValue);
  }

  /**
   * Provide the methods that should continue to work.
   *
   * @return array
   *   The data.
   */
  public static function readMethodsProvider() {
    $instance = new self("test");
    return [
      ['exists', [$instance->randomMachineName()], $instance->randomMachineName()],
      ['read', [$instance->randomMachineName()], $instance->randomArray()],
      ['readMultiple', [$instance->randomArray()], $instance->randomArray()],
      ['encode', [$instance->randomArray()], $instance->randomMachineName()],
      ['decode', [$instance->randomMachineName()], $instance->randomArray()],
      ['listAll', [$instance->randomMachineName()], $instance->randomArray()],
      ['getAllCollectionNames', [], $instance->randomArray()],
      ['getCollectionName', [], $instance->randomMachineName()],
    ];
  }

  /**
   * Test creating a collection.
   *
   * Creating collections returns a new instance, make sure it decorates the
   * new instance of the source.
   */
  public function testCreateCollection() {
    $name = $this->randomMachineName();
    $source = $this->prophesize(StorageInterface::class);
    $collectionSource = $this->prophesize(StorageInterface::class)->reveal();
    $source->createCollection($name)->willReturn($collectionSource);

    $storage = $this->getStorage($source->reveal());
    $collectionStorage = $storage->createCollection($name);

    $this->assertInstanceOf(ReadOnlyStorage::class, $collectionStorage);

    $readonlyReflection = new \ReflectionClass(ReadOnlyStorage::class);
    $storageProperty = $readonlyReflection->getProperty('storage');
    $storageProperty->setAccessible(TRUE);
    $actualSource = $storageProperty->getValue($collectionStorage);
    $this->assertEquals($collectionSource, $actualSource);
  }

  /**
   * Test the operations that should throw an error.
   *
   * @dataProvider writeMethodsProvider
   */
  public function testWriteOperations($method, $arguments) {
    $source = $this->prophesize(StorageInterface::class);
    $source->$method(Argument::any())->shouldNotBeCalled();

    $storage = $this->getStorage($source->reveal());
    try {
      call_user_func_array([$storage, $method], $arguments);
      $this->fail();
    }
    catch (UnsupportedMethod $exception) {
      $this->assertEquals(ReadOnlyStorage::class . '::' . $method . ' is not allowed on a ReadOnlyStorage', $exception->getMessage());
    }
  }

  /**
   * Provide the methods that should throw an exception.
   *
   * @return array
   *   The data
   */
  public static function writeMethodsProvider() {
    $instance = new self("test");
    return [
      ['write', [$instance->randomMachineName(), $instance->randomArray()]],
      ['delete', [$instance->randomMachineName()]],
      ['rename', [$instance->randomMachineName(), $instance->randomMachineName()]],
      ['deleteAll', [$instance->randomMachineName()]],
    ];
  }

  /**
   * Get a random array.
   *
   * @return array
   *   A random array used for data testing.
   */
  protected function randomArray() {
    return (array) $this->getRandomGenerator()->object();
  }

}
