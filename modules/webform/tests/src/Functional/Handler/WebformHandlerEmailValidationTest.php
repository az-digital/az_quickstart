<?php

namespace Drupal\Tests\webform\Functional\Handler;

use Drupal\Tests\webform\Functional\WebformBrowserTestBase;
use Drupal\webform\Entity\Webform;

/**
 * Tests for basic email webform handler functionality.
 *
 * @group webform
 */
class WebformHandlerEmailValidationTest extends WebformBrowserTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_handler_email_validate'];

  /**
   * Test basic email handler.
   */
  public function testBasicEmailHandler() {
    $assert_session = $this->assertSession();

    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = Webform::load('test_handler_email_validate');

    /* ********************************************************************** */

    $email_fields = [
      'from_mail' => $this->t('From'),
      'to_mail' => $this->t('To'),
      'cc_mail' => $this->t('Cc'),
      'bcc_mail' => $this->t('Bcc'),
      'reply_to' => $this->t('Reply-to'),
      'return_path' => $this->t('Return path'),
      'sender_mail' => $this->t('Sender'),
    ];

    // Check invalid email address handling.
    foreach ($email_fields as $email_field_name => $email_field_label) {
      $edit = [$email_field_name => 'Not valid email'];
      $this->postSubmission($webform, $edit);
      $t_args = [
        '@type' => $email_field_label,
        '%form' => $webform->label(),
        '%handler' => 'Email',
        '%email' => 'Not valid email',
      ];
      if ($email_field_name === 'from_mail') {
        $assert_session->responseContains($this->t('%form: Email not sent for %handler handler because the <em>@type</em> email (%email) is not valid.', $t_args));
      }
      else {
        $assert_session->responseContains($this->t('%form: The <em>@type</em> email address (%email) for %handler handler  is not valid.', $t_args));
      }
    }

    // Check invalid and empty recipient.
    $edit = [
      'to_mail' => 'Not valid email',
      'cc_mail' => 'Not valid email',
      'bcc_mail' => 'Not valid email',
    ];
    $this->postSubmission($webform, $edit);
    foreach ($edit as $email_field_name => $email_field_value) {
      $t_args = [
        '@type' => $email_fields[$email_field_name],
        '%form' => $webform->label(),
        '%handler' => 'Email',
        '%email' => 'Not valid email',
      ];
      $assert_session->responseContains($this->t('%form: The <em>@type</em> email address (%email) for %handler handler  is not valid.', $t_args));
    }
    $assert_session->responseContains('<em class="placeholder">Test: Handler: Email validation</em>: Email not sent for <em class="placeholder">Email</em> handler because a <em>To</em>, <em>CC</em>, or <em>BCC</em> email was not provided.');
  }

}
