<?php

namespace Drupal\Tests\webform\Functional\Element;

use Drupal\webform\Entity\Webform;

/**
 * Tests for webform checkbox element.
 *
 * @group webform
 */
class WebformElementCheckboxTest extends WebformElementBrowserTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_checkbox'];

  /**
   * Tests checkbox value element.
   */
  public function testCheckboxValue() {
    $assert_session = $this->assertSession();

    $this->drupalLogin($this->rootUser);
    $webform = Webform::load('test_element_checkbox');

    // Check submitted values.
    $edit = [
      'checkbox' => TRUE,
      'checkbox_raw' => TRUE,
      'checkbox_return_value' => 'custom_return_value',
      'checkbox_return_value_raw' => 'custom_return_value_raw',
    ];
    $sid = $this->postSubmission($webform, $edit);
    $assert_session->responseContains("checkbox: 1
checkbox_raw: 1
checkbox_return_value: custom_return_value
checkbox_return_value_raw: custom_return_value_raw");

    // Check display of value and raw.
    $this->drupalGet('/admin/structure/webform/manage/test_element_checkbox/submission/' . $sid);
    $assert_session->responseMatches('#<label>checkbox</label>\s+Yes\s+</div>#');
    $assert_session->responseMatches('#<label>checkbox_raw</label>\s+1\s+</div>#');
    $assert_session->responseMatches('#<label>checkbox_return_value</label>\s+Yes\s+</div>#');
    $assert_session->responseMatches('#<label>checkbox_return_value_raw</label>\s+custom_return_value_raw\s+</div>#');
  }

}
