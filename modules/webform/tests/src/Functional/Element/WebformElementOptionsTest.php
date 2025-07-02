<?php

namespace Drupal\Tests\webform\Functional\Element;

/**
 * Tests for webform element options.
 *
 * @group webform
 */
class WebformElementOptionsTest extends WebformElementBrowserTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_options'];

  /**
   * Tests options element.
   */
  public function testElementOptions() {
    $assert_session = $this->assertSession();

    // Check options maxlength.
    $this->drupalGet('/webform/test_element_options');
    $assert_session->responseContains('<input class="js-webform-options-sync form-text" data-drupal-selector="edit-webform-options-maxlength-options-items-0-value" type="text" id="edit-webform-options-maxlength-options-items-0-value" name="webform_options_maxlength[options][items][0][value]" value="one" size="60" maxlength="20" placeholder="Enter value…" />');
    $assert_session->responseContains('<input data-drupal-selector="edit-webform-options-maxlength-options-items-0-text" type="text" id="edit-webform-options-maxlength-options-items-0-text" name="webform_options_maxlength[options][items][0][text]" value="One" size="60" maxlength="20" placeholder="Enter text…" class="form-text" />');

    // Check default value handling.
    $this->drupalGet('/webform/test_element_options');
    $this->submitForm([], 'Submit');
    $assert_session->responseContains("webform_options: {  }
webform_options_default_value:
  one: One
  two: Two
  three: Three
webform_options_maxlength:
  one: One
  two: Two
  three: Three
webform_options_optgroup:
  'Group One':
    one: One
  'Group Two':
    two: Two
  'Group Three':
    three: Three
webform_element_options_entity: yes_no
webform_element_options_custom:
  one: One
  two: Two
  three: Three");

    // Check default value handling.
    $this->drupalGet('/webform/test_element_options');
    $edit = ['webform_element_options_custom[options]' => 'yes_no'];
    $this->submitForm($edit, 'Submit');
    $assert_session->responseContains("webform_element_options_custom: yes_no");

    // Check unique option value validation.
    $this->drupalGet('/webform/test_element_options');
    $edit = [
      'webform_options[options][items][0][value]' => 'test',
      'webform_options[options][items][1][value]' => 'test',
    ];
    $this->submitForm($edit, 'Submit');
    $assert_session->responseContains('The <em class="placeholder">Option value</em> \'test\' is already in use. It must be unique.');
  }

}
