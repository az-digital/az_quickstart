<?php

namespace Drupal\Tests\flag\Kernel;

use Drupal\Tests\migrate_drupal\Kernel\d7\MigrateDrupal7TestBase;

/**
 * Tests migration of flagging data.
 *
 * @group flag
 */
class MigrateDrupal7FlaggingTest extends MigrateDrupal7TestBase {

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
    'filter',
    'menu_ui',
    'phpass',
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

    $this->installSchema('comment', ['comment_entity_statistics']);
    $this->installSchema('flag', ['flag_counts']);
    $this->installEntitySchema('flag');
    $this->installEntitySchema('flagging');
    $this->installEntitySchema('node_type');
    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installEntitySchema('comment_type');
    $this->installEntitySchema('comment');
    $this->installEntitySchema('taxonomy_term');
    $this->installEntitySchema('taxonomy_vocabulary');
    $this->installConfig(static::$modules);

    $this->executeMigration('d7_filter_format');
    $this->executeMigration('d7_user_role');
    $this->executeMigration('d7_node_settings');
    $this->executeMigration('d7_node_type');
    $this->executeMigration('d7_user');
    $this->executeMigration('d7_comment_type');
    $this->executeMigration('d7_comment_field');
    $this->executeMigration('d7_comment_field_instance');
    $this->executeMigration('d7_comment_entity_display');
    $this->executeMigration('d7_comment_entity_form_display');
    $this->executeMigration('d7_taxonomy_vocabulary');
    $this->executeMigration('d7_node');
    $this->executeMigration('d7_node:page');
    $this->executeMigration('d7_node:article');
    $this->executeMigration('d7_node:blog');
    $this->executeMigration('d7_node:et');
    $this->executeMigration('d7_node:test_content_type');
    $this->executeMigration('d7_node:forum');
    $this->executeMigration('d7_comment');
    $this->executeMigration('d7_taxonomy_term');
    $this->executeMigration('d7_flag');
    $this->executeMigration('d7_flagging');
  }

  /**
   * Asserts that flagging entities have been migrated.
   */
  public function testMigrationResults() {
    /** @var \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager */
    $entityTypeManager = $this->container->get('entity_type.manager');
    /** @var \Drupal\flag\Entity\Flagging[] $flaggings */
    $flaggings = $entityTypeManager
      ->getStorage('flagging')
      ->loadMultiple();

    $this->assertCount(12, $flaggings);

    // Test flagging.
    $actual = $flaggings[1]->toArray();
    unset($actual['uuid']);
    $expected = [
      'id' => [0 => ['value' => 1]],
      'flag_id' => [0 => ['target_id' => 'node_flag']],
      'entity_type' => [0 => ['value' => 'node']],
      'entity_id' => [0 => ['value' => 2]],
      'flagged_entity' => [0 => ['target_id' => 2]],
      'global' => [0 => ['value' => 0]],
      'uid' => [0 => ['target_id' => '3']],
      'session_id' => [],
      'created' => [0 => ['value' => '1564543637']],
    ];
    $this->assertEquals($expected, $actual);
  }

}
