<?php

namespace Drupal\Tests\webform\Functional\Handler;

use Drupal\Tests\webform\Functional\WebformBrowserTestBase;
use Drupal\webform\Entity\Webform;

/**
 * Tests for email webform handler #options mapping functionality.
 *
 * @group webform
 */
class WebformHandlerEmailMappingTest extends WebformBrowserTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_handler_email_mapping'];

  /**
   * Test email mapping handler.
   */
  public function testEmailMapping() {
    $assert_session = $this->assertSession();

    $site_name = \Drupal::config('system.site')->get('name');
    $site_mail = \Drupal::config('system.site')->get('mail');

    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = Webform::load('test_handler_email_mapping');

    $this->postSubmission($webform);

    // Check that empty select menu email sent.
    $assert_session->pageTextContains("Select empty sent to empty@example.com from $site_name [$site_mail].");

    // Check that default select menu email sent.
    $assert_session->pageTextContains("Select default sent to default@default.com from $site_name [$site_mail].");

    // Check that no email sent.
    $assert_session->pageTextContains('Email not sent for Select yes option handler because a To, CC, or BCC email was not provided.');
    $assert_session->pageTextContains('Email not sent for Checkboxes handler because a To, CC, or BCC email was not provided.');
    $assert_session->pageTextContains('Email not sent for Radios other handler because a To, CC, or BCC email was not provided.');

    // Check that single select menu option email sent.
    $edit = [
      'select' => 'Yes',
    ];
    $this->postSubmission($webform, $edit);
    $assert_session->pageTextContains("Select yes option sent to yes@example.com from $site_name [$site_mail].");
    $assert_session->pageTextContains("Select default sent to default@default.com from $site_name [$site_mail].");
    $assert_session->pageTextNotContains("'Select empty' sent to empty@example.com from $site_name [$site_mail].");

    // Check that multiple radios checked email sent.
    $edit = [
      'checkboxes[Saturday]' => TRUE,
      'checkboxes[Sunday]' => TRUE,
    ];
    $this->postSubmission($webform, $edit);
    $assert_session->pageTextContains("Checkboxes sent to saturday@example.com,sunday@example.com from $site_name [$site_mail].");
    $assert_session->pageTextNotContains('Email not sent for Checkboxes handler because a To, CC, or BCC email was not provided.');

    // Check that checkboxes other option email sent.
    $edit = [
      'radios_other[radios]' => '_other_',
      'radios_other[other]' => '{Other}',
    ];
    $this->postSubmission($webform, $edit);
    $assert_session->pageTextContains("Radios other sent to other@example.com from $site_name [$site_mail].");
    $assert_session->pageTextNotContains('Email not sent for Radios other handler because a To, CC, or BCC email was not provided.');
  }

}
