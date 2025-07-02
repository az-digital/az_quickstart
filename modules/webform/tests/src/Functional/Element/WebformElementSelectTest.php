<?php

namespace Drupal\Tests\webform\Functional\Element;

/**
 * Tests for select element.
 *
 * @group webform
 */
class WebformElementSelectTest extends WebformElementBrowserTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_select'];

  /**
   * Test select element.
   */
  public function testSelectElement() {
    $assert_session = $this->assertSession();

    // Check default empty option always included.
    $this->drupalGet('/webform/test_element_select');
    $assert_session->responseContains('<select data-drupal-selector="edit-select-empty-option-optional" id="edit-select-empty-option-optional" name="select_empty_option_optional" class="form-select"><option value="" selected="selected">- None -</option>');
    $assert_session->responseContains('<select data-drupal-selector="edit-select-empty-option-optional-default-value" id="edit-select-empty-option-optional-default-value" name="select_empty_option_optional_default_value" class="form-select"><option value="">- None -</option>');
    $assert_session->responseContains('<select data-drupal-selector="edit-select-empty-option-required" id="edit-select-empty-option-required" name="select_empty_option_required" class="form-select required" required="required" aria-required="true"><option value="" selected="selected">- Select -</option>');

    // Disable default empty option.
    \Drupal::configFactory()->getEditable('webform.settings')
      ->set('element.default_empty_option', FALSE)
      ->save();

    // Check default empty option is not always included.
    $this->drupalGet('/webform/test_element_select');
    $assert_session->responseNotContains('<select data-drupal-selector="edit-select-empty-option-optional" id="edit-select-empty-option-optional" name="select_empty_option_optional" class="form-select"><option value="" selected="selected">- None -</option>');
    $assert_session->responseNotContains('<select data-drupal-selector="edit-select-empty-option-optional-default-value" id="edit-select-empty-option-optional-default-value" name="select_empty_option_optional_default_value" class="form-select"><option value="">- None -</option>');
    $assert_session->responseContains('<select data-drupal-selector="edit-select-empty-option-required" id="edit-select-empty-option-required" name="select_empty_option_required" class="form-select required" required="required" aria-required="true"><option value="" selected="selected">- Select -</option>');

    // Set custom empty option values.
    \Drupal::configFactory()->getEditable('webform.settings')
      ->set('element.default_empty_option', TRUE)
      ->set('element.default_empty_option_required', '{required}')
      ->set('element.default_empty_option_optional', '{optional}')
      ->save();

    // Check customize empty option displayed.
    $this->drupalGet('/webform/test_element_select');
    $assert_session->responseContains('<select data-drupal-selector="edit-select-empty-option-optional" id="edit-select-empty-option-optional" name="select_empty_option_optional" class="form-select"><option value="" selected="selected">{optional}</option>');
    $assert_session->responseContains('<select data-drupal-selector="edit-select-empty-option-optional-default-value" id="edit-select-empty-option-optional-default-value" name="select_empty_option_optional_default_value" class="form-select"><option value="">{optional}</option>');
    $assert_session->responseContains('<select data-drupal-selector="edit-select-empty-option-required" id="edit-select-empty-option-required" name="select_empty_option_required" class="form-select required" required="required" aria-required="true"><option value="" selected="selected">{required}</option>');
  }

}
