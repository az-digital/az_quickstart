<?php

namespace Drupal\Tests\migmag\Functional;

use Drupal\Core\Extension\ModuleInstallerInterface;

/**
 * Tests MigMagExportTrait with Drupal 7 source and with a known, broken result.
 *
 * @group migmag
 */
class MigMagExportTraitBrokenMigrationTest extends MigMagExportTraitProceduralTest {

  /**
   * {@inheritdoc}
   */
  protected function assertFollowUpMigrationResults(): void {}

  /**
   * {@inheritdoc}
   */
  protected function assertUpgrade(array $entity_counts) {
    $this->assertSession()->pageTextContains('Congratulations, you upgraded Drupal!');
    // Reset all the statics after migration to ensure entities are loadable.
    $this->resetAll();
  }

  /**
   * {@inheritdoc}
   *
   * Drupal core 8.9.x verifies migration results in a less structured and
   * customizable way than 9.1+.
   */
  protected function assertMigrationResults(array $expected_counts, $version) {}

  // @codingStandardsIgnoreStart
  /**
   * {@inheritdoc}
   *
   * Drupal core 8.9.x dev uses phpunit/phpunit with version constraint
   * "^6.5 || ^7". PHPUnit 6|7 aren't able to execute test based on the declared
   * dependencies if a method's dependencies are inherited. This is why we have
   * to repeat this test method.
   */
  public function testDrupal7MigrationInitial() {
    parent::testDrupal7MigrationInitial();
  }
  // @codingStandardsIgnoreEnd

  /**
   * Executes the test of Drupal 7 migration and compares output with the prev.
   *
   * @depends testDrupal7MigrationInitial
   */
  public function testDrupal7MigrationAgainAndCompare() {
    $module_installer = \Drupal::service('module_installer');
    assert($module_installer instanceof ModuleInstallerInterface);
    $module_installer->install(['migmag_missing_plugins']);
    $this->resetAll();

    $this->setNumberOfExpectedLoggedErrors(28);

    $this->executeDrupal7Migration();
    $this->createActualExport();

    // The test node won't have "phone" fields and values.
    $base_list = array_diff($this->getBaseExportAssets(), ['node' => 'node']);
    $actual_list = array_diff($this->getActualExportAssets(), ['node' => 'node']);
    $this->assertEquals($actual_list, $base_list);
    foreach ($base_list as $entity_type) {
      $this->compareEntityInstances($entity_type);
    }

    $actual_node_types = $this->getActualExportAssets('node');
    $base_node_types = $this->getBaseExportAssets('node');
    $this->assertEquals($actual_node_types, $base_node_types);

    foreach ($base_node_types as $type) {
      if ($type !== 'test_content_type') {
        $this->compareEntityInstancesWithBundle('node', $type);
      }
    }

    $actual_nodes = $this->getActualExportAssets('node', 'test_content_type');
    $base_nodes = $this->getBaseExportAssets('node', 'test_content_type');
    $this->assertCount(1, $actual_nodes);
    $this->assertCount(1, $base_nodes);

    $asset_filename = 'node-1.json';
    $this->assertArrayHasKey($asset_filename, $actual_nodes);
    $this->assertArrayHasKey($asset_filename, $base_nodes);
    $this->assertEquals(
      '99-99-99-99',
      $base_nodes[$asset_filename][0]['field_phone']
    );
    // The test module "migmag_missing_plugins" removed the field migration
    // plugin of telephone fields.
    $this->assertArrayNotHasKey(
      'field_phone',
      $actual_nodes[$asset_filename][0]
    );
    unset($base_nodes[$asset_filename][0]['field_phone']);
    $this->compareEntityContent($base_nodes[$asset_filename], $actual_nodes[$asset_filename], $asset_filename);

    $this->removeTempBaseExportModule();
  }

}
