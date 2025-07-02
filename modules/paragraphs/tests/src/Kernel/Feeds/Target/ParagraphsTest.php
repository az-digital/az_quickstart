<?php

namespace Drupal\Tests\paragraphs\Kernel\Feeds\Target;

use Drupal\node\Entity\Node;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\Tests\feeds\Kernel\FeedsKernelTestBase;
use Drupal\Tests\paragraphs\FunctionalJavascript\ParagraphsTestBaseTrait;

/**
 * @coversDefaultClass \Drupal\paragraphs\Feeds\Target\Paragraphs
 * @group paragraphs
 */
class ParagraphsTest extends FeedsKernelTestBase {

  use ParagraphsTestBaseTrait;

  /**
   * Modules to enable.
   *
   * @var string[]
   */
  protected static $modules = [
    'field',
    'feeds',
    'node',
    'entity_reference_revisions',
    'paragraphs',
    'file',
    'text',
  ];

  /**
   * The feed type.
   *
   * @var \Drupal\feeds\FeedTypeInterface
   */
  protected $feedType;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('paragraph');

    // Create feed type.
    $this->feedType = $this->createFeedTypeForCsv([
      'guid' => 'guid',
      'title' => 'title',
      'alpha' => 'alpha',
    ]);

    // Add a Paragraph field to the article content type.
    $this->addParagraphsField('article', 'field_paragraphs', 'node');

    $this->addParagraphsType('test_paragraph');
    $this->addFieldtoParagraphType('test_paragraph', 'field_text', 'text');

    drupal_flush_all_caches();
  }

  /**
   * Tests importing from a timestamp.
   */
  public function testImportParagraph() {
    $this->feedType->addMapping([
      'target' => 'field_paragraphs',
      'map' => ['value' => 'alpha'],
      'settings' => [
        'paragraphs_type' => 'test_paragraph',
        'paragraph_field' => 'field_text',
        'language' => '',
        'format' => 'plain_text',
      ],
    ]);
    $this->feedType->save();

    // Import.
    $feed = $this->createFeed($this->feedType->id(), [
      'source' => $this->resourcesPath() . '/csv/content.csv',
    ]);
    $feed->import();

    // Assert two created nodes with paragraphs.
    $this->assertNodeCount(2);

    $expected = [
      1 => 'Lorem',
      2 => 'Ut wisi',
    ];
    foreach ($expected as $nid => $value) {
      // Load the node and then the paragraph.
      $node = Node::load($nid);
      $paragraph = Paragraph::load($node->field_paragraphs->target_id);

      $this->assertEquals($value, $paragraph->field_text->value);
    }
  }

}
