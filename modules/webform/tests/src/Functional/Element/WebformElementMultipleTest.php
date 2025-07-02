<?php

namespace Drupal\Tests\webform\Functional\Element;

use Drupal\Component\Utility\DeprecationHelper;
use Drupal\webform\Entity\Webform;

/**
 * Tests for webform element multiple.
 *
 * @group webform
 */
class WebformElementMultipleTest extends WebformElementBrowserTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = [
    'test_element_multiple',
    'test_element_multiple_header',
  ];

  /**
   * Tests multiple element.
   */
  public function testMultiple() {
    $assert_session = $this->assertSession();

    /* ********************************************************************** */
    // Processing.
    /* ********************************************************************** */

    $webform = Webform::load('test_element_multiple');

    // Check processing for all elements.
    $this->drupalGet('/webform/test_element_multiple');
    $this->submitForm([], 'Submit');
    $assert_session->responseContains("webform_multiple_default:
  - One
  - Two
  - Three
webform_multiple_no_sorting:
  - One
  - Two
  - Three
webform_multiple_no_operations:
  - One
  - Two
  - Three
webform_multiple_no_add_more:
  - One
  - Two
  - Three
webform_multiple_no_add_more_input:
  - One
  - Two
  - Three
webform_multiple_custom_label:
  - One
  - Two
  - Three
webform_multiple_required:
  - One
  - Two
  - Three
webform_multiple_email_five:
  - example@example.com
  - test@test.com
webform_multiple_datelist: {  }
webform_multiple_name_composite:
  - title: ''
    first: John
    middle: ''
    last: Smith
    suffix: ''
    degree: ''
  - title: ''
    first: Jane
    middle: ''
    last: Doe
    suffix: ''
    degree: ''
webform_multiple_elements_name_item:
  - first_name: John
    last_name: Smith
  - first_name: Jane
    last_name: Doe
webform_multiple_elements_name_table:
  - first_name: John
    last_name: Smith
  - first_name: Jane
    last_name: Doe
webform_multiple_options:
  - value: one
    text: One
  - value: two
    text: Two
webform_multiple_key:
  one:
    text: One
    score: '1'
  two:
    text: Two
    score: '2'
webform_multiple_elements_hidden_table:
  - first_name: John
    id: john
    last_name: Smith
  - first_name: Jane
    id: jane
    last_name: Doe
webform_multiple_elements_flattened:
  - value: one
    text: One
    description: 'This is the number 1.'
  - value: two
    text: Two
    description: 'This is the number 2.'
webform_multiple_no_items: {  }
webform_multiple_custom_attributes: {  }");

    /* ********************************************************************** */
    // Rendering.
    /* ********************************************************************** */

    $this->drupalGet('/webform/test_element_multiple');

    // Check first tr.
    $assert_session->responseContains('<tr class="draggable" data-drupal-selector="edit-webform-multiple-default-items-0">');
    DeprecationHelper::backwardsCompatibleCall(
      currentVersion: \Drupal::VERSION,
      deprecatedVersion: '10.2',
      currentCallable: fn() => $assert_session->responseContains('<td><div class="js-form-item form-item form-type-textfield js-form-type-textfield form-item-webform-multiple-default-items-0--item- js-form-item-webform-multiple-default-items-0--item- form-no-label">'),
      deprecatedCallable: fn() => $assert_session->responseContains('<td><div class="js-form-item form-item js-form-type-textfield form-item-webform-multiple-default-items-0--item- js-form-item-webform-multiple-default-items-0--item- form-no-label">'),
    );
    $assert_session->responseContains('<label for="edit-webform-multiple-default-items-0-item-" class="visually-hidden">Item value</label>');
    $assert_session->responseContains('<input data-drupal-selector="edit-webform-multiple-default-items-0-item-" type="text" id="edit-webform-multiple-default-items-0-item-" name="webform_multiple_default[items][0][_item_]" value="One" size="60" maxlength="128" placeholder="Enter value…" class="form-text" />');
    DeprecationHelper::backwardsCompatibleCall(
      currentVersion: \Drupal::VERSION,
      deprecatedVersion: '10.2',
      currentCallable: fn() => $assert_session->responseContains('<td class="webform-multiple-table--weight"><div class="webform-multiple-table--weight js-form-item form-item form-type-number js-form-type-number form-item-webform-multiple-default-items-0-weight js-form-item-webform-multiple-default-items-0-weight form-no-label">'),
      deprecatedCallable: fn() => $assert_session->responseContains('<td class="webform-multiple-table--weight"><div class="webform-multiple-table--weight js-form-item form-item js-form-type-number form-item-webform-multiple-default-items-0-weight js-form-item-webform-multiple-default-items-0-weight form-no-label">'),
    );
    $assert_session->responseContains('<label for="edit-webform-multiple-default-items-0-weight" class="visually-hidden">Item weight</label>');
    $assert_session->responseContains('<input class="webform-multiple-sort-weight form-number" data-drupal-selector="edit-webform-multiple-default-items-0-weight" type="number" id="edit-webform-multiple-default-items-0-weight" name="webform_multiple_default[items][0][weight]" value="0" step="1" size="10" />');
    $assert_session->responseContains('<td class="webform-multiple-table--operations webform-multiple-table--operations-two"><input data-drupal-selector="edit-webform-multiple-default-items-0-operations-add" formnovalidate="formnovalidate" type="image" id="edit-webform-multiple-default-items-0-operations-add" name="webform_multiple_default_table_add_0"');
    $assert_session->responseContains('<input data-drupal-selector="edit-webform-multiple-default-items-0-operations-remove" formnovalidate="formnovalidate" type="image" id="edit-webform-multiple-default-items-0-operations-remove" name="webform_multiple_default_table_remove_0"');

    // Check that sorting is disabled.
    $assert_session->responseNotContains('<tr class="draggable" data-drupal-selector="edit-webform-multiple-no-sorting-items-0">');
    $assert_session->responseContains('<tr data-drupal-selector="edit-webform-multiple-no-sorting-items-0">');

    // Check that add more is removed.
    $assert_session->fieldValueEquals('webform_multiple_no_operations[add][more_items]', '1');
    $assert_session->buttonNotExists('webform_multiple_no_add_more_table_add');
    $assert_session->fieldNotExists('webform_multiple_no_add_more[add][more_items]');

    // Check that add more input is removed.
    $assert_session->buttonExists('webform_multiple_no_add_more_input_table_add');
    $assert_session->fieldNotExists('webform_multiple_no_add_more_input[add][more_items]');

    // Check custom labels.
    $assert_session->responseContains('<input data-drupal-selector="edit-webform-multiple-custom-label-add-submit" formnovalidate="formnovalidate" type="submit" id="edit-webform-multiple-custom-label-add-submit" name="webform_multiple_custom_label_table_add" value="{add_more_button_label}" class="button js-form-submit form-submit" />');
    $assert_session->responseContains('<span class="field-suffix">{add_more_input_label}</span>');

    // Check that operations is disabled.
    $assert_session->responseNotContains('data-drupal-selector="edit-webform-multiple-no-operations-items-0-operations-remove"');

    // Check no items message.
    $assert_session->responseContains('No items entered. Please add items below.');

    // Check that required does not include any empty elements.
    $assert_session->fieldExists('webform_multiple_required[items][2][_item_]');
    $assert_session->fieldNotExists('webform_multiple_required[items][3][_item_]');

    // Check custom label, wrapper, and element attributes.
    $assert_session->responseContains('<div class="custom-ajax" id="webform_multiple_custom_attributes_table">');
    $assert_session->responseContains('<div class="custom-table-wrapper webform-multiple-table">');
    $assert_session->responseContains('<table class="custom-table responsive-enabled" data-drupal-selector="edit-webform-multiple-custom-attributes-items" id="edit-webform-multiple-custom-attributes-items" data-striping="1">');
    $assert_session->responseContains('<th class="custom-label webform_multiple_custom_attributes-table--textfield webform-multiple-table--textfield">textfield</th>');
    $assert_session->responseContains('<label class="custom-label visually-hidden"');
    $assert_session->responseContains('<div class="custom-wrapper js-form-item form-item');
    $assert_session->responseContains('<input class="custom-element form-text"');

    /* ********************************************************************** */
    // Validation.
    /* ********************************************************************** */

    // Check unique #key validation.
    $this->drupalGet('/webform/test_element_multiple');
    $edit = ['webform_multiple_key[items][1][value]' => 'one'];
    $this->submitForm($edit, 'Submit');
    $assert_session->responseContains('The <em class="placeholder">Option value</em> \'one\' is already in use. It must be unique.');

    /* ********************************************************************** */
    // Processing.
    /* ********************************************************************** */

    // Check populated 'webform_multiple_default'.
    $assert_session->fieldValueEquals('webform_multiple_default[items][0][_item_]', 'One');
    $assert_session->fieldValueEquals('webform_multiple_default[items][1][_item_]', 'Two');
    $assert_session->fieldValueEquals('webform_multiple_default[items][2][_item_]', 'Three');
    $assert_session->fieldValueEquals('webform_multiple_default[items][3][_item_]', '');
    $assert_session->fieldNotExists('webform_multiple_default[items][4][_item_]');

    // Check adding empty after one.
    $this->submitForm($edit, 'webform_multiple_default_table_add_0');
    $assert_session->fieldValueEquals('webform_multiple_default[items][0][_item_]', 'One');
    $assert_session->fieldValueEquals('webform_multiple_default[items][1][_item_]', '');
    $assert_session->fieldValueNotEquals('webform_multiple_default[items][1][_item_]', 'Two');
    $assert_session->fieldValueEquals('webform_multiple_default[items][2][_item_]', 'Two');
    $assert_session->fieldValueEquals('webform_multiple_default[items][3][_item_]', 'Three');

    // Check removing empty after one.
    $this->submitForm($edit, 'webform_multiple_default_table_remove_1');
    $assert_session->fieldValueEquals('webform_multiple_default[items][0][_item_]', 'One');
    $assert_session->fieldValueEquals('webform_multiple_default[items][1][_item_]', 'Two');
    $assert_session->fieldValueEquals('webform_multiple_default[items][2][_item_]', 'Three');

    // Check adding 'four' and 1 more option.
    $edit = ['webform_multiple_default[items][3][_item_]' => 'Four'];
    $this->submitForm($edit, 'webform_multiple_default_table_add');
    $assert_session->fieldValueEquals('webform_multiple_default[items][3][_item_]', 'Four');
    $assert_session->fieldValueEquals('webform_multiple_default[items][4][_item_]', '');

    // Check add 10 more rows.
    $edit = ['webform_multiple_default[add][more_items]' => 10];
    $this->submitForm($edit, 'webform_multiple_default_table_add');
    $assert_session->fieldValueEquals('webform_multiple_default[items][14][_item_]', '');
    $assert_session->fieldNotExists('webform_multiple_default[items][15][_item_]');

    // Check remove 'one' options.
    $this->submitForm($edit, 'webform_multiple_default_table_remove_0');
    $assert_session->fieldNotExists('webform_multiple_default[items][14][_item_]');
    $assert_session->fieldValueNotEquals('webform_multiple_default[items][0][_item_]', 'One');
    $assert_session->fieldValueEquals('webform_multiple_default[items][0][_item_]', 'Two');
    $assert_session->fieldValueEquals('webform_multiple_default[items][1][_item_]', 'Three');
    $assert_session->fieldValueEquals('webform_multiple_default[items][2][_item_]', 'Four');

    // Add one options to 'webform_multiple_no_items'.
    $this->submitForm($edit, 'webform_multiple_no_items_table_add');
    $assert_session->responseNotContains('No items entered. Please add items below.');
    $assert_session->fieldExists('webform_multiple_no_items[items][0][_item_]');

    // Check no items message is never displayed when #required.
    $webform->setElementProperties('webform_multiple_no_items', [
      '#type' => 'webform_multiple',
      '#title' => 'webform_multiple_no_items',
      '#required' => TRUE,
    ]);
    $webform->save();
    $this->drupalGet('/webform/test_element_multiple');
    $assert_session->responseNotContains('No items entered. Please add items below.');
    $this->submitForm($edit, 'webform_multiple_default_table_remove_0');
    $assert_session->responseNotContains('No items entered. Please add items below.');

    /* ********************************************************************** */
    // Header.
    /* ********************************************************************** */

    $this->drupalGet('/webform/test_element_multiple_header');

    // Check #header property as string.
    $assert_session->responseContains('<th colspan="5">{webform_multiple_basic_header_string}</th>');

    // Check #header_label property.
    $assert_session->responseContains('<th colspan="5">{webform_multiple_basic_header_label}</th>');

    // Check #header property as string with elements.
    $assert_session->responseContains('<th colspan="6">{webform_multiple_elements_header_string}</th>');

    // Check #header property set to true.
    $assert_session->responseContains('<th class="webform_multiple_elements_header_true-table--handle webform-multiple-table--handle">');
    $assert_session->responseContains('<th class="webform_multiple_elements_header_true-table--textfield webform-multiple-table--textfield">');
    $assert_session->responseContains('<th class="webform_multiple_elements_header_true-table--email webform-multiple-table--email">');
    $assert_session->responseContains('<th class="webform_multiple_elements_header_true-table--weight webform-multiple-table--weight">');
    $assert_session->responseContains('<th class="webform_multiple_elements_header_true-table--operations webform-multiple-table--operations">');

    // Check #header property set to custom array of header string.
    $assert_session->responseContains('<th>{textfield_custom}</th>');
    $assert_session->responseContains('<th>{email_custom}</th>');

    // Check #header property set to true with header label.
    $assert_session->responseContains('<th colspan="6">{webform_multiple_elements_header_true_label}</th>');

    // Check #header property set to false with header label.
    $assert_session->responseContains('<th colspan="5">{webform_multiple_elements_header_false_label}</th>');
  }

}
