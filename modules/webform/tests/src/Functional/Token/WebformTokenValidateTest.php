<?php

namespace Drupal\Tests\webform\Functional\Token;

use Drupal\Tests\webform\Functional\WebformBrowserTestBase;

/**
 * Tests for webform token element validation.
 *
 * @group webform
 */
class WebformTokenValidateTest extends WebformBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['token'];

  /**
   * Test webform token element validation.
   */
  public function testWebformTokenValidate() {
    $assert_session = $this->assertSession();

    $this->drupalLogin($this->rootUser);

    // Check invalid token validation.
    $this->drupalGet('/admin/structure/webform/config');
    $edit = ['form_settings[default_form_open_message][value][value]' => '[webform:invalid]'];
    $this->submitForm($edit, 'Save configuration');
    $assert_session->responseContains('invalid tokens');
    $assert_session->responseContains('<em class="placeholder">Default open message</em> is using the following invalid tokens: [webform:invalid].');

    // Check valid token validation.
    $this->drupalGet('/admin/structure/webform/config');
    $edit = ['form_settings[default_form_open_message][value][value]' => '[webform:title]'];
    $this->submitForm($edit, 'Save configuration');
    $assert_session->responseNotContains('invalid tokens');
  }

}
