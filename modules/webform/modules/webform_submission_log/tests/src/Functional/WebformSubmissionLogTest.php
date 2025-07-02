<?php

namespace Drupal\Tests\webform_submission_log\Functional;

use Drupal\Tests\webform\Functional\WebformBrowserTestBase;
use Drupal\Tests\webform_submission_log\Traits\WebformSubmissionLogTrait;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;

/**
 * Tests for webform submission log.
 *
 * @group webform_submission_log
 */
class WebformSubmissionLogTest extends WebformBrowserTestBase {

  use WebformSubmissionLogTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['webform', 'webform_submission_log'];

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_submission_log'];

  /**
   * Test webform submission log.
   */
  public function testSubmissionLog() {
    global $base_path;

    $assert_session = $this->assertSession();

    $admin_user = $this->drupalCreateUser([
      'administer webform',
      'access webform submission log',
    ]);

    $webform = Webform::load('test_submission_log');

    /* ********************************************************************** */

    // Check submission created.
    $sid_1 = $this->postSubmission($webform);
    $log = $this->getLastSubmissionLog();
    $this->assertEquals($log->lid, 1);
    $this->assertEquals($log->sid, 1);
    $this->assertEquals($log->uid, 0);
    $this->assertEquals($log->handler_id, '');
    $this->assertEquals($log->operation, 'submission created');
    $this->assertEquals($log->message, '@title created.');
    $this->assertEquals($log->variables, ['@title' => 'Test: Submission: Logging: Submission #1']);
    $this->assertEquals($log->webform_id, 'test_submission_log');
    $this->assertNull($log->entity_type);
    $this->assertNull($log->entity_id);

    // Check submission draft created.
    $sid_2 = $this->postSubmission($webform, ['value' => 'Test'], 'Save Draft');
    $log = $this->getLastSubmissionLog();
    $this->assertEquals($log->lid, 2);
    $this->assertEquals($log->sid, 2);
    $this->assertEquals($log->uid, 0);
    $this->assertEquals($log->handler_id, '');
    $this->assertEquals($log->operation, 'draft created');
    $this->assertEquals($log->message, '@title draft created.');
    $this->assertEquals($log->variables, ['@title' => 'Test: Submission: Logging: Submission #2']);
    $this->assertEquals($log->webform_id, 'test_submission_log');
    $this->assertNull($log->entity_type);
    $this->assertNull($log->entity_id);

    // Check submission draft updated.
    $this->postSubmission($webform, ['value' => 'Test'], 'Save Draft');
    $log = $this->getLastSubmissionLog();
    $this->assertEquals($log->lid, 3);
    $this->assertEquals($log->sid, 2);
    $this->assertEquals($log->uid, 0);
    $this->assertEquals($log->handler_id, '');
    $this->assertEquals($log->operation, 'draft updated');
    $this->assertEquals($log->message, '@title draft updated.');
    $this->assertEquals($log->variables, ['@title' => 'Test: Submission: Logging: Submission #2']);
    $this->assertEquals($log->webform_id, 'test_submission_log');
    $this->assertNull($log->entity_type);
    $this->assertNull($log->entity_id);

    // Check submission completed.
    $this->postSubmission($webform);
    $log = $this->getLastSubmissionLog();
    $this->assertEquals($log->lid, 4);
    $this->assertEquals($log->sid, 2);
    $this->assertEquals($log->uid, 0);
    $this->assertEquals($log->handler_id, '');
    $this->assertEquals($log->operation, 'submission completed');
    $this->assertEquals($log->message, '@title completed using saved draft.');
    $this->assertEquals($log->variables, ['@title' => 'Test: Submission: Logging: Submission #2']);
    $this->assertEquals($log->webform_id, 'test_submission_log');
    $this->assertNull($log->entity_type);
    $this->assertNull($log->entity_id);

    // Login admin user.
    $this->drupalLogin($admin_user);

    $submission_log = $this->getSubmissionLog();

    // Check submission #2 converted.
    $log = $submission_log[0];
    $this->assertEquals($log->lid, 6);
    $this->assertEquals($log->uid, $admin_user->id());
    $this->assertEquals($log->sid, 2);
    $this->assertEquals($log->operation, 'submission converted');
    $this->assertEquals($log->message, '@title converted from anonymous to @user.');
    $this->assertEquals($log->variables, ['@title' => 'Test: Submission: Logging: Submission #2', '@user' => $admin_user->label()]);

    // Check submission #1 converted.
    $log = $submission_log[1];
    $this->assertEquals($log->lid, 5);
    $this->assertEquals($log->uid, $admin_user->id());
    $this->assertEquals($log->sid, 1);
    $this->assertEquals($log->operation, 'submission converted');
    $this->assertEquals($log->message, '@title converted from anonymous to @user.');
    $this->assertEquals($log->variables, ['@title' => 'Test: Submission: Logging: Submission #1', '@user' => $admin_user->label()]);

    // Check submission updated.
    $this->drupalGet("admin/structure/webform/manage/test_submission_log/submission/$sid_2/edit");
    $this->submitForm([], 'Save');
    $log = $this->getLastSubmissionLog();
    $this->assertEquals($log->lid, 7);
    $this->assertEquals($log->sid, 2);
    $this->assertEquals($log->uid, $admin_user->id());
    $this->assertEquals($log->handler_id, '');
    /* ********************************************************************** */
    // $this->assertEqual($log->operation, 'submission completed');
    // $this->assertEqual($log->message, 'Test: Submission: Logging: Submission #2 completed using saved draft.');
    /* ********************************************************************** */
    $this->assertEquals($log->webform_id, 'test_submission_log');
    $this->assertNull($log->entity_type);
    $this->assertNull($log->entity_id);

    // Check submission deleted removes all log entries for this sid.
    $this->drupalGet("admin/structure/webform/manage/test_submission_log/submission/$sid_1/delete");
    $this->submitForm([], 'Delete');
    $this->drupalGet("admin/structure/webform/manage/test_submission_log/submission/$sid_2/delete");
    $this->submitForm([], 'Delete');
    $log = $this->getLastSubmissionLog();
    $this->assertFalse($log);

    // Check all results log table is empty.
    $this->drupalGet('/admin/structure/webform/submissions/log');
    $assert_session->responseContains('No log messages available.');

    // Check webform results log table is empty.
    $this->drupalGet('/admin/structure/webform/manage/test_submission_log/results/log');
    $assert_session->responseContains('No log messages available.');

    $sid_3 = $this->postSubmission($webform);
    WebformSubmission::load($sid_3);

    // Check all results log table has record.
    $this->drupalGet('/admin/structure/webform/submissions/log');
    $assert_session->responseNotContains('No log messages available.');
    $assert_session->responseContains('<a href="' . $base_path . 'admin/structure/webform/manage/test_submission_log/results/log">Test: Submission: Logging</a>');
    $assert_session->responseContains('<a href="' . $base_path . 'admin/structure/webform/manage/test_submission_log/submission/3/log">3</a></td>');
    $assert_session->responseContains('Test: Submission: Logging: Submission #3 created.');

    // Check webform results log table has record.
    $this->drupalGet('/admin/structure/webform/manage/test_submission_log/results/log');
    $assert_session->responseNotContains('No log messages available.');
    $assert_session->responseNotContains('<a href="' . $base_path . 'admin/structure/webform/manage/test_submission_log/results/log">Test: Submission: Logging</a>');
    $assert_session->responseContains('<a href="' . $base_path . 'admin/structure/webform/manage/test_submission_log/submission/3/log">3</a></td>');
    $assert_session->responseContains('Test: Submission: Logging: Submission #3 created.');
  }

}
