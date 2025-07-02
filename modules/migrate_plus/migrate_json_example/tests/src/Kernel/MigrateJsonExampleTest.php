<?php

declare(strict_types = 1);

namespace Drupal\Tests\migrate_json_example\Kernel;

use Drupal\node\Entity\NodeType;
use Drupal\Tests\migrate_drupal\Kernel\MigrateDrupalTestBase;

/**
 * Tests migrate_json_example migrations.
 *
 * @group migrate_plus
 */
final class MigrateJsonExampleTest extends MigrateDrupalTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'file',
    'text',
    'menu_ui',
    'migrate_plus',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installConfig(['node']);
    $this->installEntitySchema('file');
    $this->installSchema('file', ['file_usage']);
    $this->installSchema('user', ['users_data']);

    // Install the module via installer to trigger hook_install.
    \Drupal::service('module_installer')->install(['migrate_json_example']);
    $this->installConfig(['migrate_json_example']);
  }

  /**
   * Tests the results of "migrate_json_example" migrations.
   */
  public function testMigrations(): void {
    $node_storage = \Drupal::entityTypeManager()->getStorage('node');
    $this->assertCount(0, $node_storage->loadMultiple());
    // Execute "product" migration from 'migrate_json_example' module.
    $this->executeMigration('product');
    $this->assertCount(2, $node_storage->loadMultiple());
  }

  /**
   * Tests whether the module can be uninstalled and installed again.
   *
   * Also, checks whether the example configs are removed after uninstall.
   */
  public function testModuleCleanup(): void {
    $test_node_type = 'product';
    // Prove that test content type existed before the uninstall process.
    $this->assertInstanceOf(NodeType::class, NodeType::load($test_node_type));
    \Drupal::service('module_installer')->uninstall(['migrate_json_example']);
    // Make sure the test content type was removed.
    $this->assertNull(NodeType::load($test_node_type));
    // Check whether test configuration files were removed.
    \Drupal::service('module_installer')->install(['migrate_json_example']);
  }

}
