<?php

namespace Drupal\Tests\paragraphs\Functional;

use Drupal\Tests\paragraphs\Functional\WidgetStable\ParagraphsTestBase;

/**
 * Tests the Paragraphs user interface.
 *
 * @group paragraphs
 */
class ParagraphsUiTest extends ParagraphsTestBase {

  /**
   * Modules to enable.
   *
   * @var string[]
   */
  protected static $modules = [
    'node',
    'paragraphs',
    'field',
    'field_ui',
    'block',
  ];

  /**
   * Tests if the paragraph type class is present when added.
   */
  public function testParagraphTypeClass() {
    $this->loginAsAdmin();
    // Add a Paragraphed test content.
    $this->addParagraphedContentType('paragraphed_test', 'paragraphs');

    $this->addParagraphsType('test_paragraph');
    $this->addParagraphsType('text');

    // Add paragraphs to a node and check if their type is present as a class.
    $this->drupalGet('node/add/paragraphed_test');
    $this->getSession()->getPage()->findButton('paragraphs_test_paragraph_add_more')->press();
    $this->assertSession()->responseContains('paragraph-type--test-paragraph');
    $this->getSession()->getPage()->findButton('paragraphs_text_add_more')->press();
    $this->assertSession()->responseContains('paragraph-type--text');
    $this->getSession()->getPage()->findButton('paragraphs_0_remove')->press();
    $this->assertSession()->responseContains('paragraph-type--text');
  }

  /**
   * Test paragraphs summary with markup text.
   */
  public function testSummary() {
    $this->addParagraphedContentType('paragraphed_test', 'paragraphs');
    $this->addParagraphsType('text');
    $this->addFieldtoParagraphType('text', 'field_text_demo', 'text');
    $this->loginAsAdmin(['edit any paragraphed_test content']);
    $settings = [
      'edit_mode' => 'closed',
      'closed_mode' => 'summary',
    ];
    $this->setParagraphsWidgetSettings('paragraphed_test', 'paragraphs', $settings, 'paragraphs');
    // Create a node and add a paragraph.
    $this->drupalGet('node/add/paragraphed_test');
    $this->getSession()->getPage()->findButton('paragraphs_text_add_more')->press();
    $edit = [
      'title[0][value]' => 'Llama test',
      'paragraphs[0][subform][field_text_demo][0][value]' => '<iframe src="https://www.llamatest.neck"></iframe>',
    ];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('paragraphed_test Llama test has been created.');
    // Assert that the summary contains the html text.
    $node = $this->getNodeByTitle('Llama test');
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->assertSession()->pageTextContains('<iframe src="https://www.llamatest.neck');
    $this->assertSession()->responseContains('class="paragraphs-description paragraphs-collapsed-description"><div class="paragraphs-content-wrapper"><span class="summary-content">&lt;iframe src=');
    // Assert that the summary keeps showing html even with longer html.
    $this->getSession()->getPage()->pressButton('paragraphs_0_edit');
    $edit = [
      'paragraphs[0][subform][field_text_demo][0][value]' => '<iframe src="https://www.llamatest.neck" class="this-is-a-pretty-long-class-that-needs-to-be-really-long-for-testing-purposes-so-we-have-a-better-summary-test-and-it-has-exactly-144-characters"></iframe>',
    ];
    $this->submitForm($edit, 'Save');
    $this->assertSession()->pageTextContains('paragraphed_test Llama test has been updated.');
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->assertSession()->pageTextContains('<iframe src="https://www.llamatest.neck" class="this-is-a-pretty-long-class-that-needs-to-be-really-long-for-testing-purposes-so-we-');
    $this->assertSession()->responseContains('class="paragraphs-description paragraphs-collapsed-description"><div class="paragraphs-content-wrapper"><span class="summary-content">&lt;iframe src=');
    // Asset that the summary does not display markup even when we have long
    // html.
    $this->getSession()->getPage()->pressButton('paragraphs_0_edit');
    $edit = [
      'paragraphs[0][subform][field_text_demo][0][value]' => '<iframe src="https://www.llamatest.neck" class="this-is-a-pretty-long-class-that-needs-to-be-really-long-for-testing-purposes-so-we-have-a-better-summary-test-and-it-has-exactly-144-characters"></iframe><h1>This is a title</h1>',
    ];
    $this->submitForm($edit, 'Save');
    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->assertSession()->responseContains('class="paragraphs-description paragraphs-collapsed-description"><div class="paragraphs-content-wrapper"><span class="summary-content">This is a title');
  }

  /**
   * Test the default paragraphs widget used.
   */
  public function testDefaultWidget() {
    $this->loginAsAdmin();

    // Create a new content type.
    $this->drupalGet('admin/structure/types/add');
    $this->submitForm([
      'name' => 'Test',
      'type' => 'test',
    ], 'Save and manage fields');

    // Add a new paragraphs field to the content type.
    $this->clickLink('Create a new field');

    $this->getSession()->getPage()->fillField('new_storage_type', 'field_ui:entity_reference_revisions:paragraph');
    if ($this->coreVersion('10.3')) {
      $this->getSession()->getPage()->pressButton('Continue');
    }
    $edit = [
      'label' => 'Paragraph',
      'field_name' => 'paragraph',
    ];
    $this->submitForm($edit, 'Continue');
    $this->submitForm([], 'Save settings');

    // Visit the "Manage form display" page of the new content type.
    $this->drupalGet('admin/structure/types/manage/test/form-display');

    // The selected widget should be "paragraphs".
    $this->assertSession()->fieldValueEquals('fields[field_paragraph][type]', 'paragraphs');
  }

}
