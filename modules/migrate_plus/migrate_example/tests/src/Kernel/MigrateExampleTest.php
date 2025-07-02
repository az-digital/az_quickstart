<?php

declare(strict_types = 1);

namespace Drupal\Tests\migrate_example\Kernel;

use Drupal\node\Entity\NodeType;
use Drupal\Tests\migrate_drupal\Kernel\MigrateDrupalTestBase;

/**
 * Tests migrate_example migrations.
 *
 * @group migrate_plus
 */
final class MigrateExampleTest extends MigrateDrupalTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'taxonomy',
    'comment',
    'text',
    'migrate_plus',
    'migrate_example',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installConfig([
      'node',
      'comment',
      'migrate_example',
    ]);

    $this->installSchema('comment', ['comment_entity_statistics']);
    $this->installSchema('node', ['node_access']);
    $this->installSchema('user', ['users_data']);

    // Install the module via installer to trigger hook_install.
    \Drupal::service('module_installer')->install(['migrate_example_setup']);
    $this->installConfig(['migrate_example_setup']);

    $this->startCollectingMessages();

    // Execute "beer" migrations from 'migrate_example' module.
    $this->executeMigration('beer_user');
    $this->executeMigrations([
      'beer_term',
      'beer_node',
      'beer_comment',
    ]);
  }

  /**
   * Tests the results of "Beer" example migration.
   */
  public function testBeerMigration(): void {
    $users = \Drupal::entityTypeManager()->getStorage('user')->loadMultiple();
    $this->assertCount(4, $users);

    $terms = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->loadMultiple();
    $this->assertCount(3, $terms);

    $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple();
    $this->assertCount(3, $nodes);

    $comments = \Drupal::entityTypeManager()->getStorage('comment')->loadMultiple();
    $this->assertCount(5, $comments);
  }

  /**
   * Tests whether the module can be uninstalled and installed again.
   *
   * Also, checks whether the example configs are removed after uninstall.
   */
  public function testModuleCleanup(): void {
    $test_node_type = 'migrate_example_beer';
    // Prove that test content type existed before the uninstall process.
    $this->assertInstanceOf(NodeType::class, NodeType::load($test_node_type));
    \Drupal::service('module_installer')->uninstall(['migrate_example_setup']);
    // Make sure the test content type was removed.
    $this->assertNull(NodeType::load($test_node_type));
    // Check whether the module can be installed again.
    \Drupal::service('module_installer')->install(['migrate_example_setup']);
  }

}
