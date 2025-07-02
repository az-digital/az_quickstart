<?php

namespace Drupal\Tests\webform\Functional;

/**
 * Example of webform browser test.
 *
 * @group webform_browser
 */
class WebformExampleFunctionalTest extends WebformBrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['webform'];

  /**
   * Test get.
   */
  public function testGet() {
    $assert_session = $this->assertSession();

    $this->drupalGet('/webform/contact');
    $this->debug('hi');
    $assert_session->responseContains('Contact');
  }

}
