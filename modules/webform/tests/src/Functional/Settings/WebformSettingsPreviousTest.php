<?php

namespace Drupal\Tests\webform\Functional\Settings;

use Drupal\Tests\webform\Functional\WebformBrowserTestBase;
use Drupal\webform\Entity\Webform;

/**
 * Tests for webform submission form previous.
 *
 * @group webform
 */
class WebformSettingsPreviousTest extends WebformBrowserTestBase {

  /**
   * Test webform submission form previous submission(s).
   */
  public function testPrevious() {
    global $base_path;

    $assert_session = $this->assertSession();

    $this->drupalLogin($this->rootUser);

    $webform = Webform::load('contact');

    /* ********************************************************************** */
    // Previous submission message.
    /* ********************************************************************** */

    // Create single submission.
    $sid_1 = $this->postSubmissionTest($webform);

    // Check default global previous submission message.
    $this->drupalGet('/webform/contact');
    $assert_session->responseContains("You have already submitted this webform. <a href=\"{$base_path}webform/contact/submissions/{$sid_1}\">View your previous submission</a>.");

    // Check custom global previous submission message.
    $this->config('webform.settings')
      ->set('settings.default_previous_submission_message', '{default_previous_submission}')
      ->save();
    $this->drupalGet('/webform/contact');
    $assert_session->responseContains('{default_previous_submission}');

    // Check custom webform previous submission message.
    $webform
      ->setSetting('previous_submission_message', '{custom_previous_submission}')
      ->save();
    $this->drupalGet('/webform/contact');
    $assert_session->responseContains('{custom_previous_submission}');

    /* ********************************************************************** */
    // Previous submissions message.
    /* ********************************************************************** */

    // Create second submission.
    $this->postSubmissionTest($webform);

    // Check default global previous submissions message.
    $this->drupalGet('/webform/contact');
    $assert_session->responseContains("You have already submitted this webform. <a href=\"{$base_path}webform/contact/submissions\">View your previous submissions</a>.");

    // Check custom global previous submissions message.
    $this->config('webform.settings')
      ->set('settings.default_previous_submissions_message', '{default_previous_submissions}')
      ->save();
    $this->drupalGet('/webform/contact');
    $assert_session->responseContains('{default_previous_submissions}');

    // Check custom webform previous submissions message.
    $webform
      ->setSetting('previous_submissions_message', '{custom_previous_submissions}')
      ->save();
    $this->drupalGet('/webform/contact');
    $assert_session->responseContains('{custom_previous_submissions}');
  }

}
