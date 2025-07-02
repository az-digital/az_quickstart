<?php

namespace Drupal\Tests\webform\Functional\Access;

use Drupal\Tests\webform\Functional\WebformBrowserTestBase;
use Drupal\user\Entity\Role;
use Drupal\webform\Entity\Webform;

/**
 * Tests for webform submission permissions.
 *
 * @group webform
 */
class WebformAccessSubmissionPermissionsTest extends WebformBrowserTestBase {

  /**
   * Test webform submission access permissions.
   */
  public function testPermissions() {
    global $base_path;

    $assert_session = $this->assertSession();

    $admin_webform_account = $this->drupalCreateUser([
      'administer webform',
      'create webform',
    ]);

    $admin_submission_account = $this->drupalCreateUser([
      'administer webform submission',
    ]);

    $own_webform_account = $this->drupalCreateUser([
      'edit own webform',
    ]);

    $any_submission_account = $this->drupalCreateUser([
      'view any webform submission',
      'edit any webform submission',
      'delete any webform submission',
    ]);

    $own_submission_account = $this->drupalCreateUser([
      'view own webform submission',
      'edit own webform submission',
      'delete own webform submission',
      'access webform submission user',
    ]);

    $webform_id = 'contact';
    $webform = Webform::load('contact');

    /* ********************************************************************** */
    // Create submission permissions (anonymous).
    /* ********************************************************************** */

    $edit = ['subject' => '{subject}', 'message' => '{message}'];
    $sid_1 = $this->postSubmission($webform, $edit);

    // Check cannot view own submissions.
    $uid = $own_submission_account->id();
    $this->drupalGet("user/$uid/submissions");
    $assert_session->statusCodeEquals(403);

    // Check cannot view own previous submission message.
    $this->drupalGet('/webform/' . $webform->id());
    $assert_session->responseNotContains('You have already submitted this webform.');

    // Check cannot 'view own submission' permission.
    $this->drupalGet("webform/{$webform_id}/submissions/{$sid_1}");
    $assert_session->statusCodeEquals(403);

    /* ********************************************************************** */
    // Own submission permissions (authenticated).
    /* ********************************************************************** */

    $this->drupalLogin($own_submission_account);

    $edit = ['subject' => '{subject}', 'message' => '{message}'];
    $sid_2 = $this->postSubmission($webform, $edit);

    // Check 'access webform submission user' permission.
    $uid = $own_submission_account->id();
    $this->drupalGet("user/$uid/submissions");
    $assert_session->statusCodeEquals(200);

    // Check view own previous submission message.
    $this->drupalGet('/webform/' . $webform->id());
    $assert_session->responseContains('You have already submitted this webform.');
    $assert_session->responseContains("<a href=\"{$base_path}webform/{$webform_id}/submissions/{$sid_2}\">View your previous submission</a>.");

    // Check 'view own submission' permission.
    $this->drupalGet("webform/{$webform_id}/submissions/{$sid_2}");
    $assert_session->statusCodeEquals(200);

    // Check 'edit own submission' permission.
    $this->drupalGet("webform/{$webform_id}/submissions/{$sid_2}/edit");
    $assert_session->statusCodeEquals(200);

    // Check 'delete own submission' permission.
    $this->drupalGet("webform/{$webform_id}/submissions/{$sid_2}/delete");
    $assert_session->statusCodeEquals(200);

    $sid_3 = $this->postSubmission($webform, $edit);

    // Check view own previous submissions message.
    $this->drupalGet('/webform/' . $webform->id());
    $assert_session->responseContains('You have already submitted this webform.');
    $assert_session->responseContains("<a href=\"{$base_path}webform/{$webform_id}/submissions\">View your previous submissions</a>");

    // Check view own previous submissions.
    $this->drupalGet("webform/{$webform_id}/submissions");
    $assert_session->statusCodeEquals(200);
    $assert_session->linkByHrefNotExists("{$base_path}admin/structure/webform/manage/{$webform_id}/submission/{$sid_1}");
    $assert_session->linkByHrefExists("{$base_path}webform/{$webform_id}/submissions/{$sid_2}");
    $assert_session->linkByHrefExists("{$base_path}webform/{$webform_id}/submissions/{$sid_3}");

    // Check webform submission allowed.
    $this->drupalGet("/admin/structure/webform/manage/{$webform_id}/submission/{$sid_2}");
    $assert_session->statusCodeEquals(200);

    // Check all results access denied.
    $this->drupalGet('/admin/structure/webform/submissions/manage');
    $assert_session->statusCodeEquals(403);

    // Check webform results access denied.
    $this->drupalGet("/admin/structure/webform/manage/{$webform_id}/results/submissions");
    $assert_session->statusCodeEquals(403);

    /* ********************************************************************** */
    // Any submission permissions.
    /* ********************************************************************** */

    // Login as any user.
    $this->drupalLogin($any_submission_account);

    // Check 'access webform submission user' permission.
    $uid = $any_submission_account->id();
    $this->drupalGet("user/$uid/submissions");
    $assert_session->statusCodeEquals(200);

    // Check 'access webform submission user' permission denied.
    $uid = $own_submission_account->id();
    $this->drupalGet("user/$uid/submissions");
    $assert_session->statusCodeEquals(200);

    // Check webform results access allowed.
    $this->drupalGet("/admin/structure/webform/manage/{$webform_id}/results/submissions");
    $assert_session->statusCodeEquals(200);
    $assert_session->linkByHrefExists("{$base_path}admin/structure/webform/manage/{$webform_id}/submission/{$sid_1}");
    $assert_session->linkByHrefExists("{$base_path}admin/structure/webform/manage/{$webform_id}/submission/{$sid_2}");
    $assert_session->linkByHrefExists("{$base_path}admin/structure/webform/manage/{$webform_id}/submission/{$sid_3}");

    // Check webform submission access allowed.
    $this->drupalGet("/admin/structure/webform/manage/{$webform_id}/submission/{$sid_2}");
    $assert_session->statusCodeEquals(200);

    // Check all results access allowed.
    $this->drupalGet('/admin/structure/webform/submissions/manage');
    $assert_session->statusCodeEquals(200);

    /* ********************************************************************** */
    // Own submission permissions (anonymous).
    /* ********************************************************************** */

    /** @var \Drupal\user\RoleInterface $anonymous_role */
    $anonymous_role = Role::load('anonymous');
    $anonymous_role->grantPermission('view own webform submission')
      ->grantPermission('edit own webform submission')
      ->grantPermission('delete own webform submission')
      ->save();
    $this->drupalLogout();

    $edit = ['name' => '{name}', 'email' => 'example@example.com', 'subject' => '{subject}', 'message' => '{message}'];
    $sid_4 = $this->postSubmission($webform, $edit);

    // Check view own previous submission message.
    $this->drupalGet('/webform/' . $webform->id());
    $assert_session->responseContains('You have already submitted this webform.');
    $assert_session->responseContains("<a href=\"{$base_path}webform/{$webform_id}/submissions/{$sid_4}\">View your previous submission</a>.");

    // Check 'view own submission' permission.
    $this->drupalGet("webform/{$webform_id}/submissions/{$sid_4}");
    $assert_session->statusCodeEquals(200);

    // Check 'edit own submission' permission.
    $this->drupalGet("webform/{$webform_id}/submissions/{$sid_4}/edit");
    $assert_session->statusCodeEquals(200);

    // Check 'delete own submission' permission.
    $this->drupalGet("webform/{$webform_id}/submissions/{$sid_4}/delete");
    $assert_session->statusCodeEquals(200);

    $sid_5 = $this->postSubmission($webform, $edit);

    // Check view own previous submissions message.
    $this->drupalGet('/webform/' . $webform->id());
    $assert_session->responseContains('You have already submitted this webform.');
    $assert_session->responseContains("<a href=\"{$base_path}webform/{$webform_id}/submissions\">View your previous submissions</a>");

    // Check view own previous submissions.
    $this->drupalGet("webform/{$webform_id}/submissions");
    $assert_session->statusCodeEquals(200);
    $assert_session->linkByHrefExists("{$base_path}webform/{$webform_id}/submissions/{$sid_4}");
    $assert_session->linkByHrefExists("{$base_path}webform/{$webform_id}/submissions/{$sid_5}");

    /* ********************************************************************** */
    // Administer webform or webform submission permission.
    /* ********************************************************************** */

    $this->drupalLogin($admin_webform_account);
    $uid = $own_submission_account->id();
    $this->drupalGet("user/$uid/submissions");
    $assert_session->statusCodeEquals(200);

    $this->drupalLogin($admin_submission_account);
    $uid = $own_submission_account->id();
    $this->drupalGet("user/$uid/submissions");
    $assert_session->statusCodeEquals(200);

    // Check user can't see all submissions unless they are the owner.
    $this->drupalLogin($own_webform_account);
    $this->drupalGet("/admin/structure/webform/manage/{$webform_id}/results/submissions");
    $assert_session->statusCodeEquals(403);

    // Check user can see all submissions when they are the webform owner.
    $webform->setOwner($own_webform_account)->save();
    $this->drupalGet("/admin/structure/webform/manage/{$webform_id}/results/submissions");
    $assert_session->statusCodeEquals(200);
    $assert_session->linkByHrefExists("{$base_path}admin/structure/webform/manage/{$webform_id}/submission/{$sid_1}");
    $assert_session->linkByHrefExists("{$base_path}admin/structure/webform/manage/{$webform_id}/submission/{$sid_2}");
    $assert_session->linkByHrefExists("{$base_path}admin/structure/webform/manage/{$webform_id}/submission/{$sid_3}");
    $assert_session->linkByHrefExists("{$base_path}admin/structure/webform/manage/{$webform_id}/submission/{$sid_4}");

    // Check user can the submissions when they are the webform owner.
    $this->drupalGet("admin/structure/webform/manage/{$webform_id}/submission/{$sid_4}");
    $assert_session->statusCodeEquals(200);
  }

}
