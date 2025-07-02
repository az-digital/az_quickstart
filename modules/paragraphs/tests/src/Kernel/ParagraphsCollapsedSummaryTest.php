<?php

namespace Drupal\Tests\paragraphs\Kernel;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\paragraphs\Entity\ParagraphsType;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\field\Traits\EntityReferenceFieldCreationTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;

/**
 * Tests the collapsed summary options.
 *
 * @group paragraphs
 */
class ParagraphsCollapsedSummaryTest extends KernelTestBase {

  use EntityReferenceFieldCreationTrait;
  use UserCreationTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'paragraphs',
    'user',
    'system',
    'field',
    'entity_reference_revisions',
    'paragraphs_test',
    'file',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installEntitySchema('paragraph');
    $this->installSchema('system', ['sequences']);
    \Drupal::moduleHandler()->loadInclude('paragraphs', 'install');

    // Create a text paragraph type with test_text_color plugin enabled.
    $paragraph_type = ParagraphsType::create(array(
      'label' => 'text_paragraph',
      'id' => 'text_paragraph',
      'behavior_plugins' => [
        'test_text_color' => [
          'enabled' => TRUE,
        ],
      ],
    ));
    $paragraph_type->save();
    $this->addParagraphsField('text_paragraph', 'text', 'string');
    EntityFormDisplay::create([
      'targetEntityType' => 'paragraph',
      'bundle' => 'text_paragraph',
      'mode' => 'default',
      'status' => TRUE,
    ])->setComponent('text', ['type' => 'string_textfield'])->save();

