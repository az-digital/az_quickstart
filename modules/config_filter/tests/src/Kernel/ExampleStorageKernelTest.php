<?php

namespace Drupal\Tests\config_filter\Kernel;

use Drupal\Core\Config\MemoryStorage;
use Drupal\KernelTests\KernelTestBase;

/**
 * Example class to test the export and import with the test trait.
 *
 * @group config_filter_example
 */
class ExampleStorageKernelTest extends KernelTestBase {

  use ConfigStorageTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'config_filter',
    'config_filter_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // Install the system config.
    $this->installConfig(['system']);

    // Set the site name and slogan.
    $this->config('system.site')->set('name', 'Config Test')->set('slogan', 'Testing is fun!')->save();
  }

  /**
   * Example to test export.
   */
  public function testExampleExport() {
    // Set up expectation for export.
    $expectedExport = new MemoryStorage();
    $this->copyConfig($this->getActiveStorage(), $expectedExport);

    // Simulate the filter.
    $system_site = $expectedExport->read('system.site');
    // Exporting means triggering the write filter methods.
    $system_site['slogan'] = 'Testing is fun! Arrr';
    $expectedExport->write('system.site', $system_site);

    // Do assertions.
    static::assertStorageEquals($expectedExport, $this->getExportStorage());
  }

  /**
   * Example to test import.
   */
  public function testExampleImport() {
    // Write active config to file system as is.
    // This is not a config export, but for the sake of the test we set up
    // the sync storage to contain the same content as the active config.
    $this->copyConfig($this->getActiveStorage(), $this->getSyncFileStorage());

    // Set up expectation for import.
    $expectedImport = new MemoryStorage();
    $this->copyConfig($this->getSyncFileStorage(), $expectedImport);

    // Simulate the filter.
    $system_site = $expectedImport->read('system.site');
    // Importing means triggering the read filter methods.
    $system_site['name'] = 'Config Test Arrr';
    $expectedImport->write('system.site', $system_site);

    // Do assertions.
    static::assertStorageEquals($expectedImport, $this->getImportStorage());
  }

}
