<?php

namespace Drupal\Tests\webform\Functional\Element;

/**
 * Tests for webform element XSS.
 *
 * @group webform
 */
class WebformElementXssTest extends WebformElementBrowserTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_xss'];

  /**
   * Test time element.
   */
  public function testTime() {
    $assert_session = $this->assertSession();
    $this->drupalGet('/webform/test_element_xss');
    $this->submitForm([], 'Submit');
    $assert_session->responseNotContains('<img src="https://www.drupal.org/" />');
  }

}