    // Add a nested Paragraph type.
    $paragraphs_type = ParagraphsType::create([
      'id' => 'nested_paragraph',
      'label' => 'nested_paragraph',
    ]);
    $paragraphs_type->save();
    $this->addParagraphsField('nested_paragraph', 'nested_paragraph_field', 'entity_reference_revisions', ['target_type' => 'paragraph']);
    EntityFormDisplay::create([
      'targetEntityType' => 'paragraph',
      'bundle' => 'nested_paragraph',
      'mode' => 'default',
      'status' => TRUE,
    ])->setComponent('nested_paragraph_field', ['type' => 'paragraphs'])->save();
  }

  /**
   * Tests the collapsed summary additional options.
   */
  public function testCollapsedSummaryOptions() {
    // Create a paragraph and set its feature settings.
    $paragraph = Paragraph::create([
      'type' => 'text_paragraph',
      'text' => 'Example text for a text paragraph',
    ]);
    $feature_settings = [
      'test_text_color' => [
        'text_color' => 'red',
      ],
    ];
    $paragraph->setAllBehaviorSettings($feature_settings);
    $paragraph->save();

    // Load the paragraph and assert its stored feature settings.
    $paragraph = Paragraph::load($paragraph->id());
    $this->assertEquals($paragraph->getAllBehaviorSettings(), $feature_settings);
    $this->assertEquals((string) $paragraph->getSummary(), '<div class="paragraphs-description paragraphs-collapsed-description"><div class="paragraphs-content-wrapper"><span class="summary-content">Example text for a text paragraph</span></div><div class="paragraphs-plugin-wrapper"><span class="summary-plugin"><span class="summary-plugin-label">Text color</span>red</span></div></div>');

    // Check the summary and the additional options.
    $paragraph_1 = Paragraph::create([
      'type' => 'nested_paragraph',
      'nested_paragraph_field' => [$paragraph],
    ]);
    $paragraph_1->save();
    // We do not include behavior summaries of nested children in the parent
    // summary.
    $this->assertEquals((string) $paragraph_1->getSummary(), '<div class="paragraphs-description paragraphs-collapsed-description"><div class="paragraphs-content-wrapper"><span class="summary-content">Example text for a text paragraph</span></div></div>');
    $info = $paragraph_1->getIcons();
    $this->assertEquals($info['count']['#prefix'], '<span class="paragraphs-badge" title="1 child">');
    $this->assertEquals($info['count']['#suffix'], '</span>');

    $this->assertEquals((string) $paragraph_1->getSummary(['depth_limit' => 0]), '');
  }

  /**
   * Tests nested paragraph summary.
   */
  public function testNestedParagraphSummary() {
    // Create a text paragraph.
    $paragraph_text_1 = Paragraph::create([
      'type' => 'text_paragraph',
      'text' => 'Text paragraph on nested level',
    ]);
    $paragraph_text_1->save();

    // Add a nested paragraph with the text inside.
    $paragraph_nested_1 = Paragraph::create([
      'type' => 'nested_paragraph',
      'nested_paragraph_field' => [$paragraph_text_1],
    ]);
    $paragraph_nested_1->save();

    // Create a new text paragraph.
    $paragraph_text_2 = Paragraph::create([
      'type' => 'text_paragraph',
      'text' => 'Text paragraph on top level',
    ]);
    $paragraph_text_2->save();

    // Add a nested paragraph with the new text and nested paragraph inside.
    $paragraph_nested_2 = Paragraph::create([
      'type' => 'nested_paragraph',
      'nested_paragraph_field' => [$paragraph_text_2, $paragraph_nested_1],
    ]);
    $paragraph_nested_2->save();
    $this->assertEquals((string) $paragraph_nested_2->getSummary(['show_behavior_summary' => FALSE]), '<div class="paragraphs-description paragraphs-collapsed-description"><div class="paragraphs-content-wrapper"><span class="summary-content">Text paragraph on top level</span></div></div>');
    $this->assertEquals((string) $paragraph_nested_2->getSummary(['show_behavior_summary' => FALSE, 'depth_limit' => 2]), '<div class="paragraphs-description paragraphs-collapsed-description"><div class="paragraphs-content-wrapper"><span class="summary-content">Text paragraph on top level</span>, <span class="summary-content">Text paragraph on nested level</span></div></div>');
    $info = $paragraph_nested_2->getIcons();
    $this->assertEquals($info['count']['#prefix'], '<span class="paragraphs-badge" title="2 children">');
    $this->assertEquals($info['count']['#suffix'], '</span>');
  }

  /**
   * Tests multiple entity references are visible in the paragraph summary.
   */
  public function testMultipleEntityReferences() {
    $user1 = $this->createUser([], 'bob');
    $user2 = $this->createUser([], 'pete');
    $paragraphs_type = ParagraphsType::create([
      'label' => 'Multiple entity references',
      'id' => 'multiple_entity_references',
    ]);
    $paragraphs_type->save();
    $this->createEntityReferenceField('paragraph', 'multiple_entity_references', 'field_user_references', 'Users', 'user', 'default', [], FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED);
    EntityFormDisplay::create([
      'targetEntityType' => 'paragraph',
      'bundle' => 'multiple_entity_references',
      'mode' => 'default',
      'status' => TRUE,
    ])->setComponent('field_user_references', ['type' => 'options_select'])->save();
    $paragraph_with_multiple_entity_references = Paragraph::create([
      'type' => 'multiple_entity_references',
    ]);
    $paragraph_with_multiple_entity_references->get('field_user_references')->appendItem($user1->id());
    $paragraph_with_multiple_entity_references->get('field_user_references')->appendItem($user2->id());
    $paragraph_with_multiple_entity_references->save();
    $this->assertEquals('<div class="paragraphs-description paragraphs-collapsed-description"><div class="paragraphs-content-wrapper"><span class="summary-content">bob</span>, <span class="summary-content">pete</span></div></div>', (string) $paragraph_with_multiple_entity_references->getSummary());
  }

  /**
   * Adds a field to a given paragraph type.
   *
   * @param string $paragraph_type_name
   *   Paragraph type name to be used.
   * @param string $field_name
   *   Paragraphs field name to be used.
   * @param string $field_type
   *   Type of the field.
   * @param array $field_edit
   *   Edit settings for the field.
   */
  protected function addParagraphsField($paragraph_type_name, $field_name, $field_type, $field_edit = []) {
    // Add a paragraphs field.
    $field_storage = FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'paragraph',
      'type' => $field_type,
      'cardinality' => '-1',
      'settings' => $field_edit
    ]);
    $field_storage->save();
    $field = FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => $paragraph_type_name,
      'settings' => [
        'handler' => 'default:paragraph',
        'handler_settings' => ['target_bundles' => NULL],
      ],
    ]);
    $field->save();
  }

}
