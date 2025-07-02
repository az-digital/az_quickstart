<?php

declare(strict_types = 1);

namespace Drupal\Tests\migrate_plus\Kernel;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate_plus\Entity\Migration;
use Drupal\Tests\migrate\Kernel\MigrateTestBase;

/**
 * Test migration config entity discovery.
 *
 * @group migrate_plus
 */
final class MigrationConfigEntityTest extends MigrateTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'migrate',
    'migrate_plus',
    'migrate_plus_test',
    'taxonomy',
    'text',
    'system',
    'user',
  ];

  /**
   * The plugin manager.
   *
   * @var \Drupal\migrate\Plugin\MigrationPluginManager
   */
  protected $pluginManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->pluginManager = \Drupal::service('plugin.manager.migration');
    $this->installConfig('migrate_plus');
    $this->installEntitySchema('taxonomy_term');
  }

  /**
   * Tests cache invalidation.
   */
  public function testCacheInvalidation(): void {
    $config = Migration::create([
      'id' => 'test',
      'status' => TRUE,
      'label' => 'Label A',
      'migration_tags' => [],
      'source' => [],
      'destination' => [],
      'migration_dependencies' => [],
    ]);
    $config->save();

    $this->assertNotEmpty($this->pluginManager->getDefinition('test'));
    $this->assertSame('Label A', $this->pluginManager->getDefinition('test')['label']);

    $config->set('label', 'Label B');
    $config->save();

    $this->assertSame('Label B', $this->pluginManager->getDefinition('test')['label']);
  }

  /**
   * Tests migration status.
   */
  public function testMigrationStatus(): void {
    $configs = [
      [
        'id' => 'test_active',
        'status' => TRUE,
        'label' => 'Label Active',
        'migration_tags' => [],
        'source' => [],
        'destination' => [],
        'migration_dependencies' => [],
      ],
      [
        'id' => 'test_inactive',
        'status' => FALSE,
        'label' => 'Label Inactive',
        'migration_tags' => [],
        'source' => [],
        'destination' => [],
        'migration_dependencies' => [],
      ],
    ];

    foreach ($configs as $config) {
      Migration::create($config)->save();
    }

    $definitions = $this->pluginManager->getDefinitions();
    $this->assertCount(1, $definitions);
    $this->assertArrayHasKey('test_active', $definitions);

    $this->expectException(PluginNotFoundException::class);
    $this->expectExceptionMessage('The "test_inactive" plugin does not exist.');
    $this->pluginManager->getDefinition('test_inactive');
  }

  /**
   * Tests migration from configuration.
   */
  public function testImport(): void {
    $this->installConfig('migrate_plus_test');
    /** @var \Drupal\migrate\Plugin\MigrationInterface $migration */
    $migration = $this->pluginManager->createInstance('fruit_terms');
    $id_map = $migration->getIdMap();
    $executable = new MigrateExecutable($migration, $this);
    $executable->import();
    $this->assertSame(3, $id_map->importedCount());
  }

}
