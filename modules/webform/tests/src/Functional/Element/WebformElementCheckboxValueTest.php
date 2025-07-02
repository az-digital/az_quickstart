<?php

namespace Drupal\Tests\webform\Functional\Element;

use Drupal\webform\Entity\Webform;

/**
 * Tests for webform checkbox value element.
 *
 * @group webform
 */
class WebformElementCheckboxValueTest extends WebformElementBrowserTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_checkbox_value'];

  /**
   * Tests checkbox value element.
   */
  public function testCheckboxValue() {
    $assert_session = $this->assertSession();

    $webform = Webform::load('test_element_checkbox_value');

    // Check submitted values.
    $this->postSubmission($webform);
    $assert_session->responseContains("checkbox_value_empty: ''
checkbox_value_filled: '{default_value}'
checkbox_value_select_other: Four");

    // Check validation.
    $edit = [
      'checkbox_value_empty[checkbox]' => TRUE,
    ];
    $this->postSubmission($webform, $edit);
    $assert_session->responseContains('Enter a value field is required.');

  }

}
