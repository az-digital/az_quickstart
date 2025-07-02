<?php

namespace Drupal\Tests\webform_node\Functional\Access;

use Drupal\Tests\webform_node\Functional\WebformNodeBrowserTestBase;
use Drupal\webform\Entity\Webform;

/**
 * Tests for webform node access permissions.
 *
 * @group webform_node
 */
class WebformNodeAccessPermissionsTest extends WebformNodeBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['webform', 'webform_node'];

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_form_wizard_basic'];

  /**
   * Tests webform node access permissions.
   *
   * @see \Drupal\webform\Tests\Access\WebformAccessPermissionTest::testWebformSubmissionAccessPermissions
   */
  public function testAccessPermissions() {
    global $base_path;

    $assert_session = $this->assertSession();

    // Own webform submission user.
    $submission_own_account = $this->drupalCreateUser([
      'view own webform submission',
      'edit own webform submission',
      'delete own webform submission',
      'access webform submission user',
    ]);

    // Any webform submission user.
    $submission_any_account = $this->drupalCreateUser([
      'view any webform submission',
      'edit any webform submission',
      'delete any webform submission',
    ]);

    // Own webform submission node user.
    $submission_own_node_account = $this->drupalCreateUser([
      'view webform submissions own node',
      'edit webform submissions own node',
      'delete webform submissions own node',
    ]);

    // Any webform submission node user.
    $submission_any_node_account = $this->drupalCreateUser([
      'view webform submissions any node',
      'edit webform submissions any node',
      'delete webform submissions any node',
    ]);

    // Create webform node that references the contact webform.
    $contact_webform = Webform::load('contact');
    $contact_node = $this->createWebformNode('contact', ['uid' => $submission_own_node_account->id()]);
    $contact_nid = $contact_node->id();

    // Create webform node that references the wizard webform.
    $wizard_webform = Webform::load('test_form_wizard_basic');
    $wizard_node = $this->createWebformNode('test_form_wizard_basic', ['uid' => $submission_own_node_account->id()]);
    $wizard_nid = $wizard_node->id();

    /* ********************************************************************** */
    // Own submission permissions (authenticated).
    /* ********************************************************************** */

    $this->drupalLogin($submission_own_account);

    $edit = ['subject' => '{subject}', 'message' => '{message}'];
    $contact_sid_1 = $this->postNodeSubmission($contact_node, $edit);
    $this->drupalLogout();

    // Check that access is denied for anonymous users to edit, edit/all,
    // resend, and duplicate.
    $this->drupalGet("node/{$contact_nid}/webform/submissions/{$contact_sid_1}/edit");
    $assert_session->statusCodeEquals(403);
    $this->drupalGet("node/{$contact_nid}/webform/submission/{$contact_sid_1}/edit/all");
    $assert_session->statusCodeEquals(403);
    $this->drupalGet("node/{$contact_nid}/webform/submissions/{$contact_sid_1}/duplicate");
    $assert_session->statusCodeEquals(403);
    $this->drupalGet("node/{$contact_nid}/webform/submission/{$contact_sid_1}/resend");
    $assert_session->statusCodeEquals(403);

    $this->drupalLogin($submission_own_account);

    // Check view own previous submission message.
    $this->drupalGet("node/{$contact_nid}");
    $assert_session->responseContains('You have already submitted this webform.');
    $assert_session->responseContains("<a href=\"{$base_path}node/{$contact_nid}/webform/submissions/{$contact_sid_1}\">View your previous submission</a>.");

    // Check 'view own submission' permission.
    $this->drupalGet("node/{$contact_nid}/webform/submissions/{$contact_sid_1}");
    $assert_session->statusCodeEquals(200);

    // Check 'edit own submission' permission.
    $this->drupalGet("node/{$contact_nid}/webform/submissions/{$contact_sid_1}/edit");
    $assert_session->statusCodeEquals(200);

    // Check 'edit own submission' permission does not allow users to edit all.
    $this->drupalGet("node/{$contact_nid}/webform/submission/{$contact_sid_1}/edit/all");
    $assert_session->statusCodeEquals(403);

    // Check 'edit own submission' permission does not allow users to resend.
    $this->drupalGet("node/{$contact_nid}/webform/submission/{$contact_sid_1}/resend");
    $assert_session->statusCodeEquals(403);

    // Check 'delete own submission' permission.
    $this->drupalGet("node/{$contact_nid}/webform/submissions/{$contact_sid_1}/delete");
    $assert_session->statusCodeEquals(200);

    $contact_sid_2 = $this->postNodeSubmission($contact_node, $edit);

    // Check view own previous submissions message.
    $this->drupalGet("node/{$contact_nid}");
    $assert_session->responseContains('You have already submitted this webform.');
    $assert_session->responseContains("<a href=\"{$base_path}node/{$contact_nid}/webform/submissions\">View your previous submissions</a>");

    // Check view own previous submissions.
    $this->drupalGet("node/{$contact_nid}/webform/submissions");
    $assert_session->statusCodeEquals(200);
    $assert_session->linkByHrefExists("{$base_path}node/{$contact_nid}/webform/submissions/{$contact_sid_1}");
    $assert_session->linkByHrefExists("{$base_path}node/{$contact_nid}/webform/submissions/{$contact_sid_2}");

    // Check submission user duplicate returns access denied.
    $this->drupalGet("node/{$contact_nid}/webform/submissions/{$contact_sid_2}/duplicate");
    $assert_session->statusCodeEquals(403);

    // Enable submission user duplicate.
    $contact_webform->setSetting('submission_user_duplicate', TRUE);
    $contact_webform->save();

    // Check submission user duplicate returns access allows.
    $this->drupalGet("node/{$contact_nid}/webform/submissions/{$contact_sid_2}/duplicate");
    $assert_session->statusCodeEquals(200);

    // Check webform results access denied.
    $this->drupalGet("node/{$contact_nid}/webform/results/submissions");
    $assert_session->statusCodeEquals(403);

    /* ********************************************************************** */
    // Any submission permissions.
    /* ********************************************************************** */

    // Login as any user.
    $this->drupalLogin($submission_any_account);

    // Check webform results access allowed.
    $this->drupalGet("node/{$contact_nid}/webform/results/submissions");
    $assert_session->statusCodeEquals(200);
    $assert_session->linkByHrefExists("{$base_path}node/{$contact_nid}/webform/submission/{$contact_sid_1}");
    $assert_session->linkByHrefExists("{$base_path}node/{$contact_nid}/webform/submission/{$contact_sid_2}");

    // Check webform submission access allowed.
    $this->drupalGet("node/{$contact_nid}/webform/submission/{$contact_sid_1}");
    $assert_session->statusCodeEquals(200);

    // Check webform submission edit allowed.
    $this->drupalGet("node/{$contact_nid}/webform/submissions/{$contact_sid_1}/edit");
    $assert_session->statusCodeEquals(200);

    // Check webform submission edit/all denied.
    $this->drupalGet("node/{$contact_nid}/webform/submission/{$contact_sid_1}/edit/all");
    $assert_session->statusCodeEquals(403);

    // Check webform submission resend allowed.
    $this->drupalGet("node/{$contact_nid}/webform/submission/{$contact_sid_1}/resend");
    $assert_session->statusCodeEquals(200);

    // Check webform submission duplicate allowed.
    $this->drupalGet("node/{$contact_nid}/webform/submission/{$contact_sid_1}/duplicate");
    $assert_session->statusCodeEquals(200);

    /* ********************************************************************** */
    // Own submission node permissions.
    /* ********************************************************************** */

    // Login as own node user.
    $this->drupalLogin($submission_own_node_account);

    // Check webform results access allowed.
    $this->drupalGet("node/{$contact_nid}/webform/results/submissions");
    $assert_session->statusCodeEquals(200);
    $assert_session->linkByHrefExists("{$base_path}node/{$contact_nid}/webform/submission/{$contact_sid_1}");
    $assert_session->linkByHrefExists("{$base_path}node/{$contact_nid}/webform/submission/{$contact_sid_2}");

    // Check webform submission access allowed.
    $this->drupalGet("node/{$contact_nid}/webform/submission/{$contact_sid_1}");
    $assert_session->statusCodeEquals(200);

    // Check webform submission edit allowed.
    $this->drupalGet("node/{$contact_nid}/webform/submissions/{$contact_sid_1}/edit");
    $assert_session->statusCodeEquals(200);

    // Check webform submission edit/all denied.
    $this->drupalGet("node/{$contact_nid}/webform/submission/{$contact_sid_1}/edit/all");
    $assert_session->statusCodeEquals(403);

    // Check webform submission resend denied.
    $this->drupalGet("node/{$contact_nid}/webform/submission/{$contact_sid_1}/resend");
    $assert_session->statusCodeEquals(403);

    // Check webform submission duplicate denied.
    $this->drupalGet("node/{$contact_nid}/webform/submission/{$contact_sid_1}/duplicate");
    $assert_session->statusCodeEquals(403);

    // Check webform submission delete allowed.
    $this->drupalGet("node/{$contact_nid}/webform/submissions/{$contact_sid_1}/delete");
    $assert_session->statusCodeEquals(200);

    /* ********************************************************************** */
    // Any submission node permissions.
    /* ********************************************************************** */

    // Login as any node user.
    $this->drupalLogin($submission_any_node_account);

    // Check webform results access allowed.
    $this->drupalGet("node/{$contact_nid}/webform/results/submissions");
    $assert_session->statusCodeEquals(200);
    $assert_session->linkByHrefExists("{$base_path}node/{$contact_nid}/webform/submission/{$contact_sid_1}");
    $assert_session->linkByHrefExists("{$base_path}node/{$contact_nid}/webform/submission/{$contact_sid_2}");

    // Check webform submission access allowed.
    $this->drupalGet("node/{$contact_nid}/webform/submission/{$contact_sid_1}");
    $assert_session->statusCodeEquals(200);

    // Check webform submission edit allowed.
    $this->drupalGet("node/{$contact_nid}/webform/submissions/{$contact_sid_1}/edit");
    $assert_session->statusCodeEquals(200);

    // Check webform submission edit/all denied.
    $this->drupalGet("node/{$contact_nid}/webform/submission/{$contact_sid_1}/edit/all");
    $assert_session->statusCodeEquals(403);

    // Check webform submission resend denied.
    $this->drupalGet("node/{$contact_nid}/webform/submission/{$contact_sid_1}/resend");
    $assert_session->statusCodeEquals(403);

    // Check webform submission duplicate denied.
    $this->drupalGet("node/{$contact_nid}/webform/submission/{$contact_sid_1}/duplicate");
    $assert_session->statusCodeEquals(403);

    // Check webform submission delete allowed.
    $this->drupalGet("node/{$contact_nid}/webform/submissions/{$contact_sid_1}/delete");
    $assert_session->statusCodeEquals(200);

    /* ********************************************************************** */
    // Wizard edit/all access.
    /* ********************************************************************** */

    // Create a wizard submission.
    $this->drupalLogin($submission_own_account);
    $this->drupalGet('/node/' . $wizard_nid);
    $this->submitForm([], 'Next >');
    $this->submitForm([], 'Submit');
    $wizard_sid = $this->getLastSubmissionId($wizard_webform);
    $this->drupalLogout();

    // Check that access is denied for anonymous users to edit/all.
    $this->drupalGet("node/{$wizard_nid}/webform/submission/{$wizard_sid}/edit/all");
    $assert_session->statusCodeEquals(403);

    $this->drupalLogin($submission_own_account);

    // Check 'edit own submission' permission does allow users to edit all.
    $this->drupalGet("node/{$wizard_nid}/webform/submission/{$wizard_sid}/edit/all");
    $assert_session->statusCodeEquals(200);

    // Login as any user.
    $this->drupalLogin($submission_any_account);

    // Check webform submission edit/all allowed.
    $this->drupalGet("node/{$contact_nid}/webform/submission/{$contact_sid_1}/edit/all");
    $assert_session->statusCodeEquals(403);
  }

}
