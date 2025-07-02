<?php

namespace Drupal\Tests\paragraphs\Functional\WidgetStable;

use Drupal\language\Entity\ConfigurableLanguage;
use Symfony\Component\CssSelector\CssSelectorConverter;

/**
 * Tests collapse all button.
 *
 * @group paragraphs
 */
class ParagraphsHeaderActionsTest extends ParagraphsTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = array(
    'content_translation',
  );

  /**
   * Tests header actions.
   */
  public function testHeaderActions() {

    // To test with a single header action, ensure the drag and drop action is
    // shown, even without the library.
    \Drupal::state()->set('paragraphs_test_dragdrop_force_show', TRUE);

    $this->addParagraphedContentType('paragraphed_test');

    $this->loginAsAdmin([
      'create paragraphed_test content',
      'edit any paragraphed_test content',
    ]);

    // Add a Paragraph type.
    $paragraph_type = 'text_paragraph';
    $this->addParagraphsType($paragraph_type);

    // Add a text field to the text_paragraph type.
    static::fieldUIAddNewField(
      'admin/structure/paragraphs_type/' . $paragraph_type,
      'text',
      'Text',
      'text_long',
      [],
      []
    );

    // Add 2 paragraphs and check for Collapse/Edit all button.
    $this->drupalGet('node/add/paragraphed_test');
    $this->assertSession()->responseNotContains('field_paragraphs_collapse_all');
    $this->assertSession()->responseNotContains('field_paragraphs_edit_all');
    $this->assertSession()->responseContains('field_paragraphs_dragdrop_mode');

    // Ensure there is only a single table row.
    $table_rows = $this->xpath('//table[contains(@class, :class)]/tbody/tr', [':class' => 'field-multiple-table']);
    $this->assertEquals(1, count($table_rows));

    // Add second paragraph and check for Collapse/Edit all button.
    $this->submitForm([], 'field_paragraphs_text_paragraph_add_more');
    $this->assertSession()->responseContains('field_paragraphs_collapse_all');
    $this->assertSession()->responseContains('field_paragraphs_edit_all');

    $edit = [
      'field_paragraphs[0][subform][field_text][0][value]' => 'First text',
      'field_paragraphs[1][subform][field_text][0][value]' => 'Second text',
    ];
    $this->submitForm($edit, 'Collapse all');

    // Checks that after collapsing all we can edit again these paragraphs.
    $this->assertSession()->responseContains('field_paragraphs_0_edit');
    $this->assertSession()->responseContains('field_paragraphs_1_edit');

    // Test Edit all button.
    $this->submitForm([], 'field_paragraphs_edit_all');
    $this->assertSession()->responseContains('field_paragraphs_0_collapse');
    $this->assertSession()->responseContains('field_paragraphs_1_collapse');

    $edit = [
      'title[0][value]' => 'Test',
    ];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('paragraphed_test Test has been created.');

    $node = $this->getNodeByTitle('Test');
    $this->drupalGet('node/' . $node->id());
    $this->clickLink('Edit');
    $this->assertSession()->pageTextNotContains('No Paragraph added yet.');

    // Add and remove another paragraph.
    $this->submitForm([], 'field_paragraphs_text_paragraph_add_more');
    $edit = [
      'field_paragraphs[2][subform][field_text][0][value]' => 'Third text',
    ];
    $this->submitForm($edit, 'field_paragraphs_2_remove');

    // Check that pressing "Collapse all" does not restore the removed
    // paragraph.
    $this->submitForm([], 'field_paragraphs_edit_all');
    $this->assertSession()->pageTextContains('First text');
    $this->assertSession()->pageTextContains('Second text');
    $this->assertSession()->pageTextNotContains('Third text');

    // Check that pressing "Edit all" does not restore the removed paragraph,
    // either.
    $this->submitForm([], 'field_paragraphs_collapse_all');
    $this->assertSession()->pageTextContains('First text');
    $this->assertSession()->pageTextContains('Second text');
    $this->assertSession()->pageTextNotContains('Third text');
    $this->assertSession()->buttonExists('field_paragraphs_collapse_all');
    $this->assertSession()->buttonExists('field_paragraphs_edit_all');
    $this->submitForm([], 'Save');

    // Check that the drag and drop button is present when there is a paragraph
    // and that it is not shown when the paragraph is deleted.
    $this->drupalGet('node/add/paragraphed_test');
    $this->assertSession()->responseContains('name="field_paragraphs_dragdrop_mode"');
    $this->submitForm([], 'field_paragraphs_0_remove');
    $this->assertSession()->responseNotContains('name="field_paragraphs_dragdrop_mode"');

    // Disable show multiple actions.
    $this->drupalGet('admin/structure/types/manage/paragraphed_test/form-display');
    $this->submitForm([], 'field_paragraphs_settings_edit');
    $this->submitForm(['fields[field_paragraphs][settings_edit_form][settings][features][collapse_edit_all]' => FALSE], 'Update');
    $this->submitForm([], 'Save');
    $this->drupalGet('node/' . $node->id() . '/edit');
    // Check that the collapse/edit all actions are not present.
    $this->assertSession()->buttonNotExists('field_paragraphs_collapse_all');
    $this->assertSession()->buttonNotExists('field_paragraphs_edit_all');
    $this->assertSession()->fieldValueEquals('field_paragraphs[0][subform][field_text][0][value]', 'First text');

    // Enable show "Collapse / Edit all" actions.
    $this->drupalGet('admin/structure/types/manage/paragraphed_test/form-display');
    $this->submitForm([], 'field_paragraphs_settings_edit');
    $this->submitForm(['fields[field_paragraphs][settings_edit_form][settings][features][collapse_edit_all]' => TRUE], 'Update');
    $this->submitForm([], 'Save');
    $this->drupalGet('node/' . $node->id() . '/edit');
    // Check that the collapse/edit all actions are present.
    $this->assertSession()->buttonExists('field_paragraphs_collapse_all');
    $this->assertSession()->buttonExists('field_paragraphs_edit_all');
    $this->assertSession()->fieldValueEquals('field_paragraphs[0][subform][field_text][0][value]', 'First text');
  }

  /**
   * Tests that header actions works fine with nesting.
   */
  public function testHeaderActionsWithNesting() {
    $this->addParagraphedContentType('paragraphed_test');

    $this->loginAsAdmin([
      'create paragraphed_test content',
      'edit any paragraphed_test content',
    ]);

    // Add Paragraph types.
    $nested_paragraph_type = 'nested_paragraph';
    $this->addParagraphsType($nested_paragraph_type);
    $paragraph_type = 'text';
    $this->addParagraphsType($paragraph_type);

    // Add a text field to the text_paragraph type.
    static::fieldUIAddNewField(
      'admin/structure/paragraphs_type/' . $paragraph_type,
      'text',
      'Text',
      'text_long',
      [],
      []
    );

    // Add a ERR paragraph field to the nested_paragraph type.
    static::fieldUIAddNewField(
      'admin/structure/paragraphs_type/' . $nested_paragraph_type,
      'nested',
      'Nested',
      'field_ui:entity_reference_revisions:paragraph', [
        'settings[target_type]' => 'paragraph',
        'cardinality' => '-1',
      ],
      []
    );

    $this->drupalGet('admin/structure/paragraphs_type/nested_paragraph/form-display');
    $this->submitForm(['fields[field_nested][type]' => 'paragraphs'], 'Save');
    $this->setParagraphsWidgetSettings($nested_paragraph_type, 'nested', ['edit_mode' => 'closed'], 'paragraphs', 'paragraph');

    // Checks that Collapse/Edit all button is presented.
    $this->drupalGet('node/add/paragraphed_test');
    $this->submitForm([], 'field_paragraphs_nested_paragraph_add_more');
    $this->submitForm([], 'field_paragraphs_text_add_more');
    $this->assertSession()->responseContains('field_paragraphs_collapse_all');
    $this->assertSession()->responseContains('field_paragraphs_edit_all');

    $this->submitForm([], 'field_paragraphs_text_add_more');
    $this->submitForm([], 'field_paragraphs_0_subform_field_nested_text_add_more');
    $this->assertSession()->responseNotContains('field_paragraphs_0_collapse_all');
    $this->assertSession()->responseNotContains('field_paragraphs_0_edit_all');
    $edit = [
      'field_paragraphs[0][subform][field_nested][0][subform][field_text][0][value]' => 'Nested text',
      'field_paragraphs[1][subform][field_text][0][value]' => 'Second text paragraph',
    ];
    $this->submitForm($edit, 'Collapse all');
    $this->assertSession()->responseContains('field-paragraphs-0-edit');
    $this->assertSession()->elementExists('css', '[name="field_paragraphs_1_edit"] + .paragraphs-dropdown');
    $this->submitForm([], 'field_paragraphs_edit_all');
    $this->assertSession()->responseContains('field-paragraphs-0-collapse');

    $edit = [
      'title[0][value]' => 'Test',
    ];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('paragraphed_test Test has been created.');

    $node = $this->getNodeByTitle('Test');
    $this->drupalGet('node/' . $node->id());
    $this->clickLink('Edit');
    $this->assertSession()->pageTextNotContains('No Paragraph added yet.');

    $this->submitForm([], 'field_paragraphs_0_subform_field_nested_text_add_more');
    $edit = [
      'field_paragraphs[0][subform][field_nested][1][subform][field_text][0][value]' => 'Second nested text',
    ];
    $this->submitForm($edit, 'field_paragraphs_0_collapse');
    $this->submitForm([], 'field_paragraphs_0_edit');
    $this->assertSession()->responseContains('field_paragraphs_0_subform_field_nested_collapse_all');
    $this->assertSession()->responseContains('field_paragraphs_0_subform_field_nested_edit_all');
  }

  /**
   * Tests header actions with multi fields.
   */
  public function testHeaderActionsWithMultiFields() {
    $this->addParagraphedContentType('paragraphed_test');
    $this->loginAsAdmin([
      'create paragraphed_test content',
      'edit any paragraphed_test content',
    ]);
    $this->drupalGet('/admin/structure/types/manage/paragraphed_test/fields/add-field');

    // Add a Paragraph type.
    $paragraph_type = 'text_paragraph';
    $this->addParagraphsType($paragraph_type);
    static::fieldUIAddNewField('admin/structure/types/manage/paragraphed_test', 'second', 'Second paragraph', 'field_ui:entity_reference_revisions:paragraph', [], []);

    // Add a text field to the text_paragraph type.
    static::fieldUIAddNewField(
      'admin/structure/paragraphs_type/' . $paragraph_type,
      'text',
      'Text',
      'text_long',
      [],
      []
    );

    $this->drupalGet('node/add/paragraphed_test');
    $this->submitForm([], 'field_paragraphs_text_paragraph_add_more');
    $this->submitForm([], 'field_second_text_paragraph_add_more');

    // Checks that we have Collapse\Edit all for each field.
    $this->assertSession()->responseContains('field_paragraphs_collapse_all');
    $this->assertSession()->responseContains('field_paragraphs_edit_all');
    $this->assertSession()->responseContains('field_second_collapse_all');
    $this->assertSession()->responseContains('field_second_edit_all');

    $edit = [
      'field_second[0][subform][field_text][0][value]' => 'Second field',
    ];
    $this->submitForm($edit, 'field_second_collapse_all');

    // Checks that we collapsed only children from second field.
    $this->assertSession()->responseNotContains('field_paragraphs_0_edit');
    $this->assertSession()->responseContains('field_second_0_edit');

    $this->submitForm([], 'field_paragraphs_collapse_all');
    $this->assertSession()->responseContains('field_paragraphs_0_edit');
    $this->assertSession()->responseContains('field_second_0_edit');

    $this->submitForm([], 'field_second_edit_all');
    $this->assertSession()->responseContains('field_second_0_collapse');

    $edit = [
      'title[0][value]' => 'Test',
    ];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('paragraphed_test Test has been created.');

    $node = $this->getNodeByTitle('Test');
    $this->drupalGet('node/' . $node->id());
    $this->clickLink('Edit');
    $this->assertSession()->pageTextNotContains('No Paragraph added yet.');
  }

  /**
   * Tests drag and drop button visibility while translating.
   */
  function testHeaderActionsWhileTranslating() {
    // Display drag and drop in tests.
    $this->addParagraphedContentType('paragraphed_test');
    \Drupal::state()->set('paragraphs_test_dragdrop_force_show', TRUE);
    $this->loginAsAdmin([
      'administer site configuration',
      'create paragraphed_test content',
      'edit any paragraphed_test content',
      'delete any paragraphed_test content',
      'administer content translation',
      'translate any entity',
      'create content translations',
      'administer languages',
    ]);
    ConfigurableLanguage::createFromLangcode('es')->save();

    // Enable translation for test content.
    $this->drupalGet('admin/config/regional/content-language');
    $edit = [
      'entity_types[node]' => TRUE,
      'settings[node][paragraphed_test][translatable]' => TRUE,
    ];
    $this->submitForm($edit, 'Save configuration');

    // Add a Paragraph type.
    $paragraph_type = 'text_paragraph';
    $this->addParagraphsType($paragraph_type);
    static::fieldUIAddNewField('admin/structure/paragraphs_type/' . $paragraph_type, 'text', 'Text', 'text_long', [], []);

    $this->drupalGet('node/add/paragraphed_test');
    // Assert that the drag and drop button is present.
    $this->assertSession()->responseContains('name="field_paragraphs_dragdrop_mode"');
    $edit = [
      'title[0][value]' => 'Title',
      'field_paragraphs[0][subform][field_text][0][value]' => 'First',
    ];
    $this->submitForm($edit, 'Save');
    $this->clickLink('Translate');
    $this->clickLink('Add');
    // Assert that the drag and drop button is not present while translating.
    $this->assertSession()->responseNotContains('name="field_paragraphs_dragdrop_mode"');
    $this->assertSession()->pageTextContains('First');
  }

}
