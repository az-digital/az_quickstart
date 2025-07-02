<?php

namespace Drupal\Tests\webform\Functional\Access;

use Drupal\Tests\webform\Functional\WebformBrowserTestBase;
use Drupal\webform\Entity\Webform;

/**
 * Tests for webform entity access rules.
 *
 * @group webform
 */
class WebformAccessEntityRulesTest extends WebformBrowserTestBase {

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
   * Tests webform entity access rules.
   */
  public function testAccessRules() {
    global $base_path;

    $assert_session = $this->assertSession();

    /** @var \Drupal\webform\WebformAccessRulesManagerInterface $access_rules_manager */
    $access_rules_manager = \Drupal::service('webform.access_rules_manager');
    $default_access_rules = $access_rules_manager->getDefaultAccessRules();

    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = Webform::load('test_submissions');
    /** @var \Drupal\webform\WebformSubmissionInterface[] $submissions */
    $submissions = array_values(\Drupal::entityTypeManager()->getStorage('webform_submission')->loadByProperties(['webform_id' => 'test_submissions']));

    $account = $this->drupalCreateUser(['access content', 'edit webform source']);

    $webform_id = $webform->id();
    $sid = $submissions[0]->id();
    $uid = $account->id();
    $rid = $account->getRoles(TRUE)[0];

    /* ********************************************************************** */
    // Test.
    /* ********************************************************************** */

    $this->drupalLogin($account);

    // Check that user cannot access test form.
    $this->drupalGet("webform/$webform_id/test");
    $assert_session->statusCodeEquals(403);

    // Assign user to 'test' access rule.
    $access_rules = [
      'test' => [
        'roles' => [],
        'users' => [$uid],
        'permissions' => [],
      ],
    ] + $default_access_rules;
    $webform->setAccessRules($access_rules)->save();

    // Check that user can access test form.
    $this->drupalGet("webform/$webform_id/test");
    $assert_session->statusCodeEquals(200);

    /* ********************************************************************** */
    // Administer.
    /* ********************************************************************** */

    // Check that user cannot access form settings.
    $this->drupalGet("admin/structure/webform/manage/$webform_id/settings");
    $assert_session->statusCodeEquals(403);
    $this->drupalGet("admin/structure/webform/manage/$webform_id/results/submissions");
    $assert_session->statusCodeEquals(403);

    // Assign user to 'administer' access rule.
    $access_rules = [
      'administer' => [
        'roles' => [],
        'users' => [$uid],
        'permissions' => [],
      ],
    ] + $default_access_rules;
    $webform->setAccessRules($access_rules)->save();

    // Check that user cannot access settings.
    $this->drupalGet("admin/structure/webform/manage/$webform_id/settings");
    $assert_session->statusCodeEquals(200);
    $this->drupalGet("admin/structure/webform/manage/$webform_id/results/submissions");
    $assert_session->statusCodeEquals(200);

    /* ********************************************************************** */
    // Create.
    /* ********************************************************************** */

    $this->drupalLogout();

    // Check create authenticated/anonymous access.
    $webform->setAccessRules($default_access_rules)->save();
    $this->drupalGet('/webform/' . $webform->id());
    $assert_session->statusCodeEquals(200);

    // Revoke create from anonymous and authenticated roles.
    $access_rules = [
      'create' => [
        'roles' => [],
        'users' => [],
        'permissions' => [],
      ],
    ] + $default_access_rules;
    $webform->setAccessRules($access_rules)->save();

    // Check create access denied.
    $this->drupalGet('/webform/' . $webform->id());
    $assert_session->statusCodeEquals(403);

    /* ********************************************************************** */
    // Any.
    /* ********************************************************************** */

    $any_tests = [
      'webform/{webform}' => 'create',
      'admin/structure/webform/manage/{webform}/results/submissions' => 'view_any',
      'admin/structure/webform/manage/{webform}/results/download' => 'view_any',
      'admin/structure/webform/manage/{webform}/results/clear' => 'purge_any',
      'admin/structure/webform/manage/{webform}/submission/{webform_submission}' => 'view_any',
      'admin/structure/webform/manage/{webform}/submission/{webform_submission}/text' => 'view_any',
      'admin/structure/webform/manage/{webform}/submission/{webform_submission}/yaml' => 'view_any',
      'admin/structure/webform/manage/{webform}/submission/{webform_submission}/edit' => 'update_any',
      'admin/structure/webform/manage/{webform}/submission/{webform_submission}/delete' => 'delete_any',
    ];

    // Check that all the test paths are access denied for anonymous users.
    foreach ($any_tests as $path => $permission) {
      $path = str_replace('{webform}', $webform_id, $path);
      $path = str_replace('{webform_submission}', $sid, $path);

      $this->drupalGet($path);
      $assert_session->statusCodeEquals(403);
    }

    // Login.
    $this->drupalLogin($account);

    // Check that all the test paths are access denied for authenticated.
    foreach ($any_tests as $path => $permission) {
      $path = str_replace('{webform}', $webform_id, $path);
      $path = str_replace('{webform_submission}', $sid, $path);

      $this->drupalGet($path);
      $assert_session->statusCodeEquals(403);
    }

    // Check any access rules by role, user id, and permission.
    foreach ($any_tests as $path => $permission) {
      $path = str_replace('{webform}', $webform_id, $path);
      $path = str_replace('{webform_submission}', $sid, $path);

      // Check access rule via role.
      $access_rules = [
        $permission => [
          'roles' => [$rid],
          'users' => [],
          'permissions' => [],
        ],
      ] + $default_access_rules;
      $webform->setAccessRules($access_rules)->save();
      $this->drupalGet($path);
      $assert_session->statusCodeEquals(200);

      // Check access rule via user id.
      $access_rules = [
        $permission => [
          'roles' => [],
          'users' => [$uid],
          'permissions' => [],
        ],
      ] + $default_access_rules;
      $webform->setAccessRules($access_rules)->save();
      $this->drupalGet($path);
      $assert_session->statusCodeEquals(200);

      // Check access rule via 'access content'.
      $access_rules = [
        $permission => [
          'roles' => [],
          'users' => [],
          'permissions' => ['access content'],
        ],
      ] + $default_access_rules;
      $webform->setAccessRules($access_rules)->save();
      $this->drupalGet($path);
      $assert_session->statusCodeEquals(200);
    }

    /* ********************************************************************** */
    // Own.
    /* ********************************************************************** */

    // Check own / user specific access rules.
    $access_rules = [
      'view_own' => [
        'roles' => [$rid],
        'users' => [],
        'permissions' => [],
      ],
      'update_own' => [
        'roles' => [$rid],
        'users' => [],
        'permissions' => [],
      ],
      'delete_own' => [
        'roles' => [$rid],
        'users' => [],
        'permissions' => [],
      ],
    ] + $default_access_rules;
    $webform->setAccessRules($access_rules)->save();

    // Must delete all existing anonymous submission to prevent them from
    // getting transferred to authenticated user.
    foreach ($submissions as $submission) {
      $submission->delete();
    }

    // Login and post a submission as a user.
    $this->drupalLogin($account);

    // Check no view previous submission message.
    $this->drupalGet('/webform/' . $webform->id());
    $assert_session->responseNotContains('You have already submitted this webform.');
    $assert_session->responseNotContains('View your previous submission');

    $sid = $this->postSubmission($webform);

    // Check view previous submission message.
    $this->drupalGet('/webform/' . $webform->id());
    $assert_session->responseContains('You have already submitted this webform.');
    $assert_session->responseContains("<a href=\"{$base_path}webform/{$webform_id}/submissions/{$sid}\">View your previous submission</a>.");

    $sid = $this->postSubmission($webform);

    // Check view previous submissions message.
    $this->drupalGet('/webform/' . $webform->id());
    $assert_session->responseContains('You have already submitted this webform.');
    $assert_session->responseContains("<a href=\"{$base_path}webform/{$webform_id}/submissions\">View your previous submissions</a>");

    // Check the new submission's view, update, and delete access for the user.
    $test_own = [
      'admin/structure/webform/manage/{webform}/results/submissions' => 403,
      'admin/structure/webform/manage/{webform}/results/download' => 403,
      'admin/structure/webform/manage/{webform}/results/clear' => 403,
      'admin/structure/webform/manage/{webform}/submission/{webform_submission}' => 200,
      'admin/structure/webform/manage/{webform}/submission/{webform_submission}/text' => 403,
      'admin/structure/webform/manage/{webform}/submission/{webform_submission}/yaml' => 403,
      'admin/structure/webform/manage/{webform}/submission/{webform_submission}/edit' => 200,
      'admin/structure/webform/manage/{webform}/submission/{webform_submission}/delete' => 200,
      'webform/{webform}/submissions/{webform_submission}' => 200,
      'webform/{webform}/submissions/{webform_submission}/edit' => 200,
      'webform/{webform}/submissions/{webform_submission}/duplicate' => 403,
      'webform/{webform}/submissions/{webform_submission}/delete' => 200,
    ];
    foreach ($test_own as $path => $status_code) {
      $path = str_replace('{webform}', $webform_id, $path);
      $path = str_replace('{webform_submission}', $sid, $path);

      $this->drupalGet($path);
      $assert_session->statusCodeEquals($status_code);
    }

    // Enable submission user duplicate.
    $webform->setSetting('submission_user_duplicate', TRUE);
    $webform->save();

    // Check enable user submission duplicate.
    $this->drupalGet("webform/$webform_id/submissions/$sid/duplicate");
    $assert_session->statusCodeEquals(200);

    // Check disabled previous submissions messages.
    $webform->setSetting('form_previous_submissions', FALSE);
    $webform->save();
    $this->drupalGet('/webform/' . $webform->id());
    $assert_session->responseNotContains('You have already submitted this webform.');
  }

}
