<?php

namespace Drupal\Tests\webform\Functional;

use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;

/**
 * Tests for webform results disabled.
 *
 * @group webform
 */
class WebformResultsDisabledTest extends WebformBrowserTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_form_results_disabled'];

  /**
   * Tests webform setting including confirmation.
   */
  public function testSettings() {
    $assert_session = $this->assertSession();

    $this->drupalLogin($this->rootUser);

    // Check results disabled.
    $webform_results_disabled = Webform::load('test_form_results_disabled');
    $webform_submission = $this->postSubmission($webform_results_disabled);
    $this->assertNull($webform_submission, 'Submission not saved to the database.');

    // Check that error message is displayed and form is available for admins.
    $this->drupalGet('/webform/test_form_results_disabled');
    $assert_session->responseContains('This webform is currently not saving any submitted data.');
    $assert_session->buttonExists('Submit');
    $assert_session->responseNotContains('Unable to display this webform. Please contact the site administrator.');

    // Check that error message not displayed and form is disabled for everyone.
    $this->drupalLogout();
    $this->drupalGet('/webform/test_form_results_disabled');
    $assert_session->responseNotContains('This webform is currently not saving any submitted data.');
    $assert_session->buttonNotExists('Submit');
    $assert_session->responseContains('Unable to display this webform. Please contact the site administrator.');

    // Enabled ignore disabled results.
    $webform_results_disabled->setSetting('results_disabled_ignore', TRUE);
    $webform_results_disabled->save();
    $this->drupalLogin($this->rootUser);

    // Check that no error message is displayed and form is available for admins.
    $this->drupalGet('/webform/test_form_results_disabled');
    $assert_session->responseNotContains('This webform is currently not saving any submitted data.');
    $assert_session->responseNotContains('Unable to display this webform. Please contact the site administrator.');
    $assert_session->buttonExists('Submit');

    // Check that results tab is not accessible.
    $this->drupalGet('/admin/structure/webform/manage/test_form_results_disabled/results/submissions');
    $assert_session->statusCodeEquals(403);

    // Check that error message not displayed and form is enabled for everyone.
    $this->drupalLogout();
    $this->drupalGet('/webform/test_form_results_disabled');
    $assert_session->responseNotContains('This webform is currently not saving any submitted data.');
    $assert_session->responseNotContains('Unable to display this webform. Please contact the site administrator.');
    $assert_session->buttonExists('Submit');

    // Unset disabled results.
    $webform_results_disabled->setSetting('results_disabled', FALSE);
    $webform_results_disabled->save();

    // Login admin.
    $this->drupalLogin($this->rootUser);

    // Check that results tab is accessible.
    $this->drupalGet('/admin/structure/webform/manage/test_form_results_disabled/results/submissions');
    $assert_session->statusCodeEquals(200);

    // Post a submission.
    $sid = $this->postSubmissionTest($webform_results_disabled);
    $webform_submission = WebformSubmission::load($sid);

    // Check that submission is available.
    $this->drupalGet('/admin/structure/webform/manage/test_form_results_disabled/results/submissions');
    $assert_session->responseNotContains('This webform is currently not saving any submitted data');
    $assert_session->responseContains('>' . $webform_submission->serial() . '<');

    // Set disabled results.
    $webform_results_disabled->setSetting('results_disabled', TRUE);
    $webform_results_disabled->save();

    // Check that submission is still available with warning.
    $this->drupalGet('/admin/structure/webform/manage/test_form_results_disabled/results/submissions');
    $assert_session->responseContains('This webform is currently not saving any submitted data');
    $assert_session->responseContains('>' . $webform_submission->serial() . '<');

    // Delete the submission.
    $webform_submission->delete();

    // Check that results tab is not accessible.
    $this->drupalGet('/admin/structure/webform/manage/test_form_results_disabled/results/submissions');
    $assert_session->statusCodeEquals(403);
  }

}
