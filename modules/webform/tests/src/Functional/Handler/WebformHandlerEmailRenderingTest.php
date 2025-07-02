<?php

namespace Drupal\Tests\webform\Functional\Handler;

use Drupal\Core\Test\AssertMailTrait;
use Drupal\Tests\webform\Functional\WebformBrowserTestBase;
use Drupal\webform\Entity\Webform;

/**
 * Tests for email webform handler rendering functionality.
 *
 * @group webform
 */
class WebformHandlerEmailRenderingTest extends WebformBrowserTestBase {

  use AssertMailTrait;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Make sure we are using distinct default and administrative themes for
    // the duration of these tests.
    \Drupal::service('theme_installer')->install(['webform_test_olivero', 'claro']);
    $this->config('system.theme')
      ->set('default', 'webform_test_olivero')
      ->set('admin', 'claro')
      ->save();
  }

  /**
   * Test email handler rendering.
   */
  public function testEmailRendering() {
    $assert_session = $this->assertSession();

    $this->drupalLogin($this->rootUser);

    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = Webform::load('contact');

    // Check that we are currently using the olivero.theme.
    $this->drupalGet('/webform/contact');
    $assert_session->responseContains('core/themes/olivero/css/base/fonts.css');

    // Post submission and send emails.
    $edit = [
      'name' => 'Dixisset',
      'email' => 'test@test.com',
      'subject' => 'Testing contact webform from [site:name]',
      'message' => 'Please ignore this email.',
    ];
    $this->postSubmission($webform, $edit);

    // Check submitting contact form and sending emails using the
    // default olivero.theme.
    $sent_emails = $this->getMails();
    $this->assertStringContainsStringIgnoringCase('HEADER 1 (CONTACT_EMAIL_CONFIRMATION)', $sent_emails[0]['body']);
    $this->assertStringContainsString('Please ignore this email.', $sent_emails[0]['body']);
    $this->assertStringContainsString('address (contact_email_confirmation)', $sent_emails[0]['body']);
    $this->assertStringContainsStringIgnoringCase('HEADER 1 (GLOBAL)', $sent_emails[1]['body']);
    $this->assertStringContainsString('Please ignore this email.', $sent_emails[1]['body']);
    $this->assertStringContainsString('address (global)', $sent_emails[1]['body']);

    // Disable dedicated page which will cause the form to now use the
    // seven.theme.
    // @see \Drupal\webform\Theme\WebformThemeNegotiator
    $webform->setSetting('page', FALSE);
    $webform->save();

    // Check that we are now using the seven.theme.
    $this->drupalGet('/webform/contact');
    $assert_session->responseNotContains('core/themes/olivero/css/base/fonts.css');

    // Post submission and send emails.
    $this->postSubmission($webform, $edit);

    // Check submitting contact form and sending emails using the
    // seven.theme but the rendered the emails still use the default
    // olivero.theme.
    // @see \Drupal\webform\Plugin\WebformHandler\EmailWebformHandler::getMessage
    $sent_emails = $this->getMails();
    $this->assertStringContainsStringIgnoringCase('HEADER 1 (CONTACT_EMAIL_CONFIRMATION)', $sent_emails[2]['body']);
    $this->assertStringContainsString('Please ignore this email.', $sent_emails[2]['body']);
    $this->assertStringContainsString('address (contact_email_confirmation)', $sent_emails[2]['body']);
    $this->assertStringContainsStringIgnoringCase('HEADER 1 (GLOBAL)', $sent_emails[3]['body']);
    $this->assertStringContainsString('Please ignore this email.', $sent_emails[3]['body']);
    $this->assertStringContainsString('address (global)', $sent_emails[3]['body']);
  }

}
