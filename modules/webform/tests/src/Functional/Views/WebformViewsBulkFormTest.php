<?php

namespace Drupal\Tests\webform\Functional\Views;

use Drupal\Tests\webform\Functional\WebformBrowserTestBase;
use Drupal\webform\Entity\Webform;

/**
 * Tests a webform submission bulk form.
 *
 * @group webform
 * @see \Drupal\webform\Plugin\views\field\WebformSubmissionBulkForm
 */
class WebformViewsBulkFormTest extends WebformBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['webform', 'webform_test_views'];

  /**
   * Tests the webform views bulk form.
   */
  public function testViewsBulkForm() {
    $assert_session = $this->assertSession();

    $admin_submission_user = $this->drupalCreateUser([
      'administer webform submission',
    ]);

    /* ********************************************************************** */

    $this->drupalLogin($admin_submission_user);

    // Check no submissions.
    $this->drupalGet('/admin/structure/webform/test/views_bulk_form');
    $assert_session->responseContains('No submissions available.');

    // Create a test submission.
    $this->drupalLogin($this->rootUser);
    $webform = Webform::load('contact');
    $sid = $this->postSubmissionTest($webform);
    $webform_submission = $this->loadSubmission($sid);

    $this->drupalLogin($admin_submission_user);

    // Check make sticky action.
    $this->assertFalse($webform_submission->isSticky(), 'Webform submission is not sticky');
    $this->drupalGet('/admin/structure/webform/test/views_bulk_form');
    $edit = [
      'webform_submission_bulk_form[0]' => TRUE,
      'action' => 'webform_submission_make_sticky_action',
    ];
    $this->submitForm($edit, 'Apply to selected items');
    $webform_submission = $this->loadSubmission($webform_submission->id());
    $this->assertTrue($webform_submission->isSticky(), 'Webform submission has been made sticky');

    // Check make unsticky action.
    $this->drupalGet('/admin/structure/webform/test/views_bulk_form');
    $edit = [
      'webform_submission_bulk_form[0]' => TRUE,
      'action' => 'webform_submission_make_unsticky_action',
    ];
    $this->submitForm($edit, 'Apply to selected items');
    $webform_submission = $this->loadSubmission($webform_submission->id());
    $this->assertFalse($webform_submission->isSticky(), 'Webform submission is not sticky anymore');

    // Check make lock action.
    $this->assertFalse($webform_submission->isLocked(), 'Webform submission is not locked');
    $this->drupalGet('/admin/structure/webform/test/views_bulk_form');
    $edit = [
      'webform_submission_bulk_form[0]' => TRUE,
      'action' => 'webform_submission_make_lock_action',
    ];
    $this->submitForm($edit, 'Apply to selected items');
    $webform_submission = $this->loadSubmission($webform_submission->id());
    $this->assertTrue($webform_submission->isLocked(), 'Webform submission has been locked');

    // Check make locked action.
    $this->drupalGet('/admin/structure/webform/test/views_bulk_form');
    $edit = [
      'webform_submission_bulk_form[0]' => TRUE,
      'action' => 'webform_submission_make_unlock_action',
    ];
    $this->submitForm($edit, 'Apply to selected items');
    $webform_submission = $this->loadSubmission($webform_submission->id());
    $this->assertFalse($webform_submission->isLocked(), 'Webform submission is not locked anymore');

    // Check delete action.
    $this->drupalGet('/admin/structure/webform/test/views_bulk_form');
    $edit = [
      'webform_submission_bulk_form[0]' => TRUE,
      'action' => 'webform_submission_delete_action',
    ];
    $this->submitForm($edit, 'Apply to selected items');
    $this->submitForm(['confirm_input' => TRUE], 'Delete');

    $webform_submission = $this->loadSubmission($webform_submission->id());
    $this->assertNull($webform_submission, '1: Webform submission has been deleted');

    // Check no submissions.
    $this->drupalGet('/admin/structure/webform/test/views_bulk_form');
    $assert_session->responseContains('No submissions available.');
  }

}
