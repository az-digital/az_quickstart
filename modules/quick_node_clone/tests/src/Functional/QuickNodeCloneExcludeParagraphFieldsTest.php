<?php

namespace Drupal\Tests\quick_node_clone\Functional;

use Drupal\Tests\field_ui\Traits\FieldUiTestTrait;
use Drupal\Tests\paragraphs\Functional\WidgetLegacy\ParagraphsTestBase;
use Drupal\Tests\paragraphs\FunctionalJavascript\ParagraphsTestBaseTrait;

/**
 * Tests node cloning excluding paragraph fields.
 *
 * @group Quick Node Clone
 */
class QuickNodeCloneExcludeParagraphFieldsTest extends ParagraphsTestBase {

  use FieldUiTestTrait, ParagraphsTestBaseTrait;

  /**
   * The installation profile to use with this test.
   *
   * @var string
   */
  protected $profile = 'standard';

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['paragraphs', 'quick_node_clone'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Crete paragraphs.
    $this->createParagraphs();
  }

  /**
   * Creates the paragraphs used by the tests.
   */
  protected function createParagraphs() {
    $this->addParagraphedContentType('paragraphed_test', 'field_paragraphs', 'entity_reference_paragraphs');

    $this->loginAsAdmin([
      'create paragraphed_test content',
      'edit any paragraphed_test content',
    ]);

    $paragraph_type = 'text_paragraph';
    $this->addParagraphsType($paragraph_type);
    $this->addParagraphsType('text');

    // Add two text fields to the text_paragraph type.
    static::fieldUIAddNewField('admin/structure/paragraphs_type/' . $paragraph_type, 'text1', 'Text 1', 'string', [], []);
    static::fieldUIAddNewField('admin/structure/paragraphs_type/' . $paragraph_type, 'text2', 'Text 2', 'string', [], []);
    $this->drupalGet('node/add/paragraphed_test');
    $this->submitForm([], 'field_paragraphs_text_paragraph_add_more');

    // Add config to exclude text 2 field.
    \Drupal::configFactory()->getEditable('quick_node_clone.settings')
      ->set('exclude.paragraph.' . $paragraph_type, ['field_text2'])
      ->save();
  }

  /**
   * Test node clone excluding fields.
   */
  public function testNodeCloneExcludeParagraphFields() {

    $this->loginAsAdmin([
      'create paragraphed_test content',
      'edit any paragraphed_test content',
      'Administer Quick Node Clone Settings',
      'clone paragraphed_test content',
    ]);

    // Test the form.
    $this->drupalGet('admin/config/quick-node-clone-paragraph');
    $this->assertSession()->fieldValueEquals('text_paragraph[field_text2]', 'field_text2');

    // Create a node and add two Paragraphs.
    $this->drupalGet('node/add/paragraphed_test');
    $title_value = 'The Original Page';
    $text1 = 'This is text 1';
    $text2 = 'This is text 2';
    $edit = [
      'title[0][value]' => $title_value,
      'field_paragraphs[0][subform][field_text1][0][value]' => $text1,
      'field_paragraphs[0][subform][field_text2][0][value]' => $text2,
    ];
    $this->submitForm([], 'field_paragraphs_text_paragraph_add_more');
    $this->submitForm($edit, 'Save');
    $node = $this->drupalGetNodeByTitle($title_value);

    $this->drupalGet('node/' . $node->id() . '/edit');
    $this->assertSession()->fieldValueEquals('field_paragraphs[0][subform][field_text1][0][value]', $text1);
    $this->assertSession()->fieldValueEquals('field_paragraphs[0][subform][field_text2][0][value]', $text2);
    $this->submitForm([], 'Save');
    $this->assertSession()->pageTextContains($text1);
    $this->assertSession()->pageTextContains($text2);

    // Clone node.
    $this->clickLink('Clone');
    $this->drupalGet('clone/' . $node->id() . '/quick_clone');
    $this->assertSession()->fieldValueEquals('field_paragraphs[0][subform][field_text1][0][value]', $text1);
    $this->assertSession()->fieldValueEquals('field_paragraphs[0][subform][field_text2][0][value]', '');
    $this->drupalGet('clone/' . $node->id() . '/quick_clone');
    $this->submitForm([], 'Save');
    $this->assertSession()->responseContains('Clone of ' . $title_value);

    // Make sure text_2 paragraph was cloned.
    $this->assertSession()->pageTextContains($text1);

    // Make sure text_2 paragraph was not cloned.
    $this->assertSession()->pageTextNotContains($text2);
  }

}
