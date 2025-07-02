<?php

namespace Drupal\Tests\migrate_example_advanced\Functional;

use Drupal\node\Entity\NodeType;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests migrate_example_advanced migrations.
 *
 * @group migrate_plus
 */
class MigrateExampleAdvancedTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'taxonomy',
    'path',
    'migrate_plus',
    'migrate_example_advanced',
    'migrate_example_advanced_setup',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests whether the module can be uninstalled and installed again.
   *
   * Also, checks whether the example configs are removed after uninstall.
   */
  public function testModuleCleanup(): void {
    $test_node_type = 'migrate_example_producer';
    // Prove that test content type existed before the uninstall process.
    $this->assertInstanceOf(NodeType::class, NodeType::load($test_node_type));
    \Drupal::service('module_installer')->uninstall(['migrate_example_advanced_setup']);
    // Make sure the test content type was removed.
    $this->assertNull(NodeType::load($test_node_type));
    // Check whether test configuration files were removed.
    \Drupal::service('module_installer')->install(['migrate_example_advanced_setup']);
  }

}
