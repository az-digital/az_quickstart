<?php

namespace Drupal\Tests\webform\Functional;

use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;

/**
 * Tests for operations on a webform submission using a tokenized URL.
 *
 * @group webform
 */
class WebformSubmissionTokenOperationsTest extends WebformBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['token'];

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_token_operations'];

  /**
   * Test operations on a webform submission using a tokenized URL.
   */
  public function testTokenOperationsTest() {
    $assert_session = $this->assertSession();

    $normal_user = $this->drupalCreateUser();

    $webform = Webform::load('test_token_operations');

    $token_operations = ['view', 'update', 'delete'];

    // Post test submission.
    $sid = $this->postSubmission($webform, ['textfield' => 'test']);
    /** @var \Drupal\webform\WebformSubmissionInterface $webform_submission */
    $webform_submission = WebformSubmission::load($sid);

    /* ********************************************************************** */

    // Check confirmation page's operations (view, update, & delete) token URLs.
    foreach ($token_operations as $token_operation) {
      $token_url = $webform_submission->getTokenUrl($token_operation);
      $link_label = $token_url->setAbsolute(FALSE)->toString();
      $link_url = $token_url->setAbsolute(TRUE)->toString();
      $assert_session->responseContains('<a href="' . $link_url . '">' . $link_label . '</a>');
    }

    /* ********************************************************************** */
    /* View */
    /* ********************************************************************** */

    // Check token view access allowed.
    $this->drupalLogin($normal_user);
    $this->drupalGet($webform_submission->getTokenUrl('view'));
    $assert_session->statusCodeEquals(200);
    $assert_session->responseContains('Submission information');
    $assert_session->responseContains('<label>textfield</label>');

    // Check that the 'Delete submission' link has token appended to it.
    $assert_session->linkByHrefExists($webform_submission->getTokenUrl('delete')->setAbsolute(FALSE)->toString());

    // Check token view access denied.
    $webform->setSetting('token_view', FALSE)->save();
    $this->drupalLogin($normal_user);
    $this->drupalGet($webform_submission->getTokenUrl('view'));
    $assert_session->statusCodeEquals(403);
    $assert_session->responseNotContains('Submission information');
    $assert_session->responseNotContains('<label>textfield</label>');

    /* ********************************************************************** */
    /* Update */
    /* ********************************************************************** */

    // Check token update access allowed.
    $this->drupalLogin($normal_user);
    $this->drupalGet($webform_submission->getTokenUrl('update'));
    $assert_session->statusCodeEquals(200);
    $assert_session->responseContains('Submission information');
    $assert_session->fieldValueEquals('textfield', $webform_submission->getElementData('textfield'));

    // Check token update does not load the submission.
    $webform->setSetting('token_update', FALSE)->save();
    $this->drupalLogin($normal_user);
    $this->drupalGet($webform_submission->getTokenUrl('update'));
    $assert_session->statusCodeEquals(200);
    $assert_session->responseNotContains('Submission information');
    $assert_session->fieldValueNotEquals('textfield', $webform_submission->getElementData('textfield'));

    /* ********************************************************************** */
    /* Delete */
    /* ********************************************************************** */

    // Check token delete access allowed.
    $this->drupalLogin($normal_user);
    $this->drupalGet($webform_submission->getTokenUrl('delete'));
    $assert_session->statusCodeEquals(200);

    // Check token delete access denied.
    $webform->setSetting('token_delete', FALSE)->save();
    $this->drupalLogin($normal_user);
    $this->drupalGet($webform_submission->getTokenUrl('delete'));
    $assert_session->statusCodeEquals(403);

    /* ********************************************************************** */
    /* Anonymous */
    /* ********************************************************************** */

    // Logout and switch to anonymous user.
    $this->drupalLogout();

    // Set access to authenticated only and reenabled tokenized URL.
    $access = $webform->getAccessRules();
    $access['create']['roles'] = ['authenticated'];
    $webform->setAccessRules($access);
    $webform
      ->setSetting('token_view', TRUE)
      ->setSetting('token_update', TRUE)
      ->setSetting('token_delete', TRUE)
      ->save();

    // Check that access is denied for anonymous user.
    $this->drupalGet('/webform/test_token_operations');
    $assert_session->statusCodeEquals(403);

    // Check token operations are allowed for anonymous user.
    foreach ($token_operations as $token_operation) {
      $this->drupalGet($webform_submission->getTokenUrl($token_operation));
      $assert_session->statusCodeEquals(200);
    }
  }

}
