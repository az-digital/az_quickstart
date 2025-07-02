<?php

namespace Drupal\Tests\webform\Functional\Element;

/**
 * Tests for email_multiple element.
 *
 * @group webform
 */
class WebformElementEmailMultipleTest extends WebformElementBrowserTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_email_multiple'];

  /**
   * Test email_multiple element.
   */
  public function testEmailMultiple() {
    $assert_session = $this->assertSession();

    // Check basic email multiple.
    $this->drupalGet('/webform/test_element_email_multiple');
    $assert_session->responseContains('<label for="edit-email-multiple-basic">email_multiple_basic</label>');
    $assert_session->responseContains('<input data-drupal-selector="edit-email-multiple-basic" aria-describedby="edit-email-multiple-basic--description" type="text" id="edit-email-multiple-basic" name="email_multiple_basic" value="" size="60" class="form-text webform-email-multiple" />');
    $assert_session->responseContains('Multiple email addresses may be separated by commas. Emails are only sent to cc and bcc addresses if a To email address is provided.');

    // Check email multiple invalid second email address.
    $this->drupalGet('/webform/test_element_email_multiple');
    $edit = ['email_multiple_basic' => 'example@example.com, Not a valid email address'];
    $this->submitForm($edit, 'Submit');
    $assert_session->responseContains('The email address <em class="placeholder">Not a valid email address</em> is not valid.');

    // Check email multiple invalid token email address.
    $this->drupalGet('/webform/test_element_email_multiple');
    $edit = ['email_multiple_basic' => 'example@example.com, [token]'];
    $this->submitForm($edit, 'Submit');
    $assert_session->responseContains('The email address <em class="placeholder">[token]</em> is not valid.');

    // Check email multiple valid second email address.
    $this->drupalGet('/webform/test_element_email_multiple');
    $edit = ['email_multiple_basic' => 'example@example.com, other@other.com'];
    $this->submitForm($edit, 'Submit');
    $assert_session->responseContains("email_multiple_basic: 'example@example.com, other@other.com'");

    // Check email multiple valid token email address (via #allow_tokens).
    $this->drupalGet('/webform/test_element_email_multiple');
    $edit = ['email_multiple_advanced' => 'example@example.com, [token], [token1]@[token2].com'];
    $this->submitForm($edit, 'Submit');
    $assert_session->responseContains("email_multiple_advanced: 'example@example.com, [token], [token1]@[token2].com'");
  }

}
