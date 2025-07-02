<?php

namespace Drupal\Tests\paragraphs\Kernel\migrate;

use Drupal\Core\TypedData\TranslatableInterface;
use Drupal\node\Entity\Node;
use Drupal\Tests\paragraphs\Traits\ParagraphsNodeMigrationAssertionsTrait;

/**
 * Test 'classic' Paragraph content migration.
 *
 * @group paragraphs
 * @require entity_reference_revisions
 */
class ParagraphContentMigrationTest extends ParagraphsMigrationTestBase {

  use ParagraphsNodeMigrationAssertionsTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'comment',
    'datetime',
    'datetime_range',
    'field',
    'file',
    'image',
    'link',
    'menu_ui',
    'node',
    'options',
    'system',
    'taxonomy',
    'telephone',
    'text',
    'user',
    'content_translation',
    'language'
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('file');
    $this->installEntitySchema('node');
    $this->installEntitySchema('paragraph');
    $this->installEntitySchema('comment');
    $this->installSchema('node', ['node_access']);
    $this->installSchema('comment', [
      'comment_entity_statistics',
    ]);

    $this->executeMigrationWithDependencies('d7_field_collection_revisions');
    $this->executeMigrationWithDependencies('d7_paragraphs_revisions');
    $this->executeMigrationWithDependencies('d7_node:paragraphs_test');

    $this->prepareMigrations([
      'd7_node:article' => [],
      'd7_node:blog' => [],
      'd7_node:book' => [],
      'd7_node:forum' => [],
      'd7_node:test_content_type' => [],
    ]);
  }

  /**
   * Tests the migration of a content with paragraphs and field collections.
   *
   * @dataProvider providerParagraphContentMigration
   */
  public function testParagraphContentMigration($migration_to_run) {
    if ($migration_to_run) {
      $this->executeMigration($migration_to_run);
    }

    $this->assertNode8Paragraphs();

    $this->assertNode9Paragraphs();

    $node_9 = Node::load(9);
    if ($node_9 instanceof TranslatableInterface && !empty($node_9->getTranslationLanguages(FALSE))) {
      $this->assertIcelandicNode9Paragraphs();
    }
  }

  /**
   * Provides data and expected results for testing paragraph migrations.
   *
   * @return string[][]
   *   The node migration to run.
   */
  public static function providerParagraphContentMigration() {
    return [
      ['migration_to_run' => NULL],
      ['migration_to_run' => 'd7_node_revision:paragraphs_test'],
      ['migration_to_run' => 'd7_node_translation:paragraphs_test'],
      ['migration_to_run' => 'd7_node_complete:paragraphs_test'],
    ];
  }

}
