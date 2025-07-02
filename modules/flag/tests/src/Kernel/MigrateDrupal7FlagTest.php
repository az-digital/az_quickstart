<?php

namespace Drupal\Tests\flag\Kernel;

use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;

/**
 * Tests migration of flags.
 *
 * @group flag
 */
class MigrateDrupal7FlagTest extends MigrateDrupal7TestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'node',
    'comment',
    'taxonomy',
    'text',
    'menu_ui',
    'flag',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $fixture = __DIR__ . '/../../fixtures/drupal7.php';
    $this->assertNotFalse(realpath($fixture));
    $this->loadFixture($fixture);

    $this->installEntitySchema('flag');
    $this->installEntitySchema('node_type');
    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installEntitySchema('comment_type');
    $this->installEntitySchema('comment');
    $this->installEntitySchema('taxonomy_term');
    $this->installEntitySchema('taxonomy_vocabulary');
    $this->installConfig(static::$modules);

    $this->executeMigration('d7_comment_type');
    $this->executeMigration('d7_node_type');
    $this->executeMigration('d7_taxonomy_vocabulary');
    $this->executeMigration('d7_flag');
  }

  /**
   * Asserts that flags have been migrated.
   */
  public function testMigrationResults() {
    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager */
    $entityTypeManager = $this->container->get('entity_type.manager');
    /** @var \Drupal\flag\Entity\Flag[] $flags */
    $flags = $entityTypeManager
      ->getStorage('flag')
      ->loadMultiple();

    $this->assertCount(4, $flags);
    // Comments.
    $this->assertEquals([
      'article',
      'comment_test_content_type',
    ], $flags['comment_flag']->getBundles());
    // Nodes.
    $this->assertEquals(['article', 'blog'], $flags['node_flag']->getBundles());
    // User.
    $this->assertEquals(['user'], $flags['user_flag']->getBundles());
    // Node global.
    $this->assertEquals(['article'], $flags['node_global_flag']->getBundles());
  }

}
