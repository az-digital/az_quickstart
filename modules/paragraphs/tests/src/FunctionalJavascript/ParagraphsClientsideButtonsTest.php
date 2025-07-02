<?php

namespace Drupal\Tests\paragraphs\FunctionalJavascript;

use Drupal\field\Entity\FieldStorageConfig;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;
use Drupal\Tests\field_ui\Traits\FieldUiTestTrait;
use Drupal\Tests\paragraphs\Traits\ParagraphsCoreVersionUiTestTrait;
use Drupal\Tests\paragraphs\Traits\ParagraphsLastEntityQueryTrait;

/**
 * Test paragraphs user interface.
 *
 * @group paragraphs
 */
class ParagraphsClientsideButtonsTest extends WebDriverTestBase {

  use LoginAdminTrait;
  use FieldUiTestTrait;
  use ParagraphsTestBaseTrait;
  use ParagraphsCoreVersionUiTestTrait;
  use ParagraphsLastEntityQueryTrait;

  /**
   * {@inheritdoc}
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
   * Tests the "Add above" button.
   */
  public function testAddParagraphAboveButton() {
    $session = $this->getSession();
    $page = $session->getPage();
    $assert_session = $this->assertSession();

    $this->addParagraphedContentType('paragraphed_test');
    $this->loginAsAdmin([
      'administer content types',
      'administer node form display',
      'edit any paragraphed_test content',
      'create paragraphed_test content',
    ]);
    // Set the add mode on the content type to modal form widget.
    $form_display = \Drupal::service('entity_display.repository')->getFormDisplay('node', 'paragraphed_test');
    $form_display->setComponent('field_paragraphs', [
      'type' => 'paragraphs',
      'settings' => [
        'title' => 'Paragraph',
        'title_plural' => 'Paragraphs',
        'edit_mode' => 'closed',
        'closed_mode' => 'summary',
        'autocollapse' => 'none',
        'add_mode' => 'modal',
        'form_display_mode' => 'default',
        'default_paragraph_type' => '_none',
        'features' => [
          'duplicate' => 'duplicate',
          'duplicate' => 'duplicate',
          'collapse_edit_all' => 'collapse_edit_all',
          'add_above' => 'add_above',
        ],
        'third_party_settings' => [],
        'region' => 'content',
      ],
    ])
      ->save();
    // Add a Paragraph type.
    $this->addParagraphsType('text');
    // Add a text field to the text_paragraph type.
    $this->drupalGet('admin/structure/paragraphs_type/text/fields/add-field');
    $page->selectFieldOption('new_storage_type', 'plain_text');
    $this->assertSession()->waitForElementVisible('css', '#string');
    if ($this->coreVersion('10.3')) {
      $page->pressButton('Continue');
    }
    $page->selectFieldOption('group_field_options_wrapper', 'string');
    $page->fillField('label', 'Text');
    $this->assertSession()->waitForElementVisible('css', '#edit-name-machine-name-suffix .link');
    $page->pressButton('Edit');
    $page->fillField('field_name', 'text');
    $page->pressButton('Continue');
    $page->pressButton('Save settings');
    // Add a paragraphed test.
    $this->drupalGet('node/add/paragraphed_test');
    // Add 3 paragraphs.
    $page->pressButton('Add Paragraph');
    $dialog = $page->find('xpath', '//div[contains(@class, "ui-dialog")]');
    $dialog->pressButton('text');
    $assert_session->assertWaitOnAjaxRequest();
    $page->pressButton('Add Paragraph');
    $dialog = $page->find('xpath', '//div[contains(@class, "ui-dialog")]');
    $dialog->pressButton('text');
    $assert_session->assertWaitOnAjaxRequest();
    $page->pressButton('Add Paragraph');
    $dialog = $page->find('xpath', '//div[contains(@class, "ui-dialog")]');
    $dialog->pressButton('text');
    $assert_session->assertWaitOnAjaxRequest();
    //$session->wait(2000);
    // Check that the add above button has the button--small class.
    $page->find('xpath', '//input[@class="paragraphs-dropdown-action paragraphs-dropdown-action--add-above button button--small js-form-submit form-submit"]');
    // At this point we should have 3 injected "Add above" buttons.
    $all_add_above_buttons = $page->findAll('css', '#edit-field-paragraphs-wrapper input.paragraphs-dropdown-action--add-above');
    $this->assertEquals(3, count($all_add_above_buttons));
    // Save the node with some text in each paragraph so we can refer to them
    // easily later.
    $edit = [
      'title[0][value]' => 'Example title',
      'field_paragraphs[0][subform][field_text][0][value]' => 'First text',
      'field_paragraphs[1][subform][field_text][0][value]' => 'Second text',
      'field_paragraphs[2][subform][field_text][0][value]' => 'Third text',
    ];
    $this->submitForm($edit, 'Save');
    $node_id = $this->getLastEntityOfType('node');

    // Make sure we honor the widget settings when injecting the button.
    $component = $form_display->getComponent('field_paragraphs');
    unset($component['settings']['features']['add_above']);
    $form_display->setComponent('field_paragraphs', $component)->save();
    $this->drupalGet("/node/{$node_id}/edit");
    $all_add_above_buttons = $page->findAll('css', '#edit-field-paragraphs-wrapper input.paragraphs-dropdown-action--add-above');
    $this->assertEquals(0, count($all_add_above_buttons));

    // Enable it back and test its behavior.
    $component = $form_display->getComponent('field_paragraphs');
    $component['settings']['features']['add_above'] = 'add_above';
    $form_display->setComponent('field_paragraphs', $component)->save();
    $this->drupalGet("/node/{$node_id}/edit");
    $edit_all_button = $assert_session->buttonExists('field_paragraphs_edit_all');
    $edit_all_button->press();
    $session->wait(2000);
    $assert_session->assertWaitOnAjaxRequest();

    // Before inserting the deltas are the ones we expect.
    $first_original_row = $assert_session->elementExists('css', '#field-paragraphs-add-more-wrapper tr.draggable:nth-of-type(1)');
    $delta_paragraph1 = $assert_session->elementExists('css', 'td.delta-order select', $first_original_row)->getValue();
    $this->assertEquals(0, $delta_paragraph1);
    $second_original_row = $assert_session->elementExists('css', '#field-paragraphs-add-more-wrapper tr.draggable:nth-of-type(2)');
    $delta_paragraph2 = $assert_session->elementExists('css', 'td.delta-order select', $second_original_row)->getValue();
    $this->assertEquals(1, $delta_paragraph2);
    $third_original_row = $assert_session->elementExists('css', '#field-paragraphs-add-more-wrapper tr.draggable:nth-of-type(3)');
    $delta_paragraph3 = $assert_session->elementExists('css', 'td.delta-order select', $third_original_row)->getValue();
    $this->assertEquals(2, $delta_paragraph3);

    // Insert a new paragraph above paragraph 2.
    $dropdown = $assert_session->elementExists('css', '.paragraphs-dropdown', $second_original_row);
    $dropdown->click();
    $add_above_button = $assert_session->elementExists('css', 'input.paragraphs-dropdown-action--add-above', $second_original_row);
    $add_above_button->click();
    $dialog = $page->find('xpath', '//div[contains(@class, "ui-dialog")]');
    $dialog->pressButton('text');
    $assert_session->assertWaitOnAjaxRequest();
    $page->fillField('field_paragraphs[3][subform][field_text][0][value]', 'Paragraph added above');

    // Add a new paragraph in order to test that the new paragraph is added at the bottom.
    $page->pressButton('Add Paragraph');
    $dialog = $page->find('xpath', '//div[contains(@class, "ui-dialog")]');
    $dialog->pressButton('text');
    $assert_session->assertWaitOnAjaxRequest();
    $page->fillField('field_paragraphs[4][subform][field_text][0][value]', 'New paragraph');

    // First row after insertion.
    $first_row = $assert_session->elementExists('css', '#field-paragraphs-add-more-wrapper tr.draggable:nth-of-type(1)');
    $text_input_first_row = $assert_session->elementExists('css', 'input.form-text', $first_row);
    $this->assertEquals('First text', $text_input_first_row->getValue());
    $delta_paragraph1 = $assert_session->elementExists('css', 'td.delta-order select', $first_row)->getValue();
    $this->assertEquals(0, $delta_paragraph1);
    // Second row after insertion.
    $second_row = $assert_session->elementExists('css', '#field-paragraphs-add-more-wrapper tr.draggable:nth-of-type(2)');
    $text_input_second_row = $assert_session->elementExists('css', 'input.form-text', $second_row);
    $this->assertEquals('Paragraph added above', $text_input_second_row->getValue());
    $delta_paragraph2 = $assert_session->elementExists('css', 'td.delta-order select', $second_row)->getValue();
    $this->assertEquals(1, $delta_paragraph2);
    // Third row after insertion.
    $third_row = $assert_session->elementExists('css', '#field-paragraphs-add-more-wrapper tr.draggable:nth-of-type(3)');
    $text_input_third_row = $assert_session->elementExists('css', 'input.form-text', $third_row);
    $this->assertEquals('Second text', $text_input_third_row->getValue());
    $delta_paragraph3 = $assert_session->elementExists('css', 'td.delta-order select', $third_row)->getValue();
    $this->assertEquals(2, $delta_paragraph3);
    // Fourth row after insertion.
    $fourth_row = $assert_session->elementExists('css', '#field-paragraphs-add-more-wrapper tr.draggable:nth-of-type(4)');
    $text_input_fourth_row = $assert_session->elementExists('css', 'input.form-text', $fourth_row);
    $this->assertEquals('Third text', $text_input_fourth_row->getValue());
    $delta_paragraph4 = $assert_session->elementExists('css', 'td.delta-order select', $fourth_row)->getValue();
    $this->assertEquals(3, $delta_paragraph4);
    $fifth_row = $assert_session->elementExists('css', '#field-paragraphs-add-more-wrapper tr.draggable:nth-of-type(5)');
    $text_input_fifth_row = $assert_session->elementExists('css', 'input.form-text', $fifth_row);
    $this->assertEquals('New paragraph', $text_input_fifth_row->getValue());
    $delta_paragraph5 = $assert_session->elementExists('css', 'td.delta-order select', $fifth_row)->getValue();
    $this->assertEquals(4, $delta_paragraph5);

    // Let's have more fun with some nested paragraphs.
    $this->addParagraphsType('rich_paragraph');
    $this->addFieldtoParagraphType('rich_paragraph', 'field_intermediate_text', 'text');
    $this->addFieldtoParagraphType('rich_paragraph', 'field_nested_paragraphs', 'entity_reference_revisions', ['target_type' => 'paragraph']);
    $form_display = \Drupal::service('entity_display.repository')->getFormDisplay('paragraph', 'rich_paragraph');
    $form_display->setComponent('field_nested_paragraphs', [
        'type' => 'paragraphs',
        'settings' => [
          'title' => 'Paragraph',
          'title_plural' => 'Paragraphs',
          'edit_mode' => 'closed',
          'closed_mode' => 'summary',
          'autocollapse' => 'none',
          'add_mode' => 'modal',
          'form_display_mode' => 'default',
          'default_paragraph_type' => '_none',
          'features' => [
            'duplicate' => 'duplicate',
            'collapse_edit_all' => 'collapse_edit_all',
            'add_above' => 'add_above',
          ],
          'third_party_settings' => [],
          'region' => 'content',
        ],
      ])
      ->save();

    $this->drupalGet("/node/{$node_id}/edit");
    $edit_all_button = $assert_session->buttonExists('field_paragraphs_edit_all');
    $edit_all_button->press();
    $session->wait(2000);
    $assert_session->assertWaitOnAjaxRequest();

    // Initially only 3 paragraphs and 3 buttons.
    $all_add_above_buttons = $page->findAll('css', '#edit-field-paragraphs-wrapper input.paragraphs-dropdown-action--add-above');
    $this->assertEquals(3, count($all_add_above_buttons));

    $first_original_row = $assert_session->elementExists('css', '#field-paragraphs-add-more-wrapper tr.draggable:nth-of-type(1)');
    $second_original_row = $assert_session->elementExists('css', '#field-paragraphs-add-more-wrapper tr.draggable:nth-of-type(2)');
    $third_original_row = $assert_session->elementExists('css', '#field-paragraphs-add-more-wrapper tr.draggable:nth-of-type(3)');

    // Insert a rich (host) paragraph above row 2.
    $dropdown = $assert_session->elementExists('css', '.paragraphs-dropdown', $second_original_row);
    $dropdown->click();
    $add_above_button = $assert_session->elementExists('css', 'input.paragraphs-dropdown-action--add-above', $second_original_row);
    $add_above_button->click();
    $dialog = $page->find('xpath', '//div[contains(@class, "ui-dialog")]');
    $dialog->pressButton('rich_paragraph');
    $assert_session->assertWaitOnAjaxRequest();
    $rich_paragraph_row = $assert_session->elementExists('css', '#field-paragraphs-add-more-wrapper tr.draggable:nth-of-type(2)');
    $add_paragraph_rich_row = $assert_session->elementExists('css', 'input[name="button_add_modal"]', $rich_paragraph_row);

    // Add a text nested paragraph.
    $add_paragraph_rich_row->click();
    $dialog = $page->find('xpath', '//div[contains(@class, "ui-dialog")]');
    $dialog->pressButton('text');
    $assert_session->assertWaitOnAjaxRequest();

    // 5 paragraphs, we expect 4 injected buttons as the cardinality of the
    // nested paragraph is one and we cannot Add Above.
    $all_add_above_buttons = $page->findAll('css', '#edit-field-paragraphs-wrapper input.paragraphs-dropdown-action--add-above');
    $this->assertEquals(4, count($all_add_above_buttons));

    // Remove the new added Paragraph.
    $rich_paragraph_row = $assert_session->elementExists('css', '#field-paragraphs-add-more-wrapper tr:nth-of-type(2) .field--name-field-nested-paragraphs tr.draggable');
    $dropdown = $assert_session->elementExists('css', '.paragraphs-dropdown', $rich_paragraph_row);
    $dropdown->click();
    $remove_button = $assert_session->buttonExists('field_paragraphs_3_subform_field_nested_paragraphs_0_remove');
    $remove_button->click();
    $assert_session->assertWaitOnAjaxRequest();
    // Set the config to allow more than one Paragraph.
    $field_storage = FieldStorageConfig::loadByName('paragraph', 'field_nested_paragraphs');
    $field_storage->setCardinality(-1);
    $field_storage->save();
    // Add the Paragraph back.
    $add_paragraph_rich_row->click();
    $dialog = $page->find('xpath', '//div[contains(@class, "ui-dialog")]');
    $dialog->pressButton('text');
    $assert_session->assertWaitOnAjaxRequest();

    // 5 paragraphs, we expect 5 injected buttons as the cardinality of the
    // nested paragraph is unlimited.
    $all_add_above_buttons = $page->findAll('css', '#edit-field-paragraphs-wrapper input.paragraphs-dropdown-action--add-above');
    $this->assertEquals(5, count($all_add_above_buttons));

    // Set some text to the normally-added paragraphs so we don't have a false
    // positive while checking for empty texts.
    $text_input_first_nested_row = $assert_session->elementExists('css', '#field-paragraphs-add-more-wrapper tr.draggable:nth-of-type(2) > td:nth-of-type(2)  > div > div > div > div:nth-of-type(2) > div input.form-text');
    $text_input_first_nested_row->setValue('Nested 1 - text 1');
    $text_input_second_nested_row = $assert_session->elementExists('css', '#field-paragraphs-add-more-wrapper tr.draggable:nth-of-type(2) > td:nth-of-type(2)  > div > div > div > div:nth-of-type(2) > div:nth-of-type(2) > div > div input.form-text');
    $text_input_second_nested_row->setValue('Nested 2 - text 1');

    // Insert a text paragraph above the first nested paragraph.
    $first_nested_row = $assert_session->elementExists('css', '#field-paragraphs-add-more-wrapper tr.draggable:nth-of-type(2)');
    $dropdown = $assert_session->elementExists('css', '.paragraphs-dropdown', $first_nested_row);
    $dropdown->click();
    $add_above_button = $assert_session->elementExists('css', 'input.paragraphs-dropdown-action--add-above', $first_nested_row);
    $add_above_button->click();
    $dialog = $page->find('xpath', '//div[contains(@class, "ui-dialog")]');
    $dialog->pressButton('text');
    $assert_session->assertWaitOnAjaxRequest();
    // Check the new element is where we expect it to be.
    $new_element_row = $assert_session->elementExists('css', '#field-paragraphs-add-more-wrapper tr.draggable:nth-of-type(2)');
    $text_input_new_element_row = $assert_session->elementExists('css', 'input.form-text', $new_element_row);
    $this->assertEquals('', $text_input_new_element_row->getValue());
    // We have one more injected add_more button.
    $all_add_above_buttons = $page->findAll('css', '#edit-field-paragraphs-wrapper input.paragraphs-dropdown-action--add-above');
    $this->assertEquals(6, count($all_add_above_buttons));
    $this->submitForm([], 'Save');

    $this->drupalGet("/node/{$node_id}/edit");
    $edit_all_button = $assert_session->buttonExists('field_paragraphs_edit_all');
    $edit_all_button->press();
    $session->wait(2000);
    $assert_session->assertWaitOnAjaxRequest();
    // Insert a Paragraph above.
    $first_row = $assert_session->elementExists('css', '#field-paragraphs-add-more-wrapper tr.draggable:nth-of-type(1)');
    $dropdown = $assert_session->elementExists('css', '.paragraphs-dropdown', $first_row);
    $dropdown->click();
    $add_above_button = $assert_session->elementExists('css', 'input.paragraphs-dropdown-action--add-above', $first_row);
    $add_above_button->click();
    $dialog = $page->find('xpath', '//div[contains(@class, "ui-dialog")]');
    $dialog->pressButton('text');
    $assert_session->assertWaitOnAjaxRequest();
    $all_add_above_buttons = $page->findAll('css', '#edit-field-paragraphs-wrapper input.paragraphs-dropdown-action--add-above');
    $this->assertEquals(7, count($all_add_above_buttons));
    // Remove the new added Paragraph.
    $first_row = $assert_session->elementExists('css', '#field-paragraphs-add-more-wrapper tr.draggable:nth-of-type(1)');
    $dropdown = $assert_session->elementExists('css', '.paragraphs-dropdown', $first_row);
    $dropdown->click();
    $remove_button = $assert_session->buttonExists('field_paragraphs_5_remove');
    $remove_button->click();
    $assert_session->assertWaitOnAjaxRequest();
    $all_add_above_buttons = $page->findAll('css', '#edit-field-paragraphs-wrapper input.paragraphs-dropdown-action--add-above');
    $this->assertEquals(6, count($all_add_above_buttons));
    // Add a Paragraph above again.
    $dropdown = $assert_session->elementExists('css', '.paragraphs-dropdown', $first_row);
    $dropdown->click();
    $add_above_button = $assert_session->elementExists('css', 'input.paragraphs-dropdown-action--add-above', $first_row);
    $add_above_button->click();
    $dialog = $page->find('xpath', '//div[contains(@class, "ui-dialog")]');
    $dialog->pressButton('text');
    $assert_session->assertWaitOnAjaxRequest();
    $all_add_above_buttons = $page->findAll('css', '#edit-field-paragraphs-wrapper input.paragraphs-dropdown-action--add-above');
    $this->assertEquals(7, count($all_add_above_buttons));
  }

}
