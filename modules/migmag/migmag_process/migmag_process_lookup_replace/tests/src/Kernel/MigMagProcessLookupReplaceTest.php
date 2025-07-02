<?php

namespace Drupal\Tests\migmag_process_lookup_replace\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\migmag_process\Plugin\migrate\process\MigMagLookup;
use Drupal\migrate\Plugin\MigratePluginManagerInterface;
use Drupal\migrate\Plugin\MigrationInterface;

/**
 * Tests the installation of Migrate Magician Forced Lookup Replace.
 *
 * @group migmag_process_lookup_replace
 */
class MigMagProcessLookupReplaceTest extends KernelTestBase {

  /**
   * Tests the installation of Migrate Magician Forced Lookup Replace.
   */
  public function testInstallWithMigrate() {
    $this->enableModules([
      'migmag_process',
      'migmag_process_lookup_replace',
      'migrate',
    ]);

    // The 'migmag_lookup' process plugin should be available.
    $process_plugin_manager = $this->container->get('plugin.manager.migrate.process');
    assert($process_plugin_manager instanceof MigratePluginManagerInterface);
    $migration_prophecy = $this->prophesize(MigrationInterface::class);
    $migmag_lookup_plugin = $process_plugin_manager->createInstance(
      'migmag_lookup',
      [],
      $migration_prophecy->reveal()
    );
    $this->assertInstanceOf(MigMagLookup::class, $migmag_lookup_plugin);

    // The 'migration_lookup' process plugin's class should be MigMagLookup's
    // class.
    $migration_lookup_plugin = $process_plugin_manager->createInstance(
      'migration_lookup',
      [],
      $migration_prophecy->reveal()
    );
    $this->assertInstanceOf(get_class($migmag_lookup_plugin), $migration_lookup_plugin);
  }

}
