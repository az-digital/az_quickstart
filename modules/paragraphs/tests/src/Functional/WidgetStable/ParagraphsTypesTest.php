<?php

namespace Drupal\Tests\paragraphs\Functional\WidgetStable;

/**
 * Tests paragraphs types.
 *
 * @group paragraphs
 */
class ParagraphsTypesTest extends ParagraphsTestBase {

  /**
   * Tests the deletion of Paragraphs types.
   */
  public function testRemoveTypesWithContent() {
    // Add a Paragraphed test content.
    $this->addParagraphedContentType('paragraphed_test', 'paragraphs');
    $this->loginAsAdmin(['edit any paragraphed_test content']);

    $this->addParagraphsType('paragraph_type_test');
    $this->addParagraphsType('text');

    // Attempt to delete the content type not used yet.
    $this->drupalGet('admin/structure/paragraphs_type');
    $this->clickLink('Delete');
    $this->assertSession()->pageTextContains('This action cannot be undone.');
    $this->clickLink('Cancel');

    // Add a test node with a Paragraph.
    $this->drupalGet('node/add/paragraphed_test');
    $this->submitForm([], 'paragraphs_paragraph_type_test_add_more');
    $edit = ['title[0][value]' => 'test_node'];
    $table_rows = $this->xpath('//table[contains(@class, :class)]/tbody/tr', [':class' => 'field-multiple-table']);
    $this->assertEquals(1, count($table_rows));
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('paragraphed_test test_node has been created.');

    // Attempt to delete the paragraph type already used.
    $this->drupalGet('admin/structure/paragraphs_type');
    $this->clickLink('Delete');
    $this->assertSession()->pageTextContains('paragraph_type_test Paragraphs type is used by 1 piece of content on your site. You can not remove this paragraph_type_test Paragraphs type until you have removed all from the content.');

    // Delete all entities of that Paragraph type.
    $this->submitForm([], 'Delete existing Paragraph');
    $this->assertSession()->pageTextContains('Entity is successfully deleted.');
    $node = $this->drupalGetNodeByTitle('test_node');
    $this->drupalGet('node/' . $node->id() . '/edit');
    $table_rows = $this->xpath('//table[contains(@class, :class)]/tbody/tr', [':class' => 'field-multiple-table']);
    $this->assertEquals(0, count($table_rows));

    // @todo Remove this when https://www.drupal.org/node/2846549 is resolved.
    $this->submitForm($edit, 'Save');
    $this->drupalGet('node/' . $node->id() . '/edit');

    // Add two different Paragraphs to the node.
    $this->submitForm([], 'paragraphs_paragraph_type_test_add_more');
    $this->submitForm([], 'paragraphs_text_add_more');
    $table_rows = $this->xpath('//table[contains(@class, :class)]/tbody/tr', [':class' => 'field-multiple-table']);
    $this->assertEquals(2, count($table_rows));
    $this->submitForm([], 'Save');
    // Attempt to delete the Paragraph type.
    $this->drupalGet('admin/structure/paragraphs_type');
    $this->clickLink('Delete');
    $this->assertSession()->pageTextContains('paragraph_type_test Paragraphs type is used by 1 piece of content on your site. You can not remove this paragraph_type_test Paragraphs type until you have removed all from the content.');
    $this->submitForm([], 'Delete existing Paragraph');
    $this->assertSession()->pageTextContains('Entity is successfully deleted.');
    $this->submitForm([], 'Delete');
    // Check that the Paragraph of the deleted type is removed and the rest
    // remains.
    $node = $this->drupalGetNodeByTitle('test_node');
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->assertSession()->pageTextNotContains('paragraph_type_test');
    $table_rows = $this->xpath('//table[contains(@class, :class)]/tbody/tr', [':class' => 'field-multiple-table']);
    $this->assertEquals(1, count($table_rows));
  }

  /**
   * Tests creating paragraph type.
   */
  public function testCreateParagraphType() {
    $this->loginAsAdmin();

    // Add a paragraph type.
    $this->drupalGet('/admin/structure/paragraphs_type/add');

    // Create a paragraph type with label and id more than 32 characters.
    $edit = [
      'label' => 'Test',
      'id' => 'test_name_with_more_than_32_characters'
    ];
    $this->submitForm($edit, 'Save and manage fields');
    $this->assertSession()->pageTextContains('Machine-readable name cannot be longer than 32 characters but is currently 38 characters long.');
    $edit['id'] = 'new_test_id';
    $this->submitForm($edit, 'Save and manage fields');
    $this->assertSession()->pageTextContains('Saved the Test Paragraphs type.');
  }
}
