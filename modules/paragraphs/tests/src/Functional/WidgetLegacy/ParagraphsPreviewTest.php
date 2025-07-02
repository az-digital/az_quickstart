<?php

namespace Drupal\Tests\paragraphs\Functional\WidgetLegacy;

/**
 * Tests the configuration of paragraphs.
 *
 * @group paragraphs
 */
class ParagraphsPreviewTest extends ParagraphsTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = array(
    'image',
  );

  /**
   * Tests the revision of paragraphs.
   */
  public function testParagraphsPreview() {
    // Create paragraph type Headline + Block.
    $this->addParagraphedContentType('article', 'field_paragraphs', 'entity_reference_paragraphs');
    $this->loginAsAdmin([
      'administer node display',
      'create article content',
      'edit any article content',
      'delete any article content',
    ]);

    // Create paragraph type Headline + Block.
    $this->addParagraphsType('text');
    // Create field types for the text.
    $this->fieldUIAddNewField('admin/structure/paragraphs_type/text', 'text', 'Text', 'text', array(), array());
    $this->assertSession()->pageTextContains('Saved Text configuration.');

    $test_text_1 = 'dummy_preview_text_1';
    $test_text_2 = 'dummy_preview_text_2';
    // Create node with two paragraphs.
    $this->drupalGet('node/add/article');
    $this->submitForm(array(), 'field_paragraphs_text_add_more');
    // Set the value of the paragraphs.
    $edit = [
      'title[0][value]' => 'Page_title',
      'field_paragraphs[0][subform][field_text][0][value]' => $test_text_1,
    ];
    // Preview the article.
    $this->submitForm($edit, 'Preview');
    // Check if the text is displayed.
    $this->assertSession()->responseContains($test_text_1);

    // Check that the parent is set correctly on all paragraphs.
    $this->assertSession()->pageTextNotContains('Parent: //');
    $page_text = $this->getSession()->getPage()->getText();
    $nr_found = substr_count($page_text, 'Parent: node//field_paragraphs');
    $this->assertGreaterThan(1, $nr_found);

    // Go back to the editing form.
    $this->clickLink('Back to content editing');

    $paragraph_1 = $this->xpath('//*[@id="edit-field-paragraphs-0-subform-field-text-0-value"]')[0];
    $this->assertEquals($paragraph_1->getValue(), $test_text_1);

    $this->submitForm($edit, 'Save');

    $this->clickLink('Edit');
    $this->submitForm(array(), 'field_paragraphs_text_add_more');
    $edit = [
      'field_paragraphs[1][subform][field_text][0][value]' => $test_text_2,
    ];
    // Preview the article.
    $this->submitForm($edit, 'Preview');
    $this->assertSession()->responseContains($test_text_1);
    $this->assertSession()->responseContains($test_text_2);

    // Go back to the editing form.
    $this->clickLink('Back to content editing');
    $new_test_text_2 = 'less_dummy_preview_text_2';

    $edit = [
      'field_paragraphs[1][subform][field_text][0][value]' => $new_test_text_2,
    ];
    // Preview the article.
    $this->submitForm($edit, 'Preview');
    $this->assertSession()->responseContains($test_text_1);
    $this->assertSession()->responseContains($new_test_text_2);

    // Check that the parent is set correctly on all paragraphs.
    $this->assertSession()->pageTextNotContains('Parent: //');
    $page_text = $this->getSession()->getPage()->getText();
    $nr_found = substr_count($page_text, 'Parent: node/1/field_paragraphs');
    $this->assertGreaterThan(1, $nr_found);

    // Go back to the editing form.
    $this->clickLink('Back to content editing');
    $paragraph_1 = $this->xpath('//*[@id="edit-field-paragraphs-0-subform-field-text-0-value"]')[0];
    $paragraph_2 = $this->xpath('//*[@id="edit-field-paragraphs-1-subform-field-text-0-value"]')[0];
    $this->assertEquals($paragraph_1->getValue(), $test_text_1);
    $this->assertEquals($paragraph_2->getValue(), $new_test_text_2);
    $this->submitForm([], 'Save');

    $this->assertSession()->responseContains($test_text_1);
    $this->assertSession()->responseContains($new_test_text_2);
    $this->assertSession()->responseContains('Page_title');
  }

}
