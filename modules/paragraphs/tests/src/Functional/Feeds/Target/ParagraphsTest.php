<?php

namespace Drupal\Tests\paragraphs\Functional\Feeds\Target;

use Drupal\Tests\feeds\Functional\FeedsBrowserTestBase;
use Drupal\Tests\paragraphs\FunctionalJavascript\ParagraphsTestBaseTrait;

/**
 * Tests the FeedsTarget plugin "paragraphs" in the UI.
 *
 * @group paragraphs
 */
class ParagraphsTest extends FeedsBrowserTestBase {

  use ParagraphsTestBaseTrait;

  /**
   * Modules to enable.
   *
   * @var string[]
   */
  protected static $modules = [
    'feeds',
    'node',
    'paragraphs',
  ];

  /**
   * The feed type entity.
   *
   * @var \Drupal\feeds\Entity\FeedType
   */
  protected $feedType;

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    // Add a Paragraph field to the article content type.
    $this->addParagraphsField('article', 'field_paragraphs', 'node');

    $this->addParagraphsType('test_paragraph');
    $this->addFieldtoParagraphType('test_paragraph', 'field_text', 'text');

    // Create a feed type.
    $this->feedType = $this->createFeedType();
  }

  /**
   * Tests adding mapping to a paragraph field.
   */
  public function testAddMapping() {
    $this->drupalGet('admin/structure/feeds/manage/' . $this->feedType->id() . '/mapping');
    $edit = [
      'add_target' => 'field_paragraphs',
    ];
    $this->submitForm($edit, 'Save');

    // And try to configure it.
    $edit = [];
    $this->submitForm($edit, 'target-settings-2');

    // Select paragraphs type.
    $edit = [
      'mappings[2][settings][paragraphs_type]' => 'test_paragraph',
    ];
    $this->submitForm($edit, 'target-save-2');

    // Configure again to select field.
    $edit = [];
    $this->submitForm($edit, 'target-settings-2');

    // Select paragraphs field.
    $edit = [
      'mappings[2][settings][paragraph_field]' => 'field_text',
    ];
    $this->submitForm($edit, 'target-save-2');

    // Set a source and save mappings.
    $edit = [
      'mappings[2][map][value][select]' => 'content',
    ];
    $this->submitForm($edit, 'Save');

    // Assert expected mapping configuration.
    $this->feedType = $this->reloadEntity($this->feedType);
    $saved_mappings = $this->feedType->getMappings();
    $expected_mapping = [
      'target' => 'field_paragraphs',
      'map' => [
        'value' => 'content',
      ],
      'settings' => [
        'paragraphs_type' => 'test_paragraph',
        'paragraph_field' => 'field_text',
        'language' => '',
        'format' => 'plain_text',
      ],
    ];
    $this->assertEquals($expected_mapping, $saved_mappings[2]);
  }

}
