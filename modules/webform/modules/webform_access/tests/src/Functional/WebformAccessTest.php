<?php

namespace Drupal\Tests\webform_access\Functional;

use Drupal\field\Entity\FieldConfig;

/**
 * Tests for webform access.
 *
 * @group webform_access
 */
class WebformAccessTest extends WebformAccessBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['field_ui'];

  /**
   * Tests webform access.
   */
  public function testWebformAccess() {
    $assert_session = $this->assertSession();

    $nid = $this->nodes['contact_01']->id();

    $this->drupalLogin($this->rootUser);

    // Check that employee and manager groups exist.
    $this->drupalGet('/admin/structure/webform/access/group/manage');
    $assert_session->linkExists('employee_group');
    $assert_session->linkExists('manager_group');

    // Check that webform node is assigned to groups.
    $assert_session->linkExists($this->nodes['contact_01']->label());

    // Check that employee and manager users can't access webform results.
    foreach ($this->users as $account) {
      $this->drupalLogin($account);
      $this->drupalGet("/node/$nid/webform/results/submissions");
      $assert_session->statusCodeEquals(403);
    }

    $this->drupalLogin($this->rootUser);

    // Assign users to groups via the UI.
    foreach ($this->groups as $name => $group) {
      $this->drupalGet("/admin/structure/webform/access/group/manage/$name");
      $edit = ['users[]' => $this->users[$name]->id()];
      $this->submitForm($edit, 'Save');
    }

    // Check that manager and employee users can access webform results.
    foreach (['manager', 'employee'] as $name) {
      $account = $this->users[$name];
      $this->drupalLogin($account);
      $this->drupalGet("/node/$nid/webform/results/submissions");
      $assert_session->statusCodeEquals(200);
    }

    // Check that employee can't delete results.
    $this->drupalLogin($this->users['employee']);
    $this->drupalGet("/node/$nid/webform/results/clear");
    $assert_session->statusCodeEquals(403);

    // Check that manager can delete results.
    $this->drupalLogin($this->users['manager']);
    $this->drupalGet("/node/$nid/webform/results/clear");
    $assert_session->statusCodeEquals(200);

    // Unassign employee user from employee group via the UI.
    $this->drupalLogin($this->rootUser);
    $this->drupalGet('/admin/structure/webform/access/group/manage/employee');
    $edit = ['users[]' => 1];
    $this->submitForm($edit, 'Save');

    // Assign employee user to manager group via the UI.
    $this->drupalLogin($this->rootUser);
    $this->drupalGet('/user/' . $this->users['employee']->id() . '/edit');
    $edit = ['webform_access_group[]' => 'manager'];
    $this->submitForm($edit, 'Save');

    // Check defining webform field's access groups default value.
    $this->drupalLogin($this->rootUser);
    $this->drupalGet('/admin/structure/types');
    $this->drupalGet('/admin/structure/types/manage/webform/fields');
    $this->drupalGet('/admin/structure/types/manage/webform/fields/node.webform.webform');
    $edit = [
      'default_value_input[webform][0][target_id]' => 'contact',
      'default_value_input[webform][0][settings][default_data]' => 'test: test',
      'default_value_input[webform][0][settings][webform_access_group][]' => 'manager',
    ];
    // @todo Remove once Drupal 10.1.x is only supported.
    if (floatval(\Drupal::VERSION) >= 10.1) {
      $edit['set_default_value'] = TRUE;
    }
    $this->submitForm($edit, 'Save settings');
    $this->drupalGet('/node/add/webform');
    $this->assertTrue($assert_session->optionExists('webform[0][settings][webform_access_group][]', 'manager')->hasAttribute('selected'));

    // Check that employee can now delete results.
    $this->drupalLogin($this->users['employee']);
    $this->drupalGet("/node/$nid/webform/results/clear");
    $assert_session->statusCodeEquals(200);

    // Unassign node from groups.
    $this->drupalLogin($this->rootUser);
    foreach ($this->groups as $name => $group) {
      $this->drupalGet("/admin/structure/webform/access/group/manage/$name");
      $edit = ['entities[]' => 'node:' . $this->nodes['contact_02']->id() . ':webform:contact'];
      $this->submitForm($edit, 'Save');
    }

    // Check that employee can't access results.
    $this->drupalLogin($this->users['employee']);
    $this->drupalGet("/node/$nid/webform/results/clear");
    $assert_session->statusCodeEquals(403);

    // Assign webform node to group via the UI.
    $this->drupalLogin($this->rootUser);
    $this->drupalGet("/node/$nid/edit");
    $edit = ['webform[0][settings][webform_access_group][]' => 'manager'];
    $this->submitForm($edit, 'Save');

    // Check that employee can now access results.
    $this->drupalLogin($this->users['employee']);
    $this->drupalGet("/node/$nid/webform/results/clear");
    $assert_session->statusCodeEquals(200);

    // Delete employee group.
    $this->groups['employee']->delete();

    // Check that employee group is configured.
    $this->drupalLogin($this->rootUser);
    $this->drupalGet('/admin/structure/webform/access/group/manage');
    $assert_session->responseContains('manager_type');
    $assert_session->linkExists('manager_group');
    $assert_session->linkExists('manager_user');
    $assert_session->linkExists('employee_user');
    $assert_session->linkExists('contact_01');
    $assert_session->linkExists('contact_02');

    // Reset caches.
    \Drupal::entityTypeManager()->getStorage('webform_access_group')->resetCache();
    \Drupal::entityTypeManager()->getStorage('webform_access_type')->resetCache();

    // Delete types.
    foreach ($this->types as $type) {
      $type->delete();
    }

    // Check that manager type has been removed.
    $this->drupalGet('/admin/structure/webform/access/group/manage');
    $assert_session->responseNotContains('manager_type');

    // Delete users.
    foreach ($this->users as $user) {
      $user->delete();
    }

    // Check that manager type has been removed.
    $this->drupalGet('/admin/structure/webform/access/group/manage');
    $assert_session->linkNotExists('manager_user');
    $assert_session->linkNotExists('employee_user');

    // Delete contact 2.
    $this->nodes['contact_02']->delete();

    // Check that contact_02 has been removed.
    $this->drupalGet('/admin/structure/webform/access/group/manage');
    $assert_session->linkNotExists('contact_02');

    // Delete webform field config.
    FieldConfig::loadByName('node', 'webform', 'webform')->delete();

    // Check that contact_02 has been removed.
    $this->drupalGet('/admin/structure/webform/access/group/manage');
    $assert_session->linkNotExists('contact_02');
  }

  /**
   * Tests webform administrator access.
   */
  public function testWebformAdministratorAccess() {
    $assert_session = $this->assertSession();

    // Check root user access to group edit form.
    $this->drupalLogin($this->rootUser);
    $this->drupalGet('/admin/structure/webform/access/group/manage/manager');
    $assert_session->fieldExists('label');
    $assert_session->fieldExists('description[value][value]');
    $assert_session->fieldExists('type');
    $assert_session->fieldExists('admins[]');
    $assert_session->fieldExists('users[]');
    $assert_session->fieldExists('entities[]');
    $assert_session->fieldExists('permissions[administer]');

    // Logout.
    $this->drupalLogout();

    // Check access denied to 'Access' tab for anonymous user.
    $this->drupalGet('/admin/structure/webform/access/group/manage');
    $assert_session->statusCodeEquals(403);

    // Login as administrator.
    $administrator = $this->drupalCreateUser();
    $this->drupalLogin($administrator);

    // Check access denied to 'Access' tab for administrator.
    $this->drupalGet('/admin/structure/webform/access/group/manage');
    $assert_session->statusCodeEquals(403);

    // Assign administrator to the 'manager' access group.
    $this->groups['manager']->addAdminId($administrator->id());
    $this->groups['manager']->save();

    // Check access allowed to 'Access' tab for administrator.
    $this->drupalGet('/admin/structure/webform/access/group/manage');
    $assert_session->statusCodeEquals(200);
    $assert_session->linkExists('Manage');
    $assert_session->linkNotExists('Edit');

    // Click 'manager_group' link and move to the group edit form.
    $this->clickLink('manager_group');

    // Check that details information exists.
    $assert_session->responseContains('<details data-drupal-selector="edit-information" id="edit-information" class="js-form-wrapper form-wrapper">');

    // Check that users element exists.
    $assert_session->fieldNotExists('label');
    $assert_session->fieldNotExists('description[value]');
    $assert_session->fieldNotExists('type');
    $assert_session->fieldNotExists('admins[]');
    $assert_session->fieldExists('users[]');
    $assert_session->fieldNotExists('entities[]');
    $assert_session->fieldNotExists('permissions[administer]');
  }

}
