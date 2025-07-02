<?php

declare(strict_types = 1);

namespace Drupal\Tests\migrate_tools\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\migrate\Plugin\MigrationInterface;

/**
 * Tests merging shared configuration.
 *
 * @group migrate_tools
 */
final class MigrateSharedConfigTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'migrate_tools',
    'migrate_shared_config_test',
    'migrate',
  ];

  /**
   * Tests including shared configuration.
   */
  public function testInclude(): void {
    $plugin_manager = $this->container->get('plugin.manager.migration');

    // Validate a single include with not conflicts.
    $migration = $plugin_manager->createInstance('test_stub_migration');
    $this->assertInstanceOf(MigrationInterface::class, $migration);
    $expected_source_configuration = [
      'batch_size' => 2,
      'plugin' => 'embedded_data',
      'data_rows' => [
        ['label' => 'foo'],
        ['label' => 'bar'],
        ['label' => 'baz'],
      ],
      'ids' => ['label' => ['type' => 'string']],
    ];
    $this->assertEquals($expected_source_configuration, $migration->getSourceConfiguration());

    // Validate multiple includes.
    $migration = $plugin_manager->createInstance('test_stub_multiple_includes_migration');
    $this->assertInstanceOf(MigrationInterface::class, $migration);
    $expected_destination_configuration = [
      'batch_size' => 2,
      'plugin' => 'entity:entity_test',
      'my_single_file_default_configuration' => 'value',
    ];
    $this->assertEquals($expected_source_configuration, $migration->getSourceConfiguration());
    $this->assertEquals($expected_destination_configuration, $migration->getDestinationConfiguration());

    // Validate with conflicts.
    $migration = $plugin_manager->createInstance('test_stub_conflicts_migration');
    $this->assertInstanceOf(MigrationInterface::class, $migration);
    $expected_source_configuration['batch_size'] = 1000;
    $this->assertEquals($expected_source_configuration, $migration->getSourceConfiguration());
  }

}
