<?php

namespace Drupal\Tests\paragraphs\Kernel;

use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\Tests\paragraphs\FunctionalJavascript\ParagraphsTestBaseTrait;
use Drupal\Tests\paragraphs\Traits\ParagraphsLastEntityQueryTrait;

/**
 * Tests some methods from the Paragraph entity.
 *
 * @group paragraphs
 */
class ParagraphsEntityMethodsTest extends KernelTestBase {

  use ParagraphsTestBaseTrait;

  /**
   * Modules to enable.
   *
   * @var string[]
   */
  protected static $modules = [
    'paragraphs',
    'node',
    'user',
    'system',
    'field',
    'entity_reference_revisions',
    'file',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installEntitySchema('paragraph');
    $this->installSchema('system', ['sequences']);
    $this->installSchema('node', ['node_access']);
    \Drupal::moduleHandler()->loadInclude('paragraphs', 'install');
  }

  /**
   * Tests the ::label() behavior on the paragraph entity.
   */
  public function testParagraphLabel() {
    $this->addParagraphedContentType('paragraphed_test');
    $paragraph_type = 'text_paragraph';
    $this->addParagraphsType($paragraph_type);
    $this->addFieldtoParagraphType($paragraph_type, 'field_text', 'string');

    // Create a node with a paragraph.
    $paragraph = Paragraph::create([
      'type' => 'text_paragraph',
      'field_text' => 'Example text that is very long and needs to be shortened.',
    ]);
    $paragraph->save();
    $node = Node::create([
      'title' => 'Test Node',
      'type' => 'paragraphed_test',
      'field_paragraphs' => [
        $paragraph,
      ],
    ]);
    $node->save();

    $storage = \Drupal::entityTypeManager()->getStorage('paragraph');

    // By this point the label should include the parent entity's label and the
    // field label.
    $paragraph = $storage->loadUnchanged($paragraph->id());
    $this->assertEquals('Test Node > field_paragraphs', $paragraph->label());

    // Create a new revision without the paragraph and verify the label on the
    // paragraph entity reflects that.
    /** @var \Drupal\node\NodeInterface $node */
    $node->set('field_paragraphs', []);
    $node->setNewRevision(TRUE);
    $node->save();
    $paragraph = $storage->loadUnchanged($paragraph->id());
    $this->assertEquals('Test Node > field_paragraphs (previous revision)', $paragraph->label());

    // Delete the node and check if the label reflects that.
    $node->delete();
    $paragraph = $storage->loadUnchanged($paragraph->id());
    $this->assertEquals('Orphaned text_paragraph: Example text that is very long and needs to be sh…', $paragraph->label());

    $paragraph3 = Paragraph::create([
      'type' => 'text_paragraph',
      'field_text' => 'Example text3',
    ]);
    $paragraph3->save();
    $node3 = Node::create([
      'title' => 'Test Node 3',
      'type' => 'paragraphed_test',
      'field_paragraphs' => [
        $paragraph3,
      ],
    ]);
    $node3->save();
    $paragraph3 = $storage->loadUnchanged($paragraph3->id());
    $this->assertEquals('Test Node 3 > field_paragraphs', $paragraph3->label());

    // If we delete the field on the node type, the paragraph becomes orphan.
    FieldStorageConfig::load('node.field_paragraphs')->delete();
    \Drupal::service('entity.memory_cache')->deleteAll();
    $paragraph3 = $storage->loadUnchanged($paragraph3->id());
    $this->assertEquals('Orphaned text_paragraph: Example text3', $paragraph3->label());
  }

}
