<?php

namespace Drupal\Tests\webform\Functional\States;

use Drupal\Tests\webform\Functional\WebformBrowserTestBase;
use Drupal\webform\Entity\Webform;

/**
 * Tests for webform states preview.
 *
 * @group webform
 */
class WebformStatesPreviewTest extends WebformBrowserTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = [
    'test_states_server_preview',
    'test_states_server_save',
    'test_states_server_clear',
  ];

  /**
   * Tests visible conditions (#states) validator for elements .
   */
  public function testStatesValidatorElementVisible() {
    $assert_session = $this->assertSession();

    $webform_preview = Webform::load('test_states_server_preview');

    // Check trigger unchecked and elements are conditionally hidden.
    $this->postSubmission($webform_preview, [], 'Preview');
    $assert_session->responseContains('trigger_checkbox');
    $assert_session->responseNotContains('dependent_checkbox');
    $assert_session->responseNotContains('dependent_markup');
    $assert_session->responseNotContains('dependent_message');
    $assert_session->responseNotContains('dependent_fieldset');
    $assert_session->responseNotContains('nested_textfield');

    // Check trigger checked and elements are conditionally visible.
    $this->postSubmission($webform_preview, ['trigger_checkbox' => TRUE], 'Preview');
    $assert_session->responseContains('trigger_checkbox');
    $assert_session->responseContains('dependent_checkbox');
    $assert_session->responseContains('dependent_markup');
    $assert_session->responseContains('dependent_message');
    $assert_session->responseContains('dependent_fieldset');
    $assert_session->responseContains('nested_textfield');

    $webform_save = Webform::load('test_states_server_save');

    // Check trigger unchecked and saved.
    $this->postSubmission($webform_save, ['trigger_checkbox' => FALSE], 'Submit');
    $assert_session->responseContains("trigger_checkbox: 0
dependent_hidden: ''
dependent_checkbox: ''
dependent_value: ''
dependent_textfield: ''
dependent_textfield_multiple: {  }
dependent_details_textfield: ''");

    // Check trigger checked and saved.
    $this->postSubmission($webform_save, ['trigger_checkbox' => TRUE], 'Submit');
    $assert_session->responseContains("trigger_checkbox: 1
dependent_hidden: '{dependent_hidden}'
dependent_checkbox: 0
dependent_value: '{value}'
dependent_textfield: '{dependent_textfield}'
dependent_textfield_multiple:
  - '{dependent_textfield}'
dependent_details_textfield: '{dependent_details_textfield}'");

    $webform_clear = Webform::load('test_states_server_clear');

    // Check trigger unchecked and not cleared.
    $this->postSubmission($webform_clear, ['trigger_checkbox' => FALSE], 'Submit');
    $assert_session->responseContains("trigger_checkbox: 0
dependent_hidden: '{dependent_hidden}'
dependent_checkbox: 1
dependent_radios: One
dependent_value: '{value}'
dependent_textfield: '{dependent_textfield}'
dependent_textfield_multiple:
  - '{dependent_textfield}'
dependent_webform_name:
  - title: ''
    first: John
    middle: ''
    last: Smith
    suffix: ''
    degree: ''
dependent_details_textfield: '{dependent_details_textfield}'");
  }

}
