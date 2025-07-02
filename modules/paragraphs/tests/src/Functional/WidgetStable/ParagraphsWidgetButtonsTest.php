<?php

namespace Drupal\Tests\paragraphs\Functional\WidgetStable;

use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Tests\paragraphs\FunctionalJavascript\ParagraphsTestBaseTrait;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\NodeType;

/**
 * Tests paragraphs stable widget buttons.
 *
 * @group paragraphs
 */
class ParagraphsWidgetButtonsTest extends ParagraphsTestBase {

  use ParagraphsTestBaseTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'paragraphs_test',
    'language',
    'content_translation',
  ];

  /**
   * Tests the widget buttons of paragraphs.
   */
  public function testWidgetButtons() {
    $this->addParagraphedContentType('paragraphed_test');

    $this->loginAsAdmin(['create paragraphed_test content', 'edit any paragraphed_test content']);
    // Add a Paragraph type.
    $paragraph_type = 'text_paragraph';
    $this->addParagraphsType($paragraph_type);
    $this->addParagraphsType('text');

    // Add a text field to the text_paragraph type.
    static::fieldUIAddNewField('admin/structure/paragraphs_type/' . $paragraph_type, 'text', 'Text', 'text_long', [], []);
    $edit = [
      'fields[field_paragraphs][type]' => 'paragraphs',
    ];
    $this->drupalGet('admin/structure/types/manage/paragraphed_test/form-display');
    $this->submitForm($edit, 'Save');
    $this->drupalGet('node/add/paragraphed_test');
    $this->submitForm([], 'field_paragraphs_text_paragraph_add_more');

    // Create a node with a Paragraph.
    $text = 'recognizable_text';
    $edit = [
      'title[0][value]' => 'paragraphs_mode_test',
      'field_paragraphs[0][subform][field_text][0][value]' => $text,
    ];
    $this->submitForm([], 'field_paragraphs_text_paragraph_add_more');
    $this->submitForm($edit, 'Save');
    $node = $this->drupalGetNodeByTitle('paragraphs_mode_test');

    // Test the 'Open' edit mode.
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->assertSession()->fieldValueEquals('field_paragraphs[0][subform][field_text][0][value]', $text);
    $this->submitForm([], 'Save');
    $this->assertSession()->pageTextContains($text);

    // Test the 'Closed' edit mode.
    $this->setParagraphsWidgetMode('paragraphed_test', 'field_paragraphs', 'closed');
    $this->drupalGet('node/' . $node->id() . '/edit');
    // Click "Edit" button.
    $this->submitForm([], 'field_paragraphs_0_edit');
    $this->submitForm([], 'field_paragraphs_1_edit');
    $this->assertSession()->fieldValueEquals('field_paragraphs[0][subform][field_text][0][value]', $text);
    $closed_mode_text = 'closed_mode_text';
    // Click "Collapse" button on both paragraphs.
    $edit = ['field_paragraphs[0][subform][field_text][0][value]' => $closed_mode_text];
    $this->submitForm($edit, 'field_paragraphs_0_collapse');
    $edit = ['field_paragraphs[1][subform][field_text][0][value]' => $closed_mode_text];
    $this->submitForm($edit, 'field_paragraphs_1_collapse');
    // Verify that we have warning message for each paragraph.
    $this->assertEquals(2, count($this->xpath("//*[contains(@class, 'paragraphs-icon-changed')]")));
    $this->assertSession()->responseContains('<span class="summary-content">' . $closed_mode_text);
    $this->submitForm([], 'Save');
    $this->assertSession()->pageTextContains('paragraphed_test ' . $node->label() . ' has been updated.');
    $this->assertSession()->pageTextContains($closed_mode_text);

    // Test the 'Preview' closed mode.
    $this->setParagraphsWidgetSettings('paragraphed_test', 'field_paragraphs', ['closed_mode' => 'preview']);
    $this->drupalGet('node/' . $node->id() . '/edit');
    // Click "Edit" button.
    $this->submitForm([], 'field_paragraphs_0_edit');
    $this->assertSession()->fieldValueEquals('field_paragraphs[0][subform][field_text][0][value]', $closed_mode_text);
    $preview_mode_text = 'preview_mode_text';
    $edit = ['field_paragraphs[0][subform][field_text][0][value]' => $preview_mode_text];
    // Click "Collapse" button.
    $this->submitForm($edit, 'field_paragraphs_0_collapse');
    $this->assertSession()->pageTextContains('You have unsaved changes on this Paragraph item.');
    $this->assertEquals(1, count($this->xpath("//*[contains(@class, 'paragraphs-icon-changed')]")));
    $this->assertSession()->pageTextContains($preview_mode_text);
    $this->submitForm([], 'Save');
    $this->assertSession()->pageTextContains('paragraphed_test ' . $node->label() . ' has been updated.');
    $this->assertSession()->pageTextContains($preview_mode_text);

    // Test the remove function.
    $this->drupalGet('node/' . $node->id() . '/edit');
    // Click "Remove" button.
    $this->submitForm([], 'field_paragraphs_0_remove');
    $this->submitForm([], 'Save');
    $this->assertSession()->pageTextContains('paragraphed_test ' . $node->label() . ' has been updated.');
    $this->assertSession()->pageTextNotContains($preview_mode_text);
  }

  /**
   * Tests if buttons are present for each widget mode.
   */
  public function testButtonsVisibility() {
    $this->addParagraphedContentType('paragraphed_test');

    $this->loginAsAdmin([
      'create paragraphed_test content',
      'edit any paragraphed_test content',
      'administer content translation',
      'administer languages',
      'create content translations',
      'translate any entity',
    ]);
    // Add a Paragraph type.
    $paragraph_type = 'text_paragraph';
    $this->addParagraphsType($paragraph_type);
    $this->addParagraphsType('text');

    // Add a text field to the text_paragraph type.
    static::fieldUIAddNewField('admin/structure/paragraphs_type/' . $paragraph_type, 'text', 'Text', 'text_long', [], []);
    $edit = [
      'fields[field_paragraphs][type]' => 'paragraphs',
    ];
    $this->drupalGet('admin/structure/types/manage/paragraphed_test/form-display');
    $this->submitForm($edit, 'Save');
    $this->drupalGet('node/add/paragraphed_test');
    $this->submitForm([], 'field_paragraphs_text_paragraph_add_more');

    // Create a node with a Paragraph.
    $text = 'recognizable_text';
    $edit = [
      'title[0][value]' => 'paragraphs_mode_test',
      'field_paragraphs[0][subform][field_text][0][value]' => $text,
    ];
    $this->submitForm([], 'field_paragraphs_text_paragraph_add_more');
    $this->submitForm($edit, 'Save');
    $node = $this->drupalGetNodeByTitle('paragraphs_mode_test');

    // Checking visible buttons on "Open" mode.
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->assertSession()->buttonExists('field_paragraphs_0_collapse');
    $this->assertSession()->buttonExists('field_paragraphs_0_remove');
    $this->assertSession()->buttonExists('field_paragraphs_0_duplicate');

    // Checking visible buttons on "Closed" mode.
    $this->setParagraphsWidgetMode('paragraphed_test', 'field_paragraphs', 'closed');
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->assertSession()->buttonExists('field_paragraphs_0_edit');
    $this->assertSession()->buttonExists('field_paragraphs_0_remove');
    $this->assertSession()->buttonExists('field_paragraphs_0_duplicate');

    // Checking visible buttons on "Preview" mode.
    $this->setParagraphsWidgetMode('paragraphed_test', 'field_paragraphs', 'closed');
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->assertSession()->buttonExists('field_paragraphs_0_edit');
    $this->assertSession()->buttonExists('field_paragraphs_0_remove');
    $this->assertSession()->buttonExists('field_paragraphs_0_duplicate');

    // Checking always show collapse and edit actions.
    $this->addParagraphsType('nested_paragraph');
    static::fieldUIAddNewField('admin/structure/paragraphs_type/nested_paragraph', 'nested', 'Nested', 'field_ui:entity_reference_revisions:paragraph', [
      'settings[target_type]' => 'paragraph',
      'cardinality' => '-1',
    ], []);
    $this->drupalGet('admin/structure/paragraphs_type/nested_paragraph/form-display');
    $edit = [
      'fields[field_nested][type]' => 'paragraphs',
    ];
    $this->submitForm($edit, 'Save');
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->submitForm([], 'field_paragraphs_nested_paragraph_add_more');
    $this->submitForm([], 'field_paragraphs_2_subform_field_nested_nested_paragraph_add_more');
    // Collapse is present on each nesting level.
    $this->assertSession()->buttonExists('field_paragraphs_2_collapse');
    $this->assertSession()->buttonExists('field_paragraphs_2_subform_field_nested_0_collapse');

    // Tests hook_paragraphs_widget_actions_alter.
    $this->drupalGet('node/add/paragraphed_test');
    $this->submitForm([], 'Add text');
    $this->assertSession()->buttonNotExists('edit-field-paragraphs-0-top-links-test-button');
    \Drupal::state()->set('paragraphs_test_dropbutton', TRUE);
    $this->drupalGet('node/add/paragraphed_test');
    $this->submitForm([], 'Add text');
    $this->assertSession()->buttonNotExists('edit-field-paragraphs-0-top-links-test-button');

    ConfigurableLanguage::createFromLangcode('sr')->save();

    // Enable translation for test content.
    $this->drupalGet('admin/config/regional/content-language');
    $edit = [
      'entity_types[node]' => TRUE,
      'settings[node][paragraphed_test][translatable]' => TRUE,
    ];
    $this->submitForm($edit, 'Save configuration');

    // Check that operation is hidden during translation.
    $this->drupalGet('sr/node/' . $node->id() . '/translations/add/en/sr');
    $this->assertSession()->buttonNotExists('edit-field-paragraphs-1-top-actions-dropdown-actions-test-button');

    // Check that "Duplicate" is hidden during translation.
    $this->assertSession()->buttonNotExists('field_paragraphs_0_duplicate');
    $this->assertSession()->responseNotContains('value="Duplicate"');
  }

  /**
   * Tests buttons visibility exception.
   */
  public function testButtonsVisibilityException() {
    // Hide the button if field is required, cardinality is one and just one
    // paragraph type is allowed.
    $content_type_name = 'paragraphed_test';
    $paragraphs_field_name = 'field_paragraphs';
    $widget_type = 'paragraphs';
    $entity_type = 'node';

    // Create the content type.
    $node_type = NodeType::create([
      'type' => $content_type_name,
      'name' => $content_type_name,
    ]);
    $node_type->save();

    $field_storage = FieldStorageConfig::loadByName($content_type_name, $paragraphs_field_name);
    if (!$field_storage) {
      // Add a paragraphs field.
      $field_storage = FieldStorageConfig::create([
        'field_name' => $paragraphs_field_name,
        'entity_type' => $entity_type,
        'type' => 'entity_reference_revisions',
        'cardinality' => '1',
        'settings' => [
          'target_type' => 'paragraph',
        ],
      ]);
      $field_storage->save();
    }
    $field = FieldConfig::create([
      'field_storage' => $field_storage,
      'bundle' => $content_type_name,
      'required' => TRUE,
      'settings' => [
        'handler' => 'default:paragraph',
        'handler_settings' => ['target_bundles' => NULL],
      ],
    ]);
    $field->save();

    /** @var \Drupal\Core\Entity\EntityDisplayRepositoryInterface $display_repository */
    $display_repository = \Drupal::service('entity_display.repository');
    $form_display = $display_repository->getFormDisplay($entity_type, $content_type_name)
      ->setComponent($paragraphs_field_name, ['type' => $widget_type]);
    $form_display->save();

    $view_display = $display_repository->getViewDisplay($entity_type, $content_type_name)
      ->setComponent($paragraphs_field_name, ['type' => 'entity_reference_revisions_entity_view']);
    $view_display->save();

    $this->loginAsAdmin([
      'create paragraphed_test content',
      'edit any paragraphed_test content',
      'administer content translation',
      'administer languages',
      'create content translations',
      'translate any entity',
    ]);
    // Add a Paragraph type.
    $paragraph_type = 'text_paragraph';
    $this->addParagraphsType($paragraph_type);

    // Add a text field to the text_paragraph type.
    static::fieldUIAddNewField('admin/structure/paragraphs_type/' . $paragraph_type, 'text', 'Text', 'text_long', [], []);
    $edit = [
      'fields[field_paragraphs][type]' => 'paragraphs',
    ];
    $this->drupalGet('admin/structure/types/manage/paragraphed_test/form-display');
    $this->submitForm($edit, 'Save');

    // Checking hidden button on "Open" mode.
    $this->drupalGet('node/add/paragraphed_test');
    $this->assertSession()->buttonNotExists('field_paragraphs_0_remove');
    $this->assertSession()->fieldValueEquals('field_paragraphs[0][subform][field_text][0][value]', '');

    // Checking hidden button on "Closed" mode.
    $this->setParagraphsWidgetMode('paragraphed_test', 'field_paragraphs', 'closed');
    $this->drupalGet('node/add/paragraphed_test');
    $this->assertSession()->buttonNotExists('field_paragraphs_0_remove');
    $this->assertSession()->fieldValueEquals('field_paragraphs[0][subform][field_text][0][value]', '');

    // Checking that the "Duplicate" button is not shown when cardinality is 1.
    $this->drupalGet('node/add/paragraphed_test');
    $this->assertSession()->buttonNotExists('field_paragraphs_0_duplicate');
  }

}
