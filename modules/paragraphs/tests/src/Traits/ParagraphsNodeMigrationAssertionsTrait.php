<?php

namespace Drupal\Tests\paragraphs\Traits;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\TypedData\TranslatableInterface;
use Drupal\entity_reference_revisions\EntityReferenceRevisionsFieldItemList;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;
use Drupal\paragraphs\ParagraphInterface;

trait ParagraphsNodeMigrationAssertionsTrait {

  /**
   * Assertions on node 8.
   */
  protected function assertNode8Paragraphs() {
    $node_8 = Node::load(8);
    assert($node_8 instanceof NodeInterface);
    // Check 'field collection test' field.
    $node_8_field_collection_field_entities = $this->getReferencedEntities($node_8, 'field_field_collection_test', 2);
    $this->assertEquals('Field Collection Text Data One UND', $node_8_field_collection_field_entities[0]->field_text->value);
    $this->assertEquals('1', $node_8_field_collection_field_entities[0]->field_integer_list->value);
    $this->assertEquals('Field Collection Text Data Two UND', $node_8_field_collection_field_entities[1]->field_text->value);
    $this->assertNull($node_8_field_collection_field_entities[1]->field_integer_list->value);
    // Check 'any paragraph' field.
    $node_8_field_any_paragraph_entities = $this->getReferencedEntities($node_8, 'field_any_paragraph', 2);
    $this->assertEquals('Paragraph Field One Bundle One UND', $node_8_field_any_paragraph_entities[0]->field_text->value);
    $this->assertEquals('Some Text', $node_8_field_any_paragraph_entities[0]->field_text_list->value);
    $this->assertEquals('Paragraph Field One Bundle Two UND', $node_8_field_any_paragraph_entities[1]->field_text->value);
    $this->assertEquals('joe@joe.com', $node_8_field_any_paragraph_entities[1]->field_email->value);
    // Check 'paragraph one only' field.
    $node_8_field_paragraph_one_only_entities = $this->getReferencedEntities($node_8, 'field_paragraph_one_only', 1);
    $this->assertEquals('Paragraph Field Two Bundle One Revision Two UND', $node_8_field_paragraph_one_only_entities[0]->field_text->value);
    $this->assertEquals('Some more text', $node_8_field_paragraph_one_only_entities[0]->field_text_list->value);
    // Check 'nested fc outer' field.
    $node_8_field_nested_fc_outer_entities = $this->getReferencedEntities($node_8, 'field_nested_fc_outer', 1);
    assert($node_8_field_nested_fc_outer_entities[0] instanceof ParagraphInterface);
    $node_8_inner_nested_fc_0_entities = $this->getReferencedEntities($node_8_field_nested_fc_outer_entities[0], 'field_nested_fc_inner', 1);
    $this->assertEquals('Nested FC test text', $node_8_inner_nested_fc_0_entities[0]->field_text->value);
  }

  /**
   * Assertions of node 9.
   */
  protected function assertNode9Paragraphs() {
    $node_9 = Node::load(9);
    assert($node_9 instanceof NodeInterface);

    if ($this->container->get('module_handler')->moduleExists('content_translation') && $node_9 instanceof TranslatableInterface) {
      // Test the default translation.
      $node_9 = $node_9->getUntranslated();
      $this->assertSame('en', $node_9->language()->getId());
    }

    // Check 'field collection test' field.
    $node_9_field_collection_field_entities = $this->getReferencedEntities($node_9, 'field_field_collection_test', 1);
    $this->assertEquals('Field Collection Text Data Two EN', $node_9_field_collection_field_entities[0]->field_text->value);
    $this->assertEquals('2', $node_9_field_collection_field_entities[0]->field_integer_list->value);
    // Check 'any paragraph' field.
    $node_9_field_any_paragraph_entities = $this->getReferencedEntities($node_9, 'field_any_paragraph', 2);
    $this->assertEquals('Paragraph Field One Bundle One EN', $node_9_field_any_paragraph_entities[0]->field_text->value);
    $this->assertEquals('Some Text', $node_9_field_any_paragraph_entities[0]->field_text_list->value);
    $this->assertEquals('Paragraph Field One Bundle Two EN', $node_9_field_any_paragraph_entities[1]->field_text->value);
    $this->assertEquals('jose@jose.com', $node_9_field_any_paragraph_entities[1]->field_email->value);
    // Check 'paragraph one only' field.
    $node_9_field_paragraph_one_only_entities = $this->getReferencedEntities($node_9, 'field_paragraph_one_only', 1);
    $this->assertEquals('Paragraph Field Two Bundle One EN', $node_9_field_paragraph_one_only_entities[0]->field_text->value);
    $this->assertEquals('Some Text', $node_9_field_paragraph_one_only_entities[0]->field_text_list->value);
    // The 'nested fc outer' field should be empty.
    $this->getReferencedEntities($node_9, 'field_nested_fc_outer', 0);
  }

