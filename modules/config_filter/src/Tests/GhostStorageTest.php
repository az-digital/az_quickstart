<?php

namespace Drupal\config_filter\Tests;

use Drupal\Core\Config\StorageInterface;
use Drupal\config_filter\Config\GhostStorage;
use Prophecy\Argument;

/**
 * Tests GhostStorage operations.
 *
 * @group config_filter
 */
class GhostStorageTest extends ReadonlyStorageTest {

  /**
   * Override the storage decorating.
   *
   * @param \Drupal\Core\Config\StorageInterface $source
   *   The storage to decorate.
   *
   * @return \Drupal\config_filter\Config\GhostStorage
   *   The storage to test.
   */
  protected function getStorage(StorageInterface $source) {
    return new GhostStorage($source);
  }

  /**
   * Override the dataprovider for write methods.
   *
   * @dataProvider writeMethodsProvider
   */
  public function testWriteOperations($method, $arguments) {
    $source = $this->prophesize(StorageInterface::class);
    $source->$method(Argument::any())->shouldNotBeCalled();

    $storage = $this->getStorage($source->reveal());

    $actual = call_user_func_array([$storage, $method], $arguments);
    $this->assertTrue($actual);
  }

}
