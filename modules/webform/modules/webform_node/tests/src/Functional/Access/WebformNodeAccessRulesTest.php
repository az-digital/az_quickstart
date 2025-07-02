<?php

namespace Drupal\Tests\webform_node\Functional\Access;

use Drupal\Tests\webform_node\Functional\WebformNodeBrowserTestBase;
use Drupal\webform\Entity\Webform;

/**
 * Tests for webform node access rules.
 *
 * @group webform_node
 */
class WebformNodeAccessRulesTest extends WebformNodeBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['webform', 'webform_node'];

  /**
   * Tests webform node access rules.
   *
   * @see \Drupal\webform\Tests\WebformEntityAccessControlsTest::testAccessRules
   */
  public function testAccessRules() {
    $assert_session = $this->assertSession();

    /** @var \Drupal\webform\WebformAccessRulesManagerInterface $access_rules_manager */
    $access_rules_manager = \Drupal::service('webform.access_rules_manager');
    $default_access_rules = $access_rules_manager->getDefaultAccessRules();

    $webform = Webform::load('contact');

    $node = $this->createWebformNode('contact');
    $nid = $node->id();

    $account = $this->drupalCreateUser(['access content']);
    $rid = $account->getRoles(TRUE)[0];
    $uid = $account->id();

    /* ********************************************************************** */

    // Log in normal user and get their rid.
    $this->drupalLogin($account);

    // Add one submission to the Webform node.
    $edit = [
      'name' => '{name}',
      'email' => 'example@example.com',
      'subject' => '{subject}',
      'message' => '{message',
    ];
    $sid = $this->postNodeSubmission($node, $edit);

    // Check create authenticated/anonymous access.
    $webform->setAccessRules($default_access_rules)->save();
    $this->drupalGet('/node/' . $node->id());
    $assert_session->fieldValueEquals('name', $account->getAccountName());
    $assert_session->fieldValueEquals('email', $account->getEmail());

    $access_rules = [
      'create' => [
        'roles' => [],
        'users' => [],
      ],
    ] + $default_access_rules;
    $webform->setAccessRules($access_rules)->save();

    // Check no access.
    $this->drupalGet('/node/' . $node->id());
    $assert_session->fieldNotExists('name');
    $assert_session->fieldNotExists('email');

    $any_tests = [
      'node/{node}/webform/results/submissions' => 'view_any',
      'node/{node}/webform/results/download' => 'view_any',
      'node/{node}/webform/results/clear' => 'purge_any',
      'node/{node}/webform/submission/{webform_submission}' => 'view_any',
      'node/{node}/webform/submission/{webform_submission}/text' => 'view_any',
      'node/{node}/webform/submission/{webform_submission}/yaml' => 'view_any',
      'node/{node}/webform/submission/{webform_submission}/edit' => 'update_any',
      'node/{node}/webform/submission/{webform_submission}/delete' => 'delete_any',
    ];

    // Check that all the test paths are access denied for authenticated.
    foreach ($any_tests as $path => $permission) {
      $path = str_replace('{node}', $nid, $path);
      $path = str_replace('{webform_submission}', $sid, $path);

      $this->drupalGet($path);
      $assert_session->statusCodeEquals(403);
    }

    // Check access rules by role and user id.
    foreach ($any_tests as $path => $permission) {
      $path = str_replace('{node}', $nid, $path);
      $path = str_replace('{webform_submission}', $sid, $path);

      // Check access rule via role.
      $access_rules = [
        $permission => [
          'roles' => [$rid],
          'users' => [],
        ],
      ] + $default_access_rules;
      $webform->setAccessRules($access_rules)->save();
      $this->drupalGet($path);
      $assert_session->statusCodeEquals(200);

      // Check access rule via role.
      $access_rules = [
        $permission => [
          'roles' => [],
          'users' => [$uid],
        ],
      ] + $default_access_rules;
      $webform->setAccessRules($access_rules)->save();
      $this->drupalGet($path);
      $assert_session->statusCodeEquals(200);
    }
  }

}
