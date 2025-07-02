<?php

namespace Drupal\Tests\webform\Functional\Settings;

use Drupal\Tests\webform\Functional\WebformBrowserTestBase;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\webform\WebformSubmissionForm;

/**
 * Tests for disable tracking of remote IP address.
 *
 * @group webform
 */
class WebformSettingsRemoteAddrTest extends WebformBrowserTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_form_remote_addr'];

  /**
   * Tests webform disable remote IP address.
   */
  public function testRemoteAddr() {
    $this->drupalLogin($this->rootUser);

    // Get submission values and data.
    $values = [
      'webform_id' => 'test_form_remote_addr',
      'data' => [
        'name' => 'John',
      ],
    ];

    // Make sure the IP is not stored.
    $webform = Webform::load('test_form_remote_addr');
    $sid = $this->postSubmission($webform, ['name' => 'John']);
    $webform_submission = WebformSubmission::load($sid);
    $this->assertEquals($webform_submission->getRemoteAddr(), '(unknown)');
    $this->assertEquals($webform_submission->getOwnerId(), 1);

    $webform_submission = WebformSubmissionForm::submitFormValues($values);
    $this->assertEquals($webform_submission->getRemoteAddr(), '(unknown)');
    $this->assertEquals($webform_submission->getOwnerId(), 1);

    // Enable the setting and make sure the IP is stored.
    $webform->setSetting('form_disable_remote_addr', FALSE);
    $webform->save();
    $sid = $this->postSubmission($webform, ['name' => 'John']);
    $webform_submission = WebformSubmission::load($sid);
    $this->assertNotEquals($webform_submission->getRemoteAddr(), '(unknown)');
    $this->assertEquals($webform_submission->getOwnerId(), 1);

    $webform_submission = WebformSubmissionForm::submitFormValues($values);
    $this->assertNotEquals($webform_submission->getRemoteAddr(), '(unknown)');
    $this->assertEquals($webform_submission->getOwnerId(), 1);
  }

}
