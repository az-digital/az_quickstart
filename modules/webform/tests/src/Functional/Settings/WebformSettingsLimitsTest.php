<?php

namespace Drupal\Tests\webform\Functional\Settings;

use Drupal\Tests\webform\Functional\WebformBrowserTestBase;
use Drupal\user\Entity\Role;
use Drupal\webform\Entity\Webform;

/**
 * Tests for webform submission form limits.
 *
 * @group webform
 */
class WebformSettingsLimitsTest extends WebformBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['webform', 'block'];

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_form_limit', 'test_form_limit_wait'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Place webform test blocks.
    $this->placeWebformBlocks('webform_test_block_submission_limit');
  }

  /**
   * Tests webform submission form limits.
   */
  public function testFormLimits() {
    $assert_session = $this->assertSession();

    $own_submission_user = $this->drupalCreateUser([
      'view own webform submission',
      'edit own webform submission',
      'delete own webform submission',
      'access webform submission user',
    ]);

    $webform_limit = Webform::load('test_form_limit');

    /* ********************************************************************** */

    $this->drupalGet('/webform/test_form_limit');

    // Check webform available.
    $assert_session->buttonExists('Submit');

    // Check submission limit blocks.
    $assert_session->responseContains('0 user submission(s)');
    $assert_session->responseContains('1 user limit (every minute)');
    $assert_session->responseContains('0 webform submission(s)');
    $assert_session->responseContains('4 webform limit (every minute)');

    // Check submission limit tokens.
    $assert_session->responseContains('limit:webform: 4');
    $assert_session->responseContains('remaining:webform: 4');
    $assert_session->responseContains('limit:user: 1');
    $assert_session->responseContains('remaining:user: 1');

    $this->drupalLogin($own_submission_user);

    // Check that draft does not count toward limit.
    $this->postSubmission($webform_limit, [], 'Save Draft');
    $this->drupalGet('/webform/test_form_limit');
    $assert_session->buttonExists('Submit');
    $assert_session->responseContains('A partially-completed form was found. Please complete the remaining portions.');
    $assert_session->responseNotContains('You are only allowed to have 1 submission for this webform.');

    // Check submission limit blocks do not count draft.
    $assert_session->responseContains('0 user submission(s)');
    $assert_session->responseContains('0 webform submission(s)');

    // Check limit reached and webform not available for authenticated user.
    $sid = $this->postSubmission($webform_limit);
    $this->drupalGet('/webform/test_form_limit');
    $assert_session->buttonNotExists('Submit');
    $assert_session->responseContains('You are only allowed to have 1 submission for this webform.');

    // Check submission limit blocks do count submission.
    $assert_session->responseContains('1 user submission(s)');
    $assert_session->responseContains('1 webform submission(s)');

    // Check authenticated user can edit own submission.
    $this->drupalGet("admin/structure/webform/manage/test_form_limit/submission/$sid/edit");
    $assert_session->responseNotContains('You are only allowed to have 1 submission for this webform.');
    $assert_session->buttonExists('Save');

    $this->drupalLogout();

    // Check admin post submission.
    $this->drupalLogin($this->rootUser);
    $sid = $this->postSubmission($webform_limit);
    $this->drupalGet("admin/structure/webform/manage/test_form_limit/submission/$sid/edit");
    $assert_session->buttonExists('Save');
    $assert_session->responseNotContains('No more submissions are permitted.');

    // Check submission limit tokens do count submission.
    $assert_session->responseContains('remaining:webform: 2');
    $assert_session->responseContains('remaining:user: 0');

    // Check submission limit blocks.
    $assert_session->responseContains('1 user submission(s)');
    $assert_session->responseContains('2 webform submission(s)');

    $this->drupalLogout();

    // Allow anonymous users to edit own submission.
    $role = Role::load('anonymous');
    $role->grantPermission('edit own webform submission');
    $role->save();

    // Check webform is still available for anonymous users.
    $this->drupalGet('/webform/test_form_limit');
    $assert_session->buttonExists('Submit');
    $assert_session->responseNotContains('You are only allowed to have 1 submission for this webform.');

    // Add 1 more submissions as an anonymous user making the total number of
    // submissions equal to 3.
    $sid = $this->postSubmission($webform_limit);

    // Check submission limit blocks.
    $assert_session->responseContains('1 user submission(s)');
    $assert_session->responseContains('3 webform submission(s)');

    // Check limit reached and webform not available for anonymous user.
    $this->drupalGet('/webform/test_form_limit');
    $assert_session->buttonNotExists('Submit');
    $assert_session->responseContains('You are only allowed to have 1 submission for this webform.');

    // Check authenticated user can edit own submission.
    $this->drupalGet("admin/structure/webform/manage/test_form_limit/submission/$sid/edit");
    $assert_session->responseNotContains('You are only allowed to have 1 submission for this webform.');
    $assert_session->buttonExists('Save');

    // Add 1 more submissions as an root user making the total number of
    // submissions equal to 4.
    $this->drupalLogin($this->rootUser);
    $this->postSubmission($webform_limit);
    $this->drupalLogout();

    // Check total limit.
    $this->drupalGet('/webform/test_form_limit');
    $assert_session->buttonNotExists('Submit');
    $assert_session->responseContains('Only 4 submissions are allowed.');
    $assert_session->responseNotContains('You are only allowed to have 1 submission for this webform.');

    // Check submission limit blocks.
    $assert_session->responseContains('0 user submission(s)');
    $assert_session->responseContains('4 webform submission(s)');

    // Check admin can still post submissions.
    $this->drupalLogin($this->rootUser);
    $this->drupalGet('/webform/test_form_limit');
    $assert_session->buttonExists('Submit');
    $assert_session->responseContains('Only 4 submissions are allowed.');
    $assert_session->responseContains('Only submission administrators are allowed to access this webform and create new submissions.');

    // Check submission limit blocks.
    $assert_session->responseContains('2 user submission(s)');
    $assert_session->responseContains('4 webform submission(s)');

    // Change submission completed to 1 hour ago.
    \Drupal::database()->query('UPDATE {webform_submission} SET completed = :completed', [':completed' => strtotime('-1 minute')]);

    // Check submission limit blocks are removed because the submission
    // intervals have passed.
    $this->drupalGet('/webform/test_form_limit');
    $assert_session->responseContains('0 user submission(s)');
    $assert_session->responseContains('0 webform submission(s)');

    /* ********************************************************************** */
    // Wait.
    /* ********************************************************************** */

    $webform_limit_wait = Webform::load('test_form_limit_wait');

    $this->postSubmission($webform_limit_wait);

    $this->drupalGet('/webform/test_form_limit_wait');
    $assert_session->responseMatches('/webform_submission:interval:user:wait =&gt; \d+ seconds/');
  }

}
