<?php

namespace Drupal\Tests\webform\Functional\Element;

/**
 * Tests for webform element multiple property.
 *
 * @group webform
 */
class WebformElementMultiplePropertyTest extends WebformElementBrowserTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_multiple_property'];

  /**
   * Tests multiple element.
   */
  public function testMultipleProperty() {
    $assert_session = $this->assertSession();

    // Check processing.
    $this->drupalGet('/webform/test_element_multiple_property');
    $this->submitForm([], 'Submit');
    $assert_session->responseContains('webform_element_multiple: false
webform_element_multiple_true: true
webform_element_multiple_false: false
webform_element_multiple_custom: 5
webform_element_multiple_disabled: 5
webform_element_multiple_true_access: true
webform_element_multiple_false_access: false
webform_element_multiple_custom_access: 5');
  }

}
