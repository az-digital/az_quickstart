<?php

namespace Drupal\Tests\webform\Functional\Element;

/**
 * Tests for webform validate multiple.
 *
 * @group webform
 */
class WebformElementValidateMultipleTest extends WebformElementBrowserTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_validate_multiple'];

  /**
   * Tests element validate multiple.
   */
  public function testValidateMultiple() {
    $assert_session = $this->assertSession();

    $this->drupalGet('/webform/test_element_validate_multiple');

    // Check that only three textfields are displayed.
    $assert_session->fieldExists('webform_element_multiple_textfield_three[items][0][_item_]');
    $assert_session->fieldNotExists('webform_element_multiple_textfield_three[items][1][_item_]');
    $assert_session->fieldNotExists('webform_element_multiple_textfield_three[items][2][_item_]');
    $assert_session->fieldNotExists('webform_element_multiple_textfield_three[items][3][_item_]');
    $assert_session->fieldNotExists('webform_element_multiple_textfield_three_table_add');

    // Add 2 more items.
    $edit = [
      'webform_element_multiple_textfield_three[add][more_items]' => 2,
    ];
    $this->submitForm($edit, 'webform_element_multiple_textfield_three_table_add');
    $assert_session->fieldExists('webform_element_multiple_textfield_three[items][0][_item_]');
    $assert_session->fieldExists('webform_element_multiple_textfield_three[items][1][_item_]');
    $assert_session->fieldExists('webform_element_multiple_textfield_three[items][2][_item_]');
    $assert_session->fieldNotExists('webform_element_multiple_textfield_three[items][3][_item_]');
    $assert_session->fieldNotExists('webform_element_multiple_textfield_three_table_add');

    // Post multiple values to checkboxes and select multiple that exceed
    // allowed values.
    $this->drupalGet('/webform/test_element_validate_multiple');
    $edit = [
      'webform_element_multiple_checkboxes_two[one]' => 'one',
      'webform_element_multiple_checkboxes_two[two]' => 'two',
      'webform_element_multiple_checkboxes_two[three]' => 'three',
      'webform_element_multiple_select_two[]' => ['one', 'two', 'three'],
    ];
    $this->submitForm($edit, 'Submit');

    // Check checkboxes multiple custom error message.
    $assert_session->responseContains('Please check only two options.');

    // Check select multiple default error message.
    $assert_session->responseContains('<em class="placeholder">webform_element_multiple_select_two</em>: this element cannot hold more than 2 values.');
  }

}
