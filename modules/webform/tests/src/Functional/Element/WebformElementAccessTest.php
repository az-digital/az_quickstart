<?php

namespace Drupal\Tests\webform\Functional\Element;

use Drupal\webform\Entity\Webform;
use Drupal\webform\Entity\WebformSubmission;

/**
 * Tests for webform element access.
 *
 * @group webform
 */
class WebformElementAccessTest extends WebformElementBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['webform', 'webform_ui'];

  /**
   * Webforms to load.
   *
   * @var array
   */
  protected static $testWebforms = ['test_element_access'];

  /**
   * Test element access.
   */
  public function testAccess() {
    $assert_session = $this->assertSession();

    $normal_user = $this->drupalCreateUser([
      'access user profiles',
    ]);

    $admin_submission_user = $this->drupalCreateUser([
      'access user profiles',
      'administer webform submission',
    ]);

    $own_submission_user = $this->drupalCreateUser([
      'access user profiles',
      'access webform overview',
      'create webform',
      'edit own webform',
      'delete own webform',
      'view own webform submission',
      'edit own webform submission',
      'delete own webform submission',
    ]);

    $webform = Webform::load('test_element_access');

    /* ********************************************************************** */

    // Check user from USER:1 to admin submission user.
    $elements = $webform->get('elements');
    $elements = str_replace('      - 1', '      - ' . $admin_submission_user->id(), $elements);
    $elements = str_replace('USER:1', 'USER:' . $admin_submission_user->id(), $elements);
    $webform->set('elements', $elements);
    $webform->save();

    // Create a webform submission.
    $this->drupalLogin($normal_user);
    $sid = $this->postSubmission($webform);
    $webform_submission = WebformSubmission::load($sid);

    // Check admins have 'administer webform element access' permission.
    $this->drupalLogin($this->rootUser);
    $this->drupalGet('/admin/structure/webform/manage/test_element_access/element/access_create_roles_anonymous/edit');
    $assert_session->fieldExists('edit-properties-access-create-roles-anonymous');

    // Check webform builder don't have 'administer webform element access'
    // permission.
    $this->drupalLogin($own_submission_user);
    $this->drupalGet('/admin/structure/webform/manage/test_element_access/element/access_create_roles_anonymous/edit');
    $assert_session->fieldNotExists('edit-properties-access-create-roles-anonymous');

    /* Create access */

    // Check anonymous role access.
    $this->drupalLogout();
    $this->drupalGet('/webform/test_element_access');
    $assert_session->fieldExists('access_create_roles_anonymous');
    $assert_session->fieldNotExists('access_create_roles_authenticated');
    $assert_session->fieldNotExists('access_create_users');
    $assert_session->fieldNotExists('access_create_permissions');

    // Check authenticated access.
    $this->drupalLogin($normal_user);
    $this->drupalGet('/webform/test_element_access');
    $assert_session->fieldNotExists('access_create_roles_anonymous');
    $assert_session->fieldExists('access_create_roles_authenticated');
    $assert_session->fieldNotExists('access_create_users');
    $assert_session->fieldExists('access_create_permissions');

    // Check admin user access.
    $this->drupalLogin($admin_submission_user);
    $this->drupalGet('/webform/test_element_access');
    $assert_session->fieldNotExists('access_create_roles_anonymous');
    $assert_session->fieldExists('access_create_roles_authenticated');
    $assert_session->fieldExists('access_create_users');
    $assert_session->fieldExists('access_create_permissions');

    /* Update access */

    // Check anonymous role access.
    $this->drupalLogout();
    $this->drupalGet($webform_submission->getTokenUrl());
    $assert_session->fieldExists('access_update_roles_anonymous');
    $assert_session->fieldNotExists('access_update_roles_authenticated');
    $assert_session->fieldNotExists('access_update_users');
    $assert_session->fieldNotExists('access_update_permissions');

    // Check authenticated role access.
    $this->drupalLogin($normal_user);
    $this->drupalGet("/webform/test_element_access/submissions/$sid/edit");
    $assert_session->fieldNotExists('access_update_roles_anonymous');
    $assert_session->fieldExists('access_update_roles_authenticated');
    $assert_session->fieldNotExists('access_update_users');
    $assert_session->fieldExists('access_update_permissions');

    // Check admin user access.
    $this->drupalLogin($admin_submission_user);
    $this->drupalGet("/admin/structure/webform/manage/test_element_access/submission/$sid/edit");
    $assert_session->fieldNotExists('access_update_roles_anonymous');
    $assert_session->fieldExists('access_update_roles_authenticated');
    $assert_session->fieldExists('access_update_users');
    $assert_session->fieldExists('access_update_permissions');

    /* View, Table, and Download access */

    $urls = [
      ['path' => "/admin/structure/webform/manage/test_element_access/submission/$sid"],
      ['path' => '/admin/structure/webform/manage/test_element_access/results/submissions'],
      ['path' => '/admin/structure/webform/manage/test_element_access/results/download'],
      ['path' => '/admin/structure/webform/manage/test_element_access/results/download', 'options' => ['query' => ['download' => 1]]],
    ];
    foreach ($urls as $url) {
      $url += ['options' => []];

      // Check anonymous role access.
      $this->drupalLogout();
      $this->drupalGet($url['path'], $url['options']);
      $assert_session->responseContains('access_view_roles (anonymous)');
      $assert_session->responseNotContains('access_view_roles (authenticated)');
      $assert_session->responseNotContains('access_view_users (USER:' . $admin_submission_user->id() . ')');
      $assert_session->responseNotContains('access_view_permissions (access user profiles)');

      // Check authenticated role access.
      $this->drupalLogin($this->rootUser);
      $this->drupalGet($url['path'], $url['options']);
      $assert_session->responseNotContains('access_view_roles (anonymous)');
      $assert_session->responseContains('access_view_roles (authenticated)');
      $assert_session->responseNotContains('access_view_users (USER:' . $admin_submission_user->id() . ')');
      $assert_session->responseContains('access_view_permissions (access user profiles)');

      // Check admin user access.
      $this->drupalLogin($admin_submission_user);
      $this->drupalGet($url['path'], $url['options']);
      $assert_session->responseNotContains('access_view_roles (anonymous)');
      $assert_session->responseContains('access_view_roles (authenticated)');
      $assert_session->responseContains('access_view_users (USER:' . $admin_submission_user->id() . ')');
      $assert_session->responseContains('access_view_permissions (access user profiles)');
    }

    /* Download token access */
    $urls = [
      '<td>token</td>' => [
        'path' => '/admin/structure/webform/manage/test_element_access/results/download',
      ],
      ',Token,' => [
        'path' => '/admin/structure/webform/manage/test_element_access/results/download',
        'options' => ['query' => ['download' => 1, 'excluded_columns' => '']],
      ],
    ];
    foreach ($urls as $raw => $url) {
      $url += ['options' => []];

      // Check anonymous role access.
      $this->drupalLogout();
      $this->drupalGet($url['path'], $url['options']);
      $assert_session->responseNotContains($raw);

      // Check authenticated role access.
      $this->drupalLogin($normal_user);
      $this->drupalGet($url['path'], $url['options']);
      $assert_session->responseNotContains($raw);

      // Check admin webform access.
      $this->drupalLogin($this->rootUser);
      $this->drupalGet($url['path'], $url['options']);
      $assert_session->responseContains($raw);

      // Check admin submission access.
      $this->drupalLogin($admin_submission_user);
      $this->drupalGet($url['path'], $url['options']);
      $assert_session->responseContains($raw);
    }

    /* #access */

    $this->drupalLogin($this->rootUser);

    // Check that textfield and fieldset are disabled in the UI.
    $this->drupalGet('/admin/structure/webform/manage/test_element_access');
    $this->assertCssSelect('[data-webform-key="textfield_access_property"].webform-ui-element-disabled');
    $this->assertCssSelect('[data-webform-key="fieldset_access_property"].webform-ui-element-disabled');
    $this->assertCssSelect('[data-webform-key="fieldset_textfield_access"]');
    $this->assertNoCssSelect('[data-webform-key="fieldset_textfield_access"].webform-ui-element-disabled');

    // Check that textfield and fieldset are removed from results.
    $this->drupalGet('/admin/structure/webform/manage/test_element_access/results/submissions');
    $assert_session->responseNotContains('textfield_access_property');
    $assert_session->responseNotContains('fieldset_access_property');
    $assert_session->responseContains('fieldset_textfield_access');

    // Check that textfield and fieldset are removed from download.
    $this->drupalGet('/admin/structure/webform/manage/test_element_access/results/download');
    $assert_session->responseNotContains('textfield_access_property');
    $assert_session->responseNotContains('fieldset_access_property');
    $assert_session->responseContains('fieldset_textfield_access');
  }

}
