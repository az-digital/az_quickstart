<?php

namespace Drupal\Tests\paragraphs\Functional\WidgetLegacy;

/**
 * Tests paragraphs widget buttons.
 *
 * @group paragraphs
 */
class ParagraphsWidgetButtonsTest extends ParagraphsTestBase {

  /**
   * Tests the widget buttons of paragraphs.
   */
  public function testWidgetButtons() {
    $this->addParagraphedContentType('paragraphed_test', 'field_paragraphs', 'entity_reference_paragraphs');

    $this->loginAsAdmin(['create paragraphed_test content', 'edit any paragraphed_test content']);
    // Add a Paragraph type.
    $paragraph_type = 'text_paragraph';
    $this->addParagraphsType($paragraph_type);
    $this->addParagraphsType('text');

    // Add a text field to the text_paragraph type.
    static::fieldUIAddNewField('admin/structure/paragraphs_type/' . $paragraph_type, 'text', 'Text', 'text_long', [], []);
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

    // Test the 'Open' mode.
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->assertSession()->fieldValueEquals('field_paragraphs[0][subform][field_text][0][value]', $text);
    $this->submitForm([], 'Save');
    $this->assertSession()->pageTextContains($text);

    // Test the 'Closed' mode.
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
    $page_text = $this->getSession()->getPage()->getText();
    $nr_found = substr_count($page_text, 'You have unsaved changes on this Paragraph item.');
    $this->assertGreaterThan(1, $nr_found);
    $this->assertSession()->responseContains('<span class="summary-content">' . $closed_mode_text);
    $this->submitForm([], 'Save');
    $this->assertSession()->pageTextContains('paragraphed_test ' . $node->label() . ' has been updated.');
    $this->assertSession()->pageTextContains($closed_mode_text);

    // Test the 'Preview' mode.
    $this->setParagraphsWidgetMode('paragraphed_test', 'field_paragraphs', 'preview');
    $this->drupalGet('node/' . $node->id() . '/edit');
    // Click "Edit" button.
    $this->submitForm([], 'field_paragraphs_0_edit');
    $this->assertSession()->fieldValueEquals('field_paragraphs[0][subform][field_text][0][value]', $closed_mode_text);
    $preview_mode_text = 'preview_mode_text';
    $edit = ['field_paragraphs[0][subform][field_text][0][value]' => $preview_mode_text];
    // Click "Collapse" button.
    $this->submitForm($edit, 'field_paragraphs_0_collapse');
    $this->assertSession()->pageTextContains('You have unsaved changes on this Paragraph item.');
    $this->assertSession()->pageTextContains($preview_mode_text);
    $this->submitForm([], 'Save');
    $this->assertSession()->pageTextContains('paragraphed_test ' . $node->label() . ' has been updated.');
    $this->assertSession()->pageTextContains($preview_mode_text);

    // Test the remove/restore function.
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->assertSession()->pageTextContains($preview_mode_text);
    // Click "Remove" button.
    $this->submitForm([], 'field_paragraphs_0_remove');
    $this->assertSession()->pageTextContains('Deleted Paragraph: text_paragraph');
    // Click "Restore" button.
    $this->submitForm([], 'field_paragraphs_0_restore');
    $this->assertSession()->fieldValueEquals('field_paragraphs[0][subform][field_text][0][value]', $preview_mode_text);
    $restore_text = 'restore_text';
    $edit = ['field_paragraphs[0][subform][field_text][0][value]' => $restore_text];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('paragraphed_test ' . $node->label() . ' has been updated.');
    $this->assertSession()->pageTextContains($restore_text);

    // Test the remove/confirm remove function.
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->assertSession()->pageTextContains($restore_text);
    // Click "Remove" button.
    $this->submitForm([], 'field_paragraphs_0_remove');
    $this->assertSession()->pageTextContains('Deleted Paragraph: text_paragraph');
    // Click "Confirm Removal" button.
    $this->submitForm([], 'field_paragraphs_0_confirm_remove');
    $this->submitForm([], 'Save');
    $this->assertSession()->pageTextContains('paragraphed_test ' . $node->label() . ' has been updated.');
    $this->assertSession()->pageTextNotContains($restore_text);
  }

}
