<?php

namespace Drupal\Tests\migmag_process\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\migmag_process\MigMagMigrateStub;
use Drupal\migmag_process\Plugin\migrate\process\MigMagLookup;
use Drupal\migrate\Plugin\MigratePluginManagerInterface;
use Drupal\migrate\Plugin\MigrationInterface;

/**
 * Tests the installation of Migrate Magician Lookup.
 *
 * @group migmag_process
 */
class MigMagLookupInstallTest extends KernelTestBase {

  /**
   * Tests installation of Migrate Magician Process without Migrate module.
   */
  public function testInstallWithoutMigrate() {
    $this->enableModules([
      'migmag_process',
    ]);

    // The 'migmag_process.lookup.stub' service shouldn't be registered.
    $this->assertFalse($this->container->has('migmag_process.lookup.stub'));
  }

  /**
   * Tests installation of Migrate Magician Lookup with Migrate module.
   */
  public function testInstallWithMigrate() {
    $this->enableModules([
      'migrate',
      'migmag_process',
    ]);

    // The 'migmag_lookup.stub' service should be available.
    $this->assertTrue($this->container->has('migmag_process.lookup.stub'));
    $this->assertInstanceOf(MigMagMigrateStub::class, $this->container->get('migmag_process.lookup.stub'));

    // The 'migmag_lookup' process plugin should also be available.
    $process_plugin_manager = $this->container->get('plugin.manager.migrate.process');
    assert($process_plugin_manager instanceof MigratePluginManagerInterface);
    $migration_prophecy = $this->prophesize(MigrationInterface::class);
    $migmag_lookup_plugin = $process_plugin_manager->createInstance(
      'migmag_lookup',
      [],
      $migration_prophecy->reveal()
    );
    $this->assertInstanceOf(MigMagLookup::class, $migmag_lookup_plugin);
  }

}
