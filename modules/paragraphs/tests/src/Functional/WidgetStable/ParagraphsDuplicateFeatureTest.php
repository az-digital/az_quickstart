<?php

namespace Drupal\Tests\paragraphs\Functional\WidgetStable;

use Drupal\field\Entity\FieldStorageConfig;

/**
 * Tests paragraphs duplicate feature.
 *
 * @group paragraphs
 */
class ParagraphsDuplicateFeatureTest extends ParagraphsTestBase {

  protected static $modules = [
    'node',
    'paragraphs',
    'field',
    'field_ui',
    'block',
    'paragraphs_test',
  ];

  /**
   * Tests duplicate paragraph feature.
   */
  public function testDuplicateButton() {
    $this->addParagraphedContentType('paragraphed_test');

    $this->loginAsAdmin(['create paragraphed_test content', 'edit any paragraphed_test content']);
    // Add a Paragraph type.
    $paragraph_type = 'text_paragraph';
    $this->addParagraphsType($paragraph_type);

    // Add a text field to the text_paragraph type.
    static::fieldUIAddNewField('admin/structure/paragraphs_type/' . $paragraph_type, 'text', 'Text', 'text_long', [], []);
    $this->drupalGet('node/add/paragraphed_test');
    $this->submitForm([], 'field_paragraphs_text_paragraph_add_more');
    $this->submitForm([], 'field_paragraphs_text_paragraph_add_more');

    // Create a node with a Paragraph.
    $edit = [
      'title[0][value]' => 'paragraphs_mode_test',
      'field_paragraphs[0][subform][field_text][0][value]' => 'A',
      'field_paragraphs[1][subform][field_text][0][value]' => 'B',
      'field_paragraphs[2][subform][field_text][0][value]' => 'C',
    ];
    $this->submitForm($edit, 'Save');
    $node = $this->drupalGetNodeByTitle('paragraphs_mode_test');

    $this->drupalGet('node/' . $node->id() . '/edit');

    // Click "Duplicate" button on A and move C to the first position.
    $edit = ['field_paragraphs[2][_weight]' => -1];
    $this->submitForm($edit, 'field_paragraphs_0_duplicate');
    $this->assertSession()->fieldValueEquals('field_paragraphs[0][subform][field_text][0][value]', 'A');
    $this->assertSession()->fieldValueEquals('field_paragraphs[0][_weight]', 1);
    $this->assertSession()->fieldValueEquals('field_paragraphs[1][subform][field_text][0][value]', 'B');
    $this->assertSession()->fieldValueEquals('field_paragraphs[1][_weight]', 3);
    $this->assertSession()->fieldValueEquals('field_paragraphs[2][subform][field_text][0][value]', 'C');
    $this->assertSession()->fieldValueEquals('field_paragraphs[2][_weight]', 0);
    $this->assertSession()->fieldValueEquals('field_paragraphs[3][subform][field_text][0][value]', 'A');
    $this->assertSession()->fieldValueEquals('field_paragraphs[3][_weight]', 2);

    // Move C after the A's and save.
    $edit = [
      'field_paragraphs[0][_weight]' => -2,
      'field_paragraphs[1][_weight]' => 2,
      'field_paragraphs[2][_weight]' => 1,
      'field_paragraphs[3][_weight]' => -1,
    ];

    // Save and check if all paragraphs are present in the correct order.
    $this->submitForm($edit, 'Save');
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->assertSession()->fieldValueEquals('field_paragraphs[0][subform][field_text][0][value]', 'A');
    $this->assertSession()->fieldValueEquals('field_paragraphs[1][subform][field_text][0][value]', 'A');
    $this->assertSession()->fieldValueEquals('field_paragraphs[2][subform][field_text][0][value]', 'C');
    $this->assertSession()->fieldValueEquals('field_paragraphs[3][subform][field_text][0][value]', 'B');

    // Delete the second A, then duplicate C.
    $this->submitForm([], 'field_paragraphs_1_remove');
    $this->submitForm([], 'field_paragraphs_2_duplicate');
    $this->submitForm([], 'Save');

    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->assertSession()->fieldValueEquals('field_paragraphs[0][subform][field_text][0][value]', 'A');
    $this->assertSession()->fieldValueEquals('field_paragraphs[1][subform][field_text][0][value]', 'C');
    $this->assertSession()->fieldValueEquals('field_paragraphs[2][subform][field_text][0][value]', 'C');
    $this->assertSession()->fieldValueEquals('field_paragraphs[3][subform][field_text][0][value]', 'B');
    // Check that the duplicate action is present.
    $this->assertSession()->buttonExists('field_paragraphs_0_duplicate');

    // Disable show duplicate action.
    $this->drupalGet('admin/structure/types/manage/paragraphed_test/form-display');
    $this->assertSession()->pageTextContains('Features: Duplicate, Collapse / Edit all');
    $this->submitForm([], 'field_paragraphs_settings_edit');
    $this->submitForm(['fields[field_paragraphs][settings_edit_form][settings][features][duplicate]' => FALSE], 'Update');
    $this->assertSession()->pageTextContains('Features: Collapse / Edit all');
    $this->submitForm([], 'Save');
    $this->drupalGet('node/' . $node->id() . '/edit');
    // Check that the duplicate action is not present.
    $this->assertSession()->buttonNotExists('field_paragraphs_0_duplicate');
    $this->assertSession()->fieldValueEquals('field_paragraphs[0][subform][field_text][0][value]', 'A');

    // Enable show duplicate action.
    $this->drupalGet('admin/structure/types/manage/paragraphed_test/form-display');
    $this->assertSession()->pageTextContains('Features: Collapse / Edit all');
    $this->submitForm([], 'field_paragraphs_settings_edit');
    $this->submitForm(['fields[field_paragraphs][settings_edit_form][settings][features][duplicate]' => TRUE], 'Update');
    $this->assertSession()->pageTextContains('Features: Duplicate, Collapse / Edit all');
    $this->submitForm([], 'Save');
    $this->drupalGet('node/' . $node->id() . '/edit');
    // Check that the duplicate action is present.
    $this->assertSession()->buttonExists('field_paragraphs_0_duplicate');
    $this->assertSession()->fieldValueEquals('field_paragraphs[0][subform][field_text][0][value]', 'A');
  }

