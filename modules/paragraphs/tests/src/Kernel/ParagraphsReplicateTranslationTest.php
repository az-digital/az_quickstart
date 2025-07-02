<?php

namespace Drupal\Tests\paragraphs\Kernel;

use Drupal\Core\Entity\Entity;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\paragraphs\Entity\ParagraphsType;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the replication functionality provided by Replicate module.
 *
 * @group paragraphs
 */
class ParagraphsReplicateTranslationTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'paragraphs',
    'replicate',
    'node',
    'user',
    'system',
    'field',
    'entity_reference_revisions',
    'file',
    'content_translation',
    'language'
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // Create paragraphs and article content types.
    $values = ['type' => 'article', 'name' => 'Article'];
    $node_type = NodeType::create($values);
    $node_type->save();
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installEntitySchema('paragraph');
    $this->installSchema('system', ['sequences']);
    $this->installSchema('node', ['node_access']);
    $this->installConfig(['language']);
    \Drupal::moduleHandler()->loadInclude('paragraphs', 'install');

    ConfigurableLanguage::createFromLangcode('de')->save();
    ConfigurableLanguage::createFromLangcode('fr')->save();
  }

  /**
   * Tests the replication of the parent entity.
   */
  public function testReplicationTranslated() {
    // Create the paragraph type.
    $paragraph_type = ParagraphsType::create([
      'label' => 'test_text',
      'id' => 'test_text'
    ]);
    $paragraph_type->save();

    $paragraph_type_nested = ParagraphsType::create([
      'label' => 'test_nested',
      'id' => 'test_nested',
    ]);
    $paragraph_type_nested->save();

    // Add a title field to both paragraph bundles.
    $field_storage = FieldStorageConfig::create([
      'field_name' => 'title',
      'entity_type' => 'paragraph',
      'type' => 'string',
      'cardinality' => '1',
    ]);
    $field_storage->save();
    $field = FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'test_text',
      'translatable' => TRUE,
    ]);
    $field->save();
    $field = FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'test_nested',
      'translatable' => TRUE,
    ]);
    $field->save();

    // Add a paragraph field to the nested paragraph.
    $field_storage = FieldStorageConfig::create([
      'field_name' => 'nested_paragraph_field',
      'entity_type' => 'paragraph',
      'type' => 'entity_reference_revisions',
      'cardinality' => '-1',
      'settings' => [
        'target_type' => 'paragraph',
      ],
    ]);
    $field_storage->save();
    $field = FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'test_nested',
      'translatable' => TRUE,
    ]);
    $field->save();

    // Add a paragraph field to the article.
    $field_storage = FieldStorageConfig::create([
      'field_name' => 'node_paragraph_field',
      'entity_type' => 'node',
      'type' => 'entity_reference_revisions',
      'cardinality' => '-1',
      'settings' => [
        'target_type' => 'paragraph',
      ],
    ]);
    $field_storage->save();
    $field = FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => 'article',
      'translatable' => TRUE,
    ]);
    $field->save();

    // Create a paragraph.
    $paragraph = Paragraph::create([
      'title' => 'Simple paragraph',
      'type' => 'test_text',
    ]);
    $paragraph->addTranslation('de');
    $paragraph->addTranslation('fr');
    $paragraph->save();

    // Create nested paragraph.
    $paragraph_nested = Paragraph::create([
      'title' => 'Nested paragraph',
      'type' => 'test_text',
    ]);
    $paragraph_nested->addTranslation('de');
    $paragraph_nested->addTranslation('fr');
    $paragraph_nested->save();

    // Create another paragraph.
    $paragraph_nested_parent = Paragraph::create([
      'title' => 'Parent paragraph',
      'type' => 'test_nested',
      'nested_paragraph_field' => [$paragraph_nested],
    ]);
    $paragraph_nested_parent->addTranslation('de');
    $paragraph_nested_parent->addTranslation('fr');
    $paragraph_nested_parent->save();

    // Create a node with two paragraphs.
    $title = $this->randomMachineName();
    $node = Node::create([
      'title' => $title,
      'type' => 'article',
      'node_paragraph_field' => array($paragraph, $paragraph_nested_parent),
      ]);
    $node->addTranslation('de', ['title' => $title . ' de']);
    $node->addTranslation('fr', ['title' => $title . ' fr']);
    $node->save();

    // Simulate that only one of the 2 translations should be duplicated
    // (see #3215573).
    $node->removeTranslation('fr');

    $replicated_node = $this->container->get('replicate.replicator')
      ->replicateEntity($node);

    // Check that all paragraphs on the replicated node were replicated too.
    $this->assertNotEquals($replicated_node->id(), $node->id(), 'We have two different nodes.');
    $this->assertNotEquals($replicated_node->node_paragraph_field[0]->target_id, $node->node_paragraph_field[0]->target_id, 'Simple paragraph was duplicated.');
    $this->assertEquals('Simple paragraph', $replicated_node->node_paragraph_field[0]->entity->title->value, "Simple paragraph inherited title from it's original.");
    $this->assertNotEquals($replicated_node->node_paragraph_field[1]->target_id, $node->node_paragraph_field[1]->target_id, 'Parent paragraph was duplicated.');
    $this->assertEquals('Parent paragraph', $replicated_node->node_paragraph_field[1]->entity->title->value, "Parent paragraph inherited title from it's original.");
    $this->assertNotEquals($replicated_node->node_paragraph_field[1]->entity->nested_paragraph_field[0]->target_id, $node->node_paragraph_field[1]->entity->nested_paragraph_field[0]->target_id, 'Nested paragraph was duplicated.');
    $this->assertEquals('Nested paragraph', $replicated_node->node_paragraph_field[1]->entity->nested_paragraph_field[0]->entity->title->value, "Nested paragraph inherited title from it's original.");

    // DE translation should exist, FR translation should not exist.
    $this->assertEquals(["en", "de"], array_keys($replicated_node->getTranslationLanguages()));
    $this->assertEquals(["en", "de"], array_keys($replicated_node->node_paragraph_field[1]->entity->getTranslationLanguages()));
    $this->assertEquals(["en", "de"], array_keys($replicated_node->node_paragraph_field[1]->entity->nested_paragraph_field[0]->entity->getTranslationLanguages()));
  }
}
