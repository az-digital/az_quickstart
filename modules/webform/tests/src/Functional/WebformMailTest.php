<?php

namespace Drupal\Tests\webform\Functional;

/**
 * Performs tests on the pluggable mailing framework.
 *
 * @group webform_browser
 */
class WebformMailTest extends WebformBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['webform'];

  /**
   * Checks the From: and Reply-to: headers.
   */
  public function testFromAndReplyToHeader() {
    $language = \Drupal::languageManager()->getCurrentLanguage();

    // Use the state system collector mail backend.
    $this->config('system.mail')->set('interface.default', 'test_mail_collector')->save();
    // Reset the state variable that holds sent messages.
    \Drupal::state()->set('system.test_mail_collector', []);
    // Send an email with a reply-to address specified.
    $from_email = 'simpletest@example.com';
    $reply_email = 'webform@example.com';

    // Send an email and check that the From-header contains the from_name.
    \Drupal::service('plugin.manager.mail')->mail('webform', '', 'from_test@example.com', $language, ['subject' => '', 'body' => '', 'from_mail' => $from_email, 'from_name' => 'DrÃ©pal'], $reply_email);

    $captured_emails = \Drupal::state()->get('system.test_mail_collector');
    $sent_message = end($captured_emails);
    $this->assertEquals($sent_message['headers']['From'], '=?utf-8?Q?Dr=C3=83=C2=A9pal?= <simpletest@example.com>', 'From header is correctly encoded.');
  }

}