  /**
   * Tests duplicate paragraph feature with nested paragraphs.
   */
  public function testDuplicateButtonWithNesting() {
    $this->addParagraphedContentType('paragraphed_test');

    $this->loginAsAdmin(['create paragraphed_test content', 'edit any paragraphed_test content']);
    // Add nested Paragraph type.
    $nested_paragraph_type = 'nested_paragraph';
    $this->addParagraphsType($nested_paragraph_type);
    // Add text Paragraph type.
    $paragraph_type = 'text';
    $this->addParagraphsType($paragraph_type);

    // Add a text field to the text_paragraph type.
    static::fieldUIAddNewField('admin/structure/paragraphs_type/' . $paragraph_type, 'text', 'Text', 'text_long', [], []);

    // Add a ERR paragraph field to the nested_paragraph type.
    static::fieldUIAddNewField('admin/structure/paragraphs_type/' . $nested_paragraph_type, 'nested', 'Nested', 'field_ui:entity_reference_revisions:paragraph', [
      'settings[target_type]' => 'paragraph',
      'cardinality' => '-1',
    ], []);
    $this->drupalGet('node/add/paragraphed_test');
    $this->submitForm([], 'field_paragraphs_nested_paragraph_add_more');

    // Create a node with a Paragraph.
    $edit = [
      'title[0][value]' => 'paragraphs_mode_test',
    ];
    $this->submitForm($edit, 'Save');
    $node = $this->drupalGetNodeByTitle('paragraphs_mode_test');

    // Add a text field to nested paragraph.
    $text = 'recognizable_text';
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->submitForm([], 'field_paragraphs_0_subform_field_nested_text_add_more');
    $edit = [
      'field_paragraphs[0][subform][field_nested][0][subform][field_text][0][value]' => $text,
    ];
    $this->submitForm($edit, 'Save');

    // Switch mode to closed.
    $this->setParagraphsWidgetMode('paragraphed_test', 'field_paragraphs', 'closed');
    $this->drupalGet('node/' . $node->id() . '/edit');

    // Click "Duplicate" button.
    $this->submitForm([], 'field_paragraphs_0_duplicate');
    $this->submitForm([], 'field_paragraphs_0_edit');
    $this->assertSession()->fieldValueEquals('field_paragraphs[0][subform][field_nested][0][subform][field_text][0][value]', $text);
    $this->assertSession()->fieldValueEquals('field_paragraphs[1][subform][field_nested][0][subform][field_text][0][value]', $text);

    // Change the text paragraph value of duplicated nested paragraph.
    $second_paragraph_text = 'duplicated_text';
    $edit = [
      'field_paragraphs[1][subform][field_nested][0][subform][field_text][0][value]' => $second_paragraph_text,
    ];

    // Save and check if the changed text paragraph value of the duplicated
    // paragraph is not the same as in the original paragraph.
    $this->submitForm($edit, 'Save');

    $page_text = $this->getSession()->getPage()->getText();
    $text_nr_found = substr_count($page_text, $text);
    $this->assertSame(1, $text_nr_found);

    $page_text = $this->getSession()->getPage()->getText();
    $second_paragraph_text_nr_found = substr_count($page_text, $second_paragraph_text);
    $this->assertSame(1, $second_paragraph_text_nr_found);

  }

  /**
   * Tests duplicate paragraph feature for fields with a limited cardinality.
   */
  public function testDuplicateButtonWithLimitedCardinality() {
    $this->addParagraphedContentType('paragraphed_test');
    /** @var \Drupal\field\FieldStorageConfigInterface $field_storage */
    $field_storage = FieldStorageConfig::load('node.field_paragraphs');
    $field_storage->setCardinality(2)->save();

    $this->loginAsAdmin(['create paragraphed_test content', 'edit any paragraphed_test content']);
    // Add a Paragraph type.
    $paragraph_type = 'text_paragraph';
    $this->addParagraphsType($paragraph_type);

    // Add a text field to the text_paragraph type.
    static::fieldUIAddNewField('admin/structure/paragraphs_type/' . $paragraph_type, 'text', 'Text', 'text_long', [], []);
    $this->drupalGet('node/add/paragraphed_test');
    $this->submitForm([], 'field_paragraphs_text_paragraph_add_more');

    $edit = [
      'title[0][value]' => 'paragraphs_mode_test',
      'field_paragraphs[0][subform][field_text][0][value]' => 'A',
      'field_paragraphs[1][subform][field_text][0][value]' => 'B',
    ];
    $this->submitForm($edit, 'Save');
    $node = $this->drupalGetNodeByTitle('paragraphs_mode_test');

    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->assertSession()->fieldNotExists('field_paragraphs_0_duplicate');
    $this->assertSession()->fieldNotExists('field_paragraphs_1_duplicate');
  }

}
