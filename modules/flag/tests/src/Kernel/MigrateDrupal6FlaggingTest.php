<?php

namespace Drupal\Tests\flag\Kernel;

use Drupal\Tests\migrate_drupal\Kernel\d6\MigrateDrupal6TestBase;

/**
 * Tests migration of flagging data.
 *
 * @group flag
 */
class MigrateDrupal6FlaggingTest extends MigrateDrupal6TestBase {

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

    $fixture = __DIR__ . '/../../fixtures/drupal6.php';
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

    $this->executeMigration('d6_filter_format');
    $this->executeMigration('d6_user_role');
    $this->executeMigration('d6_node_settings');
    $this->executeMigration('d6_node_type');
    $this->executeMigration('d6_field');
    $this->executeMigration('d6_field_instance');
    $this->executeMigration('d6_user');
    $this->executeMigration('d6_comment_type');
    $this->executeMigration('d6_comment_field');
    $this->executeMigration('d6_comment_field_instance');
    $this->executeMigration('d6_comment_entity_display');
    $this->executeMigration('d6_comment_entity_form_display');
    $this->executeMigration('d6_taxonomy_vocabulary');
    $this->executeMigration('d6_node');
    $this->executeMigration('d6_node:page');
    $this->executeMigration('d6_node:company');
    $this->executeMigration('d6_node:employee');
    $this->executeMigration('d6_node:story');
    $this->executeMigration('d6_node:test_planet');
    $this->executeMigration('d6_node:forum');
    $this->executeMigration('d6_comment');
    $this->executeMigration('d6_taxonomy_term');
    $this->executeMigration('d6_flag');
    $this->executeMigration('d6_flagging');
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
      'uid' => [0 => ['target_id' => 2]],
      'session_id' => [],
      'created' => [0 => ['value' => '1564543637']],
    ];
    $this->assertEquals($expected, $actual);
  }

}
