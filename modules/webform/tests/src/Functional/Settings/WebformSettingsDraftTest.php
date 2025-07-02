<?php

namespace Drupal\Tests\webform\Functional\Settings;

use Drupal\Component\Utility\Html;
use Drupal\Tests\webform\Functional\WebformBrowserTestBase;
use Drupal\user\Entity\User;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;

/**
 * Tests for webform submission form draft.
 *
 * @group webform
 */
class WebformSettingsDraftTest extends WebformBrowserTestBase {

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_form_draft_authenticated', 'test_form_draft_anonymous', 'test_form_draft_multiple', 'test_form_preview'];

  /**
   * Test webform submission form draft.
   */
  public function testDraft() {
    $assert_session = $this->assertSession();

    /** @var \Drupal\webform\WebformSubmissionStorageInterface $webform_submission_storage */
    $webform_submission_storage = \Drupal::entityTypeManager()->getStorage('webform_submission');

    $normal_user = $this->drupalCreateUser(['view own webform submission']);

    $admin_submission_user = $this->drupalCreateUser([
      'administer webform submission',
    ]);

    /* ********************************************************************** */
    // Draft access.
    /* ********************************************************************** */

    // Check access denied to review drafts when disabled.
    $this->drupalGet('/webform/contact/drafts');
    $assert_session->statusCodeEquals(403);

    // Check access denied to review authenticated drafts.
    $this->drupalGet('/webform/test_form_draft_authenticated/drafts');
    $assert_session->statusCodeEquals(403);

    // Check access allowed to review anonymous drafts.
    $this->drupalGet('/webform/test_form_draft_anonymous/drafts');
    $assert_session->statusCodeEquals(200);

    /* ********************************************************************** */
    // Autosave for anonymous draft to authenticated draft.
    /* ********************************************************************** */

    $webform_ids = [
      'test_form_draft_authenticated' => 'Test: Webform: Draft authenticated',
      'test_form_draft_anonymous' => 'Test: Webform: Draft anonymous',
    ];
    foreach ($webform_ids as $webform_id => $webform_title) {
      $is_authenticated = ($webform_id === 'test_form_draft_authenticated') ? TRUE : FALSE;

      // Login draft account.
      ($is_authenticated) ? $this->drupalLogin($normal_user) : $this->drupalLogout();

      $webform = Webform::load($webform_id);

      // Save a draft.
      $sid = $this->postSubmission($webform, ['name' => 'John Smith'], 'Save Draft');
      /** @var \Drupal\webform\WebformSubmissionInterface $webform_submission */
      $webform_submission = WebformSubmission::load($sid);

      // Check saved draft message.
      $assert_session->responseContains('Your draft has been saved');
      $assert_session->responseNotContains('You have an existing draft');

      // Check access allowed to review drafts.
      $this->drupalGet("webform/$webform_id/drafts");
      $assert_session->statusCodeEquals(200);

      // Check draft title and info.
      $account = ($is_authenticated) ? $normal_user : User::getAnonymousUser();
      $assert_session->responseContains('<title>' . Html::escape('Drafts for ' . $webform->label() . ' for ' . ($account->getAccountName() ?: 'Anonymous') . ' | Drupal') . '</title>');
      $assert_session->responseContains('<div>1 draft</div>');

      // Check loaded draft message.
      $this->drupalGet("webform/$webform_id");
      $assert_session->responseNotContains('Your draft has been saved');
      $assert_session->responseContains('You have an existing draft');
      $assert_session->fieldValueEquals('name', 'John Smith');

      // Check no draft message when webform is closed.
      $webform->setStatus(FALSE)->save();
      $this->drupalGet("webform/$webform_id");
      $assert_session->responseNotContains('You have an existing draft');
      $assert_session->fieldNotExists('name');
      $assert_session->responseContains('Sorry… This form is closed to new submissions.');
      $webform->setStatus(TRUE)->save();

      // Login admin account.
      $this->drupalLogin($admin_submission_user);

      // Check submission.
      $this->drupalGet("admin/structure/webform/manage/$webform_id/submission/$sid");
      $assert_session->responseContains('<div><b>Is draft:</b> Yes</div>');

      // Login draft account.
      ($is_authenticated) ? $this->drupalLogin($normal_user) : $this->drupalLogout();

      // Check update draft and bypass validation.
      $this->drupalGet("webform/$webform_id");
      $edit = ['name' => '', 'comment' => 'Hello World!'];
      $this->submitForm($edit, 'Save Draft');
      $assert_session->responseContains('Your draft has been saved');
      $assert_session->responseNotContains('You have an existing draft');
      $assert_session->fieldValueEquals('name', '');
      $assert_session->fieldValueEquals('comment', 'Hello World!');

      // Check preview of draft with valid data.
      $this->drupalGet("webform/$webform_id");
      $edit = ['name' => 'John Smith', 'comment' => 'Hello World!'];
      $this->submitForm($edit, 'Preview');
      $assert_session->responseNotContains('Your draft has been saved');
      $assert_session->responseNotContains('You have an existing draft');
      $assert_session->fieldNotExists('name');
      $assert_session->fieldNotExists('comment');
      $assert_session->responseContains('<label>Name</label>');
      $assert_session->responseContains('<label>Comment</label>');
      $assert_session->responseContains('Hello World!');
      $assert_session->responseContains('Please review your submission. Your submission is not complete until you press the "Submit" button!');

      // Check submit.
      $this->drupalGet("webform/$webform_id");
      $this->submitForm([], 'Submit');
      $assert_session->responseContains("New submission added to $webform_title.");

      // Check submission not in draft.
      $this->drupalGet("webform/$webform_id");
      $assert_session->responseNotContains('Your draft has been saved');
      $assert_session->responseNotContains('You have an existing draft');
      $assert_session->fieldValueEquals('name', '');
      $assert_session->fieldValueEquals('comment', '');
    }

    /* ********************************************************************** */
    // Convert anonymous draft to authenticated draft.
    /* ********************************************************************** */

    $webform = Webform::load('test_form_draft_anonymous');

    // Save a draft.
    $sid = $this->postSubmission($webform, ['name' => 'John Smith'], 'Save Draft');
    $assert_session->responseContains('Your draft has been saved');

    // Check that submission is owned anonymous.
    $webform_submission_storage->resetCache();
    $webform_submission = WebformSubmission::load($sid);
    $this->assertEquals($webform_submission->getOwnerId(), 0);

    // Check loaded draft message.
    $this->drupalGet('/webform/test_form_draft_anonymous');
    $assert_session->responseContains('You have an existing draft');
    $assert_session->fieldValueEquals('name', 'John Smith');

    // Login the normal user.
    $this->drupalLogin($normal_user);

    // Check that submission is now owned by the normal user.
    $webform_submission_storage->resetCache();
    $webform_submission = WebformSubmission::load($sid);
    $this->assertEquals($webform_submission->getOwnerId(), $normal_user->id());

    // Check that drafts are not convert when form_convert_anonymous = FALSE.
    $this->drupalLogout();
    $webform->setSetting('form_convert_anonymous', FALSE)->save();

    $sid = $this->postSubmission($webform, ['name' => 'John Smith']);
    $this->drupalLogin($normal_user);

    // Check that submission is still owned by anonymous user.
    $webform_submission_storage->resetCache();
    $webform_submission = WebformSubmission::load($sid);
    $this->assertEquals($webform_submission->getOwnerId(), 0);

    // Logout.
    $this->drupalLogout();

    // Change 'test_form_draft_anonymous' to be confidential.
    $webform->setSetting('form_confidential', TRUE);

    // Save a draft.
    $sid = $this->postSubmission($webform, ['name' => 'John Smith'], 'Save Draft');
    $assert_session->responseContains('Your draft has been saved');

    // Check that submission is owned anonymous.
    $webform_submission_storage->resetCache();
    $webform_submission = WebformSubmission::load($sid);
    $this->assertEquals($webform_submission->getOwnerId(), 0);

    // Check loaded draft message does NOT appear on confidential submissions.
    $this->drupalGet('/webform/test_form_draft_anonymous');
    $assert_session->responseContains('You have an existing draft');

    // Login the normal user.
    $this->drupalLogin($normal_user);

    $webform_submission_storage->resetCache();
    $webform_submission = WebformSubmission::load($sid);
    // Check that submission is NOT owned by the normal user.
    $this->assertNotEquals($webform_submission->getOwnerId(), $normal_user->id());

    // Check that submission is still anonymous.
    $this->assertEquals($webform_submission->getOwnerId(), 0);

    /* ********************************************************************** */
    // Export.
    /* ********************************************************************** */

    $this->drupalLogin($admin_submission_user);

    // Check export with draft settings.
    $this->drupalGet('/admin/structure/webform/manage/test_form_draft_authenticated/results/download');
    $assert_session->fieldValueEquals('state', 'all');

    // Check export without draft settings.
    $this->drupalGet('/admin/structure/webform/manage/test_form_preview/results/download');
    $assert_session->fieldNotExists('state');

    // Check autosave on submit with validation errors.
    $this->drupalGet('/webform/test_form_draft_authenticated');
    $this->submitForm([], 'Submit');
    $assert_session->responseContains('Name field is required.');
    $this->drupalGet('/webform/test_form_draft_authenticated');
    $assert_session->responseContains('You have an existing draft');

    // Check autosave on preview.
    $this->drupalGet('/webform/test_form_draft_authenticated');
    $edit = ['name' => 'John Smith'];
    $this->submitForm($edit, 'Preview');
    $assert_session->responseContains('Please review your submission.');
    $this->drupalGet('/webform/test_form_draft_authenticated');
    $assert_session->responseContains('You have an existing draft');
    $assert_session->responseContains('<label>Name</label>' . PHP_EOL . '        John Smith');

    /* ********************************************************************** */
    // Test webform draft multiple.
    /* ********************************************************************** */

    $config = \Drupal::configFactory()->getEditable('webform.settings');
    $this->drupalLogin($normal_user);

    $webform = Webform::load('test_form_draft_multiple');

    // Save first draft.
    $sid_1 = $this->postSubmission($webform, ['name' => 'John Smith'], 'Save Draft');
    $assert_session->responseContains('Submission saved. You may return to this form later and it will restore the current values.');
    $webform_submission_1 = WebformSubmission::load($sid_1);

    // Check restore first draft.
    $this->drupalGet('/webform/test_form_draft_multiple');
    $assert_session->responseNotContains('You have saved drafts.');
    $assert_session->responseContains('You have a pending draft for this webform.');
    $assert_session->fieldValueEquals('name', '');

    // Check customizing default draft previous message.
    $default_draft_pending_single_message = $config->get('settings.default_draft_pending_single_message');
    $config->set('settings.default_draft_pending_single_message', '{default_draft_pending_single_message}')->save();
    $this->drupalGet('/webform/test_form_draft_multiple');
    $assert_session->responseNotContains('You have a pending draft for this webform.');
    $assert_session->responseContains('{default_draft_pending_single_message}');
    $config->set('settings.default_draft_pending_single_message', $default_draft_pending_single_message)->save();

    // Check customizing draft previous message.
    $webform->setSetting('draft_pending_single_message', '{draft_pending_single_message}')->save();
    $this->drupalGet('/webform/test_form_draft_multiple');
    $assert_session->responseNotContains('You have a pending draft for this webform.');
    $assert_session->responseContains('{draft_pending_single_message}');
    $webform->setSetting('draft_pending_single_message', '')->save();

    // Check load pending draft using token.
    $this->drupalGet('/webform/test_form_draft_multiple');
    $this->clickLink('Load your pending draft');
    $assert_session->fieldValueEquals('name', 'John Smith');
    $this->drupalGet('/webform/test_form_draft_multiple', ['query' => ['token' => $webform_submission_1->getToken()]]);
    $assert_session->fieldValueEquals('name', 'John Smith');

    // Check user drafts.
    $this->drupalGet('/webform/test_form_draft_multiple/drafts');
    $assert_session->responseContains('token=' . $webform_submission_1->getToken());

    // Save second draft.
    $sid_2 = $this->postSubmission($webform, ['name' => 'John Smith'], 'Save Draft');
    $webform_submission_2 = WebformSubmission::load($sid_2);
    $assert_session->responseContains('Submission saved. You may return to this form later and it will restore the current values.');
    $this->drupalGet('/webform/test_form_draft_multiple');
    $assert_session->responseNotContains('You have a pending draft for this webform.');
    $assert_session->responseContains('You have pending drafts for this webform. <a href="' . base_path() . 'webform/test_form_draft_multiple/drafts">View your pending drafts</a>.');

    // Check customizing default drafts previous message.
    $default_draft_pending_multiple_message = $config->get('settings.default_draft_pending_multiple_message');
    $config->set('settings.default_draft_pending_multiple_message', '{default_draft_pending_multiple_message}')->save();
    $this->drupalGet('/webform/test_form_draft_multiple');
    $assert_session->responseNotContains('You have pending drafts for this webform.');
    $assert_session->responseContains('{default_draft_pending_multiple_message}');
    $config->set('settings.default_draft_pending_multiple_message', $default_draft_pending_multiple_message)->save();

    // Check customizing drafts previous message.
    $webform->setSetting('draft_pending_multiple_message', '{draft_pending_multiple_message}')->save();
    $this->drupalGet('/webform/test_form_draft_multiple');
    $assert_session->responseNotContains('You have pending drafts for this webform.');
    $assert_session->responseContains('{draft_pending_multiple_message}');
    $webform->setSetting('draft_pending_multiple_message', '')->save();

    // Check user drafts now has second draft.
    $this->drupalGet('/webform/test_form_draft_multiple/drafts');
    $assert_session->responseContains('token=' . $webform_submission_1->getToken());
    $assert_session->responseContains('token=' . $webform_submission_2->getToken());

    // Check that anonymous user can't load drafts.
    $this->drupalLogout();
    $this->drupalGet('/webform/test_form_draft_multiple', ['query' => ['token' => $webform_submission_1->getToken()]]);
    $assert_session->fieldValueEquals('name', '');

    // Save third anonymous draft.
    $this->postSubmission($webform, ['name' => 'Jane Doe'], 'Save Draft');
    $assert_session->responseContains('Submission saved. You may return to this form later and it will restore the current values.');

    // Check restore third anonymous draft.
    $this->drupalGet('/webform/test_form_draft_multiple');
    $assert_session->responseNotContains('You have saved drafts.');
    $assert_session->responseContains('You have a pending draft for this webform.');
    $assert_session->fieldValueEquals('name', '');

    $this->drupalGet('/webform/test_form_draft_multiple');
    $this->clickLink('Load your pending draft');
    $assert_session->fieldValueEquals('name', 'Jane Doe');

    // Get the total number of drafts.
    $total_drafts = $webform_submission_storage->getTotal($webform, NULL, NULL, ['in_draft' => TRUE]);

    // Post form with validation errors.
    $this->postSubmission($webform);
    $assert_session->responseContains('Name field is required.');

    // Check that 1 additional draft was created.
    $total = $webform_submission_storage->getTotal($webform, NULL, NULL, ['in_draft' => TRUE]);
    $this->assertEquals($total_drafts + 1, $total);

    // Post the same form with validation errors.
    $this->submitForm([], 'Submit');
    $assert_session->responseContains('Name field is required.');

    // Check that only 1 additional draft exists.
    $total = $webform_submission_storage->getTotal($webform, NULL, NULL, ['in_draft' => TRUE]);
    $this->assertEquals($total_drafts + 1, $total);

    /* ********************************************************************** */
    // Test webform submission form reset draft.
    /* ********************************************************************** */

    $this->drupalLogin($this->rootUser);

    $webform = Webform::load('test_form_draft_authenticated');

    // Check saved draft.
    $sid = $this->postSubmission($webform, ['name' => 'John Smith'], 'Save Draft');
    $this->assertNotNull($sid);
    $webform_submission = WebformSubmission::load($sid);
    $this->assertEquals($sid, $webform_submission->id());

    // Check reset delete's the draft.
    $this->postSubmission($webform, [], 'Reset');
    $webform_submission_storage->resetCache();
    $webform_submission = WebformSubmission::load($sid);
    $this->assertNull($webform_submission);

    // Check submission with comment.
    $sid = $this->postSubmission($webform, ['name' => 'John Smith', 'comment' => 'This is a comment'], 'Save Draft');
    $this->postSubmission($webform);
    $webform_submission_storage->resetCache();
    $webform_submission = WebformSubmission::load($sid);
    $this->assertEquals('This is a comment', $webform_submission->getElementData('comment'));

    // Check submitted draft is not delete on reset.
    $this->drupalGet('/admin/structure/webform/manage/test_form_draft_authenticated/submission/' . $sid . '/edit');
    $edit = ['comment' => 'This is ignored'];
    $this->submitForm($edit, 'Reset');
    $webform_submission_storage->resetCache();
    $webform_submission = WebformSubmission::load($sid);
    $this->assertEquals($sid, $webform_submission->id());
    $this->assertEquals('This is a comment', $webform_submission->getElementData('comment'));
    $this->assertNotEquals('This is ignored', $webform_submission->getElementData('comment'));

    // Check total number of drafts.
    $total = $webform_submission_storage->getTotal($webform, NULL, $this->rootUser, ['in_draft' => TRUE]);
    $this->assertEquals(0, $total);
  }

}
