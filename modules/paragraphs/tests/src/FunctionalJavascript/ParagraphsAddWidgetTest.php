<?php

namespace Drupal\Tests\paragraphs\FunctionalJavascript;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\field_ui\Traits\FieldUiTestTrait;
use Drupal\Tests\paragraphs\Traits\ParagraphsCoreVersionUiTestTrait;

/**
 * Test paragraphs user interface.
 *
 * @group paragraphs
 */
class ParagraphsAddWidgetTest extends WebDriverTestBase {

  use LoginAdminTrait;
  use FieldUiTestTrait;
  use ParagraphsTestBaseTrait;
  use ParagraphsCoreVersionUiTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'node',
    'paragraphs_test',
    'paragraphs',
    'field',
    'field_ui',
    'block',
    'link',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->placeDefaultBlocks();

  }

  /**
   * Tests the add widget button with modal form.
   */
  public function testAddWidgetButton() {
    $this->addParagraphedContentType('paragraphed_test');
    $this->loginAsAdmin([
      'administer content types',
      'administer node form display',
      'edit any paragraphed_test content',
      'create paragraphed_test content',
    ]);
    // Set the add mode on the content type to modal form widget.
    $this->drupalGet('admin/structure/types/manage/paragraphed_test/form-display');
    $page = $this->getSession()->getPage();
    $page->pressButton('field_paragraphs_settings_edit');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $edit = [
      'fields[field_paragraphs][settings_edit_form][settings][edit_mode]' => 'closed',
      'fields[field_paragraphs][settings_edit_form][settings][add_mode]' => 'modal'
    ];
    $this->submitForm($edit, 'Update');
    $this->assertSession()->assertWaitOnAjaxRequest();

    $this->submitForm([], 'Save');

    // Add a Paragraph type.
    $paragraph_type = 'text_paragraph';
    $this->addParagraphsType($paragraph_type);
    $this->addParagraphsType('text');

    // Add icons to the paragraphs types.
    $icon_one = $this->addParagraphsTypeIcon($paragraph_type);
    $icon_two = $this->addParagraphsTypeIcon('text');

    // Add a text field to the text_paragraph type.
    $this->drupalGet('admin/structure/paragraphs_type/' . $paragraph_type . '/fields/add-field');
    $page->selectFieldOption('new_storage_type', 'formatted_text');
    if ($this->coreVersion('10.3')) {
      $page->pressButton('Continue');
    }
    else {
      $this->assertSession()->waitForElementVisible('css', '#text_long');
    }
    $page->selectFieldOption('group_field_options_wrapper', 'text_long');
    $page->fillField('label', 'Text');
    $this->assertSession()->waitForElementVisible('css', '#edit-name-machine-name-suffix .link');
    $page->pressButton('Edit');
    $page->fillField('field_name', 'text');
    $page->pressButton('Continue');
    $page->pressButton('Save settings');

    // Create paragraph type Nested test.
    $this->addParagraphsType('nested_test');

    $this->drupalGet('/admin/structure/paragraphs_type/nested_test/fields/add-field');
    $page->selectFieldOption('new_storage_type', 'field_ui:entity_reference_revisions:paragraph');
    if ($this->coreVersion('10.3')) {
      $page->pressButton('Continue');
    }
    $page->fillField('label', 'Paragraphs');
    $this->assertSession()->waitForElementVisible('css', '#edit-name-machine-name-suffix .link');
    $page->pressButton('Edit');
    $page->fillField('field_name', 'paragraphs');
    $page->pressButton('Continue');
    $page->pressButton('Save settings');

    // Set the settings for the field in the nested paragraph.
    $component = [
      'type' => 'paragraphs',
      'region' => 'content',
      'settings' => [
        'edit_mode' => 'closed',
        'add_mode' => 'modal',
        'form_display_mode' => 'default',
      ],
    ];
    EntityFormDisplay::load('paragraph.nested_test.default')->setComponent('field_paragraphs', $component)->save();

    // Add a paragraphed test.
    $this->drupalGet('node/add/paragraphed_test');

    // Add a nested paragraph with the add widget.
    $page->pressButton('Add Paragraph');
    $this->assertSession()->elementTextContains('css', '.ui-dialog-title', 'Add Paragraph');
    $paragraphs_dialog = $this->assertSession()->waitForElementVisible('css', 'div.ui-dialog');
    $paragraphs_dialog->pressButton('nested_test');
    $this->assertSession()->assertWaitOnAjaxRequest();

    // Verify that the paragraphs type icons are being displayed.
    $button_one = $this->assertSession()->buttonExists($paragraph_type);
    $button_two = $this->assertSession()->buttonExists('text');
    $this->assertStringContainsString($icon_one->getFilename(), $button_one->getAttribute('style'));
    $this->assertStringContainsString($icon_two->getFilename(), $button_two->getAttribute('style'));

    // Find the add button in the nested paragraph with xpath.
    $element = $this->xpath('//div[contains(@class, "form-item")]/div/div/div[contains(@class, "paragraph-type-add-modal")]/input');
    $element[0]->click();

    // Add a text inside the nested paragraph.
    $page = $this->getSession()->getPage();
    $dialog = $page->find('xpath', '//div[contains(@class, "ui-dialog")]');
    $dialog->pressButton('text');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $edit = [
      'title[0][value]' => 'Example title',
    ];
    $this->submitForm($edit, 'Save');

    // Check the created paragraphed test.
    $this->assertSession()->pageTextContainsOnce('paragraphed_test Example title has been created.');
    $this->assertSession()->elementTextContains('css', '.paragraph--type--nested-test', 'Paragraphs');
    $this->assertSession()->elementTextContains('css', '.paragraph--type--text', '');

    // Add a paragraphs field with another paragraphs widget title to the
    // paragraphed_test content type.
    $this->addParagraphsField('paragraphed_test', 'field_paragraphs_two', 'node');
    $settings = [
      'title' => 'Renamed paragraph',
      'title_plural' => 'Renamed paragraphs',
      'add_mode' => 'modal',
    ];
    $this->setParagraphsWidgetSettings('paragraphed_test', 'field_paragraphs_two', $settings);

    // Check that the "add" buttons and modal form windows are labeled
    // correctly.
    $this->drupalGet('node/add/paragraphed_test');
    $page->pressButton('Add Paragraph');
    $this->assertSession()->elementTextContains('css', '.ui-dialog-title', 'Add Paragraph');
    $this->assertSession()->elementTextNotContains('css', '.ui-dialog-title', 'Add Renamed paragraph');
    $this->assertSession()->elementExists('css', '.ui-dialog-titlebar-close')->press();
    $page->pressButton('Add Renamed paragraph');
    $this->assertSession()->elementTextContains('css', '.ui-dialog-title', 'Add Renamed paragraph');
    $this->assertSession()->elementTextNotContains('css', '.ui-dialog-title', 'Add Paragraph');
  }

  /**
   * Test Modal add widget with hidden delta field.
   */
  public function testModalAddWidgetDelta() {
    $content_type = 'test_modal_delta';
    $this->addParagraphedContentType($content_type);
    $this->loginAsAdmin([
      "administer content types",
      "administer node form display",
      "edit any $content_type content",
      "create $content_type content",
    ]);

    // Set the add mode on the content type to modal form widget.
    $this->drupalGet("admin/structure/types/manage/$content_type/form-display");
    $page = $this->getSession()->getPage();
    $page->pressButton('field_paragraphs_settings_edit');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $edit = [
      'fields[field_paragraphs][settings_edit_form][settings][edit_mode]' => 'closed',
      'fields[field_paragraphs][settings_edit_form][settings][add_mode]' => 'modal',
    ];
    $this->submitForm($edit, 'Update');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->submitForm([], 'Save');

    // Add a Paragraph types.
    $this->addParagraphsType('test_1');
    $this->addParagraphsType('test_2');
    $this->addParagraphsType('test_3');

    // Add a text field to the text_paragraph type.
    $this->drupalGet('admin/structure/paragraphs_type/test_1/fields/add-field');
    $page->selectFieldOption('new_storage_type', 'formatted_text');
    if ($this->coreVersion('10.3')) {
      $page->pressButton('Continue');
    }
    else {
      $this->assertSession()->waitForElementVisible('css', '#text_long');
    }
    $page->selectFieldOption('group_field_options_wrapper', 'text_long');
    $page->fillField('label', 'Text');
    $this->assertSession()->waitForElementVisible('css', '#edit-name-machine-name-suffix .link');
    $page->pressButton('Edit');
    $page->fillField('field_name', 'text_1');
    $page->pressButton('Continue');
    $page->pressButton('Save settings');

    $this->drupalGet('admin/structure/paragraphs_type/test_2/fields/add-field');
    $page->selectFieldOption('new_storage_type', 'formatted_text');
    if ($this->coreVersion('10.3')) {
      $page->pressButton('Continue');
    }
    else {
      $this->assertSession()->waitForElementVisible('css', '#text_long');
    }
    $page->selectFieldOption('group_field_options_wrapper', 'text_long');
    $page->fillField('label', 'Text');
    $this->assertSession()->waitForElementVisible('css', '#edit-name-machine-name-suffix .link');
    $page->pressButton('Edit');
    $page->fillField('field_name', 'text_2');
    if ($this->coreVersion('10.2')) {
      $page->pressButton('Continue');
      $page->pressButton('Save settings');
    }
    else {
      $page->pressButton('Save and continue');
      $page->pressButton('Save field settings');
      $page->pressButton('Save settings');
    }

    $this->drupalGet('admin/structure/paragraphs_type/test_3/fields/add-field');
    $page->selectFieldOption('new_storage_type', 'formatted_text');
    if ($this->coreVersion('10.3')) {
      $page->pressButton('Continue');
    }
    else {
      $this->assertSession()->waitForElementVisible('css', '#text_long');
    }
    $page->selectFieldOption('group_field_options_wrapper', 'text_long');
    $page->fillField('label', 'Text');
    $this->assertSession()->waitForElementVisible('css', '#edit-name-machine-name-suffix .link');
    $page->pressButton('Edit');
    $page->fillField('field_name', 'test_3');
    $page->pressButton('Continue');
    $page->pressButton('Save settings');

    // Create paragraph type Nested test.
    $this->addParagraphsType('test_nested');

    $this->drupalGet('/admin/structure/paragraphs_type/test_nested/fields/add-field');
    $page->selectFieldOption('new_storage_type', 'field_ui:entity_reference_revisions:paragraph');
    if ($this->coreVersion('10.3')) {
      $page->pressButton('Continue');
    }
    $page->fillField('label', 'Paragraphs');
    $this->assertSession()->waitForElementVisible('css', '#edit-name-machine-name-suffix .link');
    $page->pressButton('Edit');
    $page->fillField('field_name', 'paragraphs');
    $page->pressButton('Continue');
    $page->pressButton('Save settings');

    // Set the settings for the field in the nested paragraph.
    $component = [
      'type' => 'paragraphs',
      'region' => 'content',
      'settings' => [
        'edit_mode' => 'closed',
        'add_mode' => 'modal',
        'form_display_mode' => 'default',
      ],
    ];
    EntityFormDisplay::load('paragraph.test_nested.default')->setComponent('field_paragraphs', $component)->save();

    // Add a paragraphed test.
    $this->drupalGet('node/add/test_modal_delta');
    $page->fillField('title[0][value]', 'Test modal add widget delta');

    // Add a nested paragraph with the add widget - use negative delta.
    //
    // This case covers full execution of
    // ParagraphsWidget::prepareDeltaPosition() when list is empty.
    $this->getSession()->executeScript("jQuery('input.paragraph-type-add-delta').last().val(-100)");
    $page->find('xpath', '//*[@name="button_add_modal"]')->click();
    $page->find('xpath', '//*[contains(@class, "paragraphs-add-dialog") and contains(@class, "ui-dialog-content")]//*[contains(@name, "test_nested")]')->click();
    $this->assertSession()->assertWaitOnAjaxRequest();

    // NOTE: After nested paragraphs is added there will be 2 add buttons and we
    // will use xpath "ancestor" axis to switch scope between base paragraphs
    // and nested paragraphs.
    //
    // For jQuery selector, we will use first() and last(), for nested and base
    // paragraph respectively.
    //
    // Add 2 additional paragraphs in base field.
    $this->getSession()->executeScript("jQuery('input.paragraph-type-add-delta').last().val('')");
    for ($i = 1; $i <= 2; $i++) {
      $page->find('xpath', '//*[@name="button_add_modal" and not(ancestor::table)]')->click();
      $page->find('xpath', '//*[contains(@class, "paragraphs-add-dialog") and contains(@class, "ui-dialog-content")]//*[contains(@name, "test_' . $i . '")]')->click();
      $this->assertSession()->assertWaitOnAjaxRequest();
    }

    // There should be 3 paragraphs and last one should be "test_2" type.
    $base_paragraphs = $page->findAll('xpath', '//*[contains(@class, "paragraph-type-label") and not(ancestor::div[contains(@class, "paragraphs-nested")])]');
    $this->assertEquals(3, count($base_paragraphs), 'There should be 3 paragraphs.');
    $this->assertEquals('test_2', $base_paragraphs[2]->getText(), 'Last paragraph should be type "test_2".');

    // Add new paragraph to 1st position - set delta to 0 for base paragraphs.
    $this->getSession()->executeScript("jQuery('input.paragraph-type-add-delta').last().val(0)");
    $page->find('xpath', '//*[@name="button_add_modal" and not(ancestor::table)]')->click();
    $page->find('xpath', '//*[contains(@class, "paragraphs-add-dialog") and contains(@class, "ui-dialog-content")]//*[contains(@name, "test_3")]')->click();
    $this->assertSession()->assertWaitOnAjaxRequest();

    // There should be 4 paragraphs and first one should be "test_3" type.
    $base_paragraphs = $page->findAll('xpath', '//*[contains(@class, "paragraph-type-label") and not(ancestor::div[contains(@class, "paragraphs-nested")])]');
    $this->assertEquals(4, count($base_paragraphs), 'There should be 4 paragraphs.');
    $this->assertEquals('test_3', $base_paragraphs[0]->getText(), '1st paragraph should be type "test_3".');

    // Add new paragraph to 3rd position - set delta to 2 for base paragraphs.
    $this->getSession()->executeScript("jQuery('input.paragraph-type-add-delta').last().val(2)");
    $page->find('xpath', '//*[@name="button_add_modal" and not(ancestor::table)]')->click();
    $page->find('xpath', '//*[contains(@class, "paragraphs-add-dialog") and contains(@class, "ui-dialog-content")]//*[contains(@name, "test_2")]')->click();
    $this->assertSession()->assertWaitOnAjaxRequest();

    // There should be 5 paragraphs and 3rd one should be "test_2" type.
    $base_paragraphs = $page->findAll('xpath', '//*[contains(@class, "paragraph-type-label") and not(ancestor::div[contains(@class, "paragraphs-nested")])]');
    $this->assertEquals(5, count($base_paragraphs), 'There should be 5 paragraphs.');
    $this->assertEquals('test_2', $base_paragraphs[2]->getText(), '3rd paragraph should be type "test_2".');

    // Add new paragraph to last position - using really big delta.
    $this->getSession()->executeScript("jQuery('input.paragraph-type-add-delta').last().val(1000)");
    $page->find('xpath', '//*[@name="button_add_modal" and not(ancestor::table)]')->click();
    $page->find('xpath', '//*[contains(@class, "paragraphs-add-dialog") and contains(@class, "ui-dialog-content")]//*[contains(@name, "test_1")]')->click();
    $this->assertSession()->assertWaitOnAjaxRequest();

    // There should be 6 paragraphs and last one should be "test_1" type.
    $base_paragraphs = $page->findAll('xpath', '//*[contains(@class, "paragraph-type-label") and not(ancestor::div[contains(@class, "paragraphs-nested")])]');
    $this->assertEquals(6, count($base_paragraphs), 'There should be 6 paragraphs.');
    $this->assertEquals('test_1', $base_paragraphs[5]->getText(), 'Last paragraph should be type "test_1".');

    // Clear delta base paragraphs.
    $this->getSession()->executeScript("jQuery('input.paragraph-type-add-delta').last().val('')");
    $page->find('xpath', '//*[@name="button_add_modal" and not(ancestor::table)]')->click();
    $page->find('xpath', '//*[contains(@class, "paragraphs-add-dialog") and contains(@class, "ui-dialog-content")]//*[contains(@name, "test_3")]')->click();
    $this->assertSession()->assertWaitOnAjaxRequest();

    // There should be 7 paragraphs and last one should be "test_3" type.
    $base_paragraphs = $page->findAll('xpath', '//*[contains(@class, "paragraph-type-label") and not(ancestor::div[contains(@class, "paragraphs-nested")])]');
    $this->assertEquals(7, count($base_paragraphs), 'There should be 7 paragraphs.');
    $this->assertEquals('test_3', $base_paragraphs[6]->getText(), 'Last paragraph should be type "test_3".');

    // Save -> Open -> Check.
    $page->pressButton('Save');
    $this->drupalGet('/node/1/edit');

    // Check order for all Base Paragraphs.
    $base_paragraphs = $page->findAll('xpath', '//*[contains(@class, "paragraph-type-label") and not(ancestor::div[contains(@class, "paragraphs-nested")])]');
    $base_paragraphs_type = [];
    foreach ($base_paragraphs as $base_paragraph) {
      $base_paragraphs_type[] = $base_paragraph->getText();
    }
    $this->assertEquals(
      [
        'test_3',
        'test_nested',
        'test_2',
        'test_1',
        'test_2',
        'test_1',
        'test_3',
      ],
      $base_paragraphs_type
    );

    // Test adding in nested paragraphs.
    $page->find('xpath', '//tr[2]/td[2]//*[contains(@class, "paragraphs-icon-button-edit")]')->click();
    $this->assertSession()->assertWaitOnAjaxRequest();

    // Add paragraph in nested to have initial state for adding positions.
    $page->find('xpath', '//*[@name="button_add_modal" and ancestor::table]')->click();
    $page->find('xpath', '//*[contains(@class, "paragraphs-add-dialog") and contains(@class, "ui-dialog-content")]//*[contains(@name, "test_1")]')->click();
    $this->assertSession()->assertWaitOnAjaxRequest();

    // Add new paragraph to first position.
    $this->getSession()->executeScript("jQuery('input.paragraph-type-add-delta').first().val(0)");
    $page->find('xpath', '//*[@name="button_add_modal" and ancestor::table]')->click();
    $page->find('xpath', '//*[contains(@class, "paragraphs-add-dialog") and contains(@class, "ui-dialog-content")]//*[contains(@name, "test_3")]')->click();
    $this->assertSession()->assertWaitOnAjaxRequest();

    // Add new paragraph to 2nd position - using float value for index.
    $this->getSession()->executeScript("jQuery('input.paragraph-type-add-delta').first().val(1.1111)");
    $page->find('xpath', '//*[@name="button_add_modal" and ancestor::table]')->click();
    $page->find('xpath', '//*[contains(@class, "paragraphs-add-dialog") and contains(@class, "ui-dialog-content")]//*[contains(@name, "test_2")]')->click();
    $this->assertSession()->assertWaitOnAjaxRequest();

    // Add new paragraph to first position - using negative index.
    $this->getSession()->executeScript("jQuery('input.paragraph-type-add-delta').first().val(-100)");
    $page->find('xpath', '//*[@name="button_add_modal" and ancestor::table]')->click();
    $page->find('xpath', '//*[contains(@class, "paragraphs-add-dialog") and contains(@class, "ui-dialog-content")]//*[contains(@name, "test_2")]')->click();
    $this->assertSession()->assertWaitOnAjaxRequest();

    // Add new paragraph to last position - using some text as position.
    $this->getSession()->executeScript("jQuery('input.paragraph-type-add-delta').first().val('some_text')");
    $page->find('xpath', '//*[@name="button_add_modal" and ancestor::table]')->click();
    $page->find('xpath', '//*[contains(@class, "paragraphs-add-dialog") and contains(@class, "ui-dialog-content")]//*[contains(@name, "test_3")]')->click();
    $this->assertSession()->assertWaitOnAjaxRequest();

    // Check order for all Nested Paragraphs.
    $nested_paragraphs = $page->findAll('xpath', '//*[contains(@class, "paragraph-type-label") and ancestor::div[contains(@class, "paragraphs-nested")]]');
    $nested_paragraphs_type = [];
    foreach ($nested_paragraphs as $nested_paragraph) {
      $nested_paragraphs_type[] = $nested_paragraph->getText();
    }
    $this->assertEquals(
      [
        'test_2',
        'test_3',
        'test_2',
        'test_1',
        'test_3',
      ],
      $nested_paragraphs_type
    );

    // Check the Add above functionality does not affect the position of the new
    // added Paragraphs when using the Add Paragraph button at the bottom.
    $this->drupalGet('node/add/test_modal_delta');
    // Add a new Paragraph.
    $page->find('xpath', '//*[@name="button_add_modal"]')->click();
    $paragraphs_dialog = $this->assertSession()->waitForElementVisible('css', 'div.ui-dialog');
    $paragraphs_dialog->find('xpath', '//*[contains(@class, "paragraphs-add-dialog") and contains(@class, "ui-dialog-content")]//*[contains(@name, "test_1")]')->press();
    $this->assertSession()->assertWaitOnAjaxRequest();
    // Attempt to add a new Paragraph above and cancel.
    $page->find('xpath', '//*[@name="button_add_modal"]')->click();
    $this->getSession()->executeScript("jQuery('input.paragraph-type-add-delta').first().val(0)");
    $this->assertSession()->elementExists('css', '.ui-dialog-titlebar-close')->press();
    $delta = $this->getSession()->evaluateScript("jQuery('.paragraph-type-add-delta').val()");
    $this->assertEquals($delta, '');
    // Add a new Paragraph with the Add button at the bottom.
    $page->find('xpath', '//*[@name="button_add_modal"]')->click();
    $paragraphs_dialog = $this->assertSession()->waitForElementVisible('css', 'div.ui-dialog');
    $paragraphs_dialog->find('xpath', '//*[contains(@class, "paragraphs-add-dialog") and contains(@class, "ui-dialog-content")]//*[contains(@name, "test_2")]')->press();
    $this->assertSession()->assertWaitOnAjaxRequest();
    // The position of it should be below the first added Paragraph.
    $base_paragraphs = $page->findAll('xpath', '//*[contains(@class, "paragraph-type-label") and not(ancestor::div[contains(@class, "paragraphs-nested")])]');
    $base_paragraphs_type = [];
    foreach ($base_paragraphs as $base_paragraph) {
      $base_paragraphs_type[] = $base_paragraph->getText();
    }
    $this->assertEquals(['test_1', 'test_2'], $base_paragraphs_type);
  }

}
