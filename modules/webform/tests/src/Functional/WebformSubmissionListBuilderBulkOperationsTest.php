<?php

namespace Drupal\Tests\webform\Functional;

use Drupal\webform\Entity\Webform;

/**
 * Tests for webform submission list builder.
 *
 * @group webform
 */
class WebformSubmissionListBuilderBulkOperationsTest extends WebformBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['node', 'webform', 'webform_test_submissions'];

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_submissions'];

  /**
   * Tests results.
   */
  public function testResults() {
    $assert_session = $this->assertSession();

    $admin_submission_user = $this->drupalCreateUser([
      'administer webform submission',
    ]);

    $update_submission_user = $this->drupalCreateUser([
      'view any webform submission',
      'edit any webform submission',
    ]);

    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = Webform::load('test_submissions');

    /** @var \Drupal\webform\WebformSubmissionInterface[] $submissions */
    $submissions = array_values(\Drupal::entityTypeManager()->getStorage('webform_submission')->loadByProperties(['webform_id' => 'test_submissions']));

    $path = '/admin/structure/webform/manage/' . $webform->id() . '/results/submissions';

    /* ********************************************************************** */

    // Login the admin submission user.
    $this->drupalLogin($admin_submission_user);

    // Check bulk operation access.
    $this->drupalGet($path);
    $this->assertCssSelect('#webform-submission-bulk-form');
    $this->assertCssSelect('#edit-items-' . $submissions[0]->id());
    $this->assertCssSelect('#edit-items-' . $submissions[1]->id());
    $this->assertCssSelect('#edit-items-' . $submissions[2]->id());

    // Check available actions when NOT filtered by archived webforms.
    $this->drupalGet($path);
    $this->assertCssSelect('option[value="webform_submission_make_sticky_action"]');
    $this->assertCssSelect('option[value="webform_submission_make_unsticky_action"]');
    $this->assertCssSelect('option[value="webform_submission_make_lock_action"]');
    $this->assertCssSelect('option[value="webform_submission_make_unlock_action"]');
    $this->assertCssSelect('option[value="webform_submission_delete_action"]');

    /* ********************************************************************** */
    // Access.
    /* ********************************************************************** */

    // Login the update submission user.
    $this->drupalLogin($update_submission_user);

    $this->drupalGet($path);
    $this->assertCssSelect('option[value="webform_submission_make_sticky_action"]');
    $this->assertCssSelect('option[value="webform_submission_make_unsticky_action"]');
    $this->assertCssSelect('option[value="webform_submission_make_lock_action"]');
    $this->assertCssSelect('option[value="webform_submission_make_unlock_action"]');
    $this->assertNoCssSelect('option[value="webform_submission_delete_action"]');

    // Login the admin submission user.
    $this->drupalLogin($admin_submission_user);

    /* ********************************************************************** */
    // Disable/Enable.
    /* ********************************************************************** */

    // Check bulk operation disable.
    \Drupal::configFactory()->getEditable('webform.settings')
      ->set('settings.webform_submission_bulk_form', FALSE)
      ->save();
    $this->drupalGet($path);
    $this->assertNoCssSelect('#webform-submission-bulk-form');

    // Re-enable bulk operations.
    \Drupal::configFactory()->getEditable('webform.settings')
      ->set('settings.webform_submission_bulk_form', TRUE)
      ->save();

    /* ********************************************************************** */
    // Sticky/Unsticky.
    /* ********************************************************************** */

    // Check first submission is NOT sticky.
    $this->assertFalse($submissions[0]->isSticky());

    // Check submission sticky action.
    $this->drupalGet($path);
    $edit = [
      'action' => 'webform_submission_make_sticky_action',
      'items[' . $submissions[0]->id() . ']' => TRUE,
    ];
    $this->submitForm($edit, 'Apply to selected items', 'webform-submission-bulk-form');
    $assert_session->responseContains('<em class="placeholder">Star/flag submission</em> was applied to 1 item.');

    // Check first submission is sticky.
    $submissions[0] = $this->reloadSubmission($submissions[0]->id());
    $this->assertTrue($submissions[0]->isSticky());

    // Check submission unsticky action.
    $this->drupalGet($path);
    $edit = [
      'action' => 'webform_submission_make_unsticky_action',
      'items[' . $submissions[0]->id() . ']' => TRUE,
    ];
    $this->submitForm($edit, 'Apply to selected items', 'webform-submission-bulk-form');
    $assert_session->responseContains('<em class="placeholder">Unstar/unflag submission</em> was applied to 1 item.');

    // Check first submission is NOT sticky.
    $submissions[0] = $this->reloadSubmission($submissions[0]->id());
    $this->assertFalse($submissions[0]->isSticky());

    /* ********************************************************************** */
    // Lock/Unlock.
    /* ********************************************************************** */

    // Check first submission is NOT lock.
    $this->assertFalse($submissions[0]->isLocked());

    // Check submission lock action.
    $this->drupalGet($path);
    $edit = [
      'action' => 'webform_submission_make_lock_action',
      'items[' . $submissions[0]->id() . ']' => TRUE,
    ];
    $this->submitForm($edit, 'Apply to selected items', 'webform-submission-bulk-form');
    $assert_session->responseContains('<em class="placeholder">Lock submission</em> was applied to 1 item.');

    // Check first submission is lock.
    $submissions[0] = $this->reloadSubmission($submissions[0]->id());
    $this->assertTrue($submissions[0]->isLocked());

    // Check submission unlock action.
    $this->drupalGet($path);
    $edit = [
      'action' => 'webform_submission_make_unlock_action',
      'items[' . $submissions[0]->id() . ']' => TRUE,
    ];
    $this->submitForm($edit, 'Apply to selected items', 'webform-submission-bulk-form');
    $assert_session->responseContains('<em class="placeholder">Unlock submission</em> was applied to 1 item.');

    // Check first submission is NOT lock.
    $submissions[0] = $this->reloadSubmission($submissions[0]->id());
    $this->assertFalse($submissions[0]->isLocked());

    /* ********************************************************************** */
    // Delete.
    /* ********************************************************************** */

    // Check submission delete action.
    $this->drupalGet($path);
    $edit = [
      'action' => 'webform_submission_delete_action',
      'items[' . $submissions[0]->id() . ']' => TRUE,
    ];
    $this->submitForm($edit, 'Apply to selected items', 'webform-submission-bulk-form');
    $edit = [
      'confirm_input' => TRUE,
    ];
    $this->submitForm($edit, 'Delete');
    $assert_session->responseContains('Deleted 1 item.');

    // Check submission is now deleted.
    $submissions[0] = $this->reloadSubmission($submissions[0]->id());
    $this->assertNull($submissions[0]);
  }

}