  /**
   * Assertions of the Icelandic translation of node 9.
   */
  protected function assertIcelandicNode9Paragraphs() {
    // Confirm that the Icelandic translation of node 9 (which was node 10 on
    // the source site) has the expected data.
    $node_9 = Node::load(9);
    assert($node_9 instanceof NodeInterface);
    assert($node_9 instanceof TranslatableInterface);
    $node_9_translation_languages = $node_9->getTranslationLanguages(FALSE);
    $this->assertEquals(['is'], array_keys($node_9_translation_languages));
    $node_9 = $node_9->getTranslation('is');
    $this->assertSame('is', $node_9->language()->getId());

    // Check 'field collection test' field.
    $node_9_field_collection_field_entities = $this->getReferencedEntities($node_9, 'field_field_collection_test', 3);
    $this->assertEquals('Field Collection Text Data One IS', $node_9_field_collection_field_entities[0]->field_text->value);
    $this->assertEquals('1', $node_9_field_collection_field_entities[0]->field_integer_list->value);
    $this->assertEquals('Field Collection Text Data Two IS', $node_9_field_collection_field_entities[1]->field_text->value);
    $this->assertEquals('2', $node_9_field_collection_field_entities[1]->field_integer_list->value);
    $this->assertEquals('Field Collection Text Data Three IS', $node_9_field_collection_field_entities[2]->field_text->value);
    $this->assertEquals('3', $node_9_field_collection_field_entities[2]->field_integer_list->value);
    // Check 'any paragraph' field.
    $node_9_field_any_paragraph_entities = $this->getReferencedEntities($node_9, 'field_any_paragraph', 3);
    $this->assertEquals('Paragraph Field One Bundle One IS', $node_9_field_any_paragraph_entities[0]->field_text->value);
    $this->assertEquals('Some Text', $node_9_field_any_paragraph_entities[0]->field_text_list->value);
    $this->assertEquals('Paragraph Field One Bundle Two IS', $node_9_field_any_paragraph_entities[1]->field_text->value);
    $this->assertEquals('jose@jose.com', $node_9_field_any_paragraph_entities[1]->field_email->value);
    $this->assertEquals('Paragraph Field One Bundle Two Delta 3 IS', $node_9_field_any_paragraph_entities[2]->field_text->value);
    $this->assertEquals('john@john.com', $node_9_field_any_paragraph_entities[2]->field_email->value);
    // Check 'paragraph one only' field.
    $node_9_field_paragraph_one_only_entities = $this->getReferencedEntities($node_9, 'field_paragraph_one_only', 1);
    $this->assertEquals('Paragraph Field Two Bundle One IS', $node_9_field_paragraph_one_only_entities[0]->field_text->value);
    $this->assertEquals('Some more text', $node_9_field_paragraph_one_only_entities[0]->field_text_list->value);
    // The 'nested fc outer' field should be empty.
    $this->getReferencedEntities($node_9, 'field_nested_fc_outer', 0);
  }

  /**
   * Get the referred entities.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The parent entity.
   * @param string $field_name
   *   The name of the entity revision reference field.
   * @param int $expected_count
   *   The expected number of the referenced entities.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   An array of entity objects keyed by field item deltas.
   */
  protected function getReferencedEntities(ContentEntityInterface $entity, $field_name, int $expected_count) {
    $entity_field = $entity->hasField($field_name) ?
      $entity->get($field_name) :
      NULL;
    assert($entity_field instanceof EntityReferenceRevisionsFieldItemList);
    $entity_field_entities = $entity_field->referencedEntities();
    $this->assertCount($expected_count, $entity_field_entities);

    return $entity_field_entities;
  }

}
