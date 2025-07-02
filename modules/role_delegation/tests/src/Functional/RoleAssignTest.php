<?php

namespace Drupal\Tests\role_delegation\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Functional tests for assigning roles.
 *
 * @group role_delegation
 */
class RoleAssignTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['user', 'role_delegation', 'node'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Ensure we can only see the roles we have permission to assign.
   */
  public function testRoleAccess() {
    $rid1 = $this->drupalCreateRole([]);
    $rid2 = $this->drupalCreateRole([]);
    $rid3 = $this->drupalCreateRole([]);

    // Only 2 of the 3 roles appear on the roles edit page.
    $current_user = $this->drupalCreateUser([
      sprintf('assign %s role', $rid1),
      sprintf('assign %s role', $rid2),
    ]);
    $this->drupalLogin($current_user);
    $account = $this->drupalCreateUser();
    $this->drupalGet(sprintf('/user/%s/roles', $account->id()));
    $this->assertSession()->fieldExists(sprintf('role_change[%s]', $rid1));
    $this->assertSession()->fieldExists(sprintf('role_change[%s]', $rid2));
    $this->assertSession()->fieldNotExists(sprintf('role_change[%s]', $rid3));

    // A user who can access the real roles field should not see the role
    // delegation field.
    $current_user = $this->drupalCreateUser([
      'administer users',
      'administer permissions',
      'assign all roles',
    ]);
    $this->drupalLogin($current_user);
    $this->drupalGet(sprintf('/user/%s/edit', $account->id()));
    $this->assertSession()->fieldExists(sprintf('roles[%s]', $rid1));
    $this->assertSession()->fieldNotExists(sprintf('role_change[%s]', $rid1));

    // A user who can edit a user, but does not have access to the real role
    // field, but can delegate should see the role delegation field.
    $current_user = $this->drupalCreateUser([
      'administer users',
      'assign all roles',
    ]);
    $this->drupalLogin($current_user);
    $this->drupalGet(sprintf('/user/%s/edit', $account->id()));
    $this->assertSession()->fieldNotExists(sprintf('roles[%s]', $rid1), NULL);
    $this->assertSession()->fieldExists(sprintf('role_change[%s]', $rid1));

    // Similar, but single role permissions rather than assigning all roles.
    $current_user = $this->drupalCreateUser([
      'administer users',
      sprintf('assign %s role', $rid1),
    ]);
    $this->drupalLogin($current_user);
    $this->drupalGet(sprintf('/user/%s/edit', $account->id()));
    $this->assertSession()->fieldNotExists(sprintf('roles[%s]', $rid1), NULL);
    $this->assertSession()->fieldExists(sprintf('role_change[%s]', $rid1));
    $this->assertSession()->fieldNotExists(sprintf('role_change[%s]', $rid2), NULL);
  }

  /**
   * Test that we can assign roles we have access to via the Roles form.
   */
  public function testRoleAssignRolesForm() {
    $user_storage = \Drupal::entityTypeManager()->getStorage('user');
    // Create a role and login as a user with the permission to assign it.
    $rid1 = $this->drupalCreateRole([]);
    $rid2 = $this->drupalCreateRole([]);
    $current_user = $this->drupalCreateUser([
      sprintf('assign %s role', $rid1),
      sprintf('assign %s role', $rid2),
    ]);
    $this->drupalLogin($current_user);

    // Go to the users roles edit page.
    $account = $this->drupalCreateUser();
    $this->drupalGet(sprintf('/user/%s/roles', $account->id()));

    // The form element field id and name.
    $field_id = sprintf('edit-role-change-%s', $rid1);
    $field_name = sprintf('role_change[%s]', $rid1);

    // Ensure its disabled by default.
    $this->assertSession()->checkboxNotChecked($field_id);
    self::assertFalse($account->hasPermission('assign $rid1 role'), 'The target user does not have the role by default.');
    $this->assertSession()->checkboxNotChecked($field_id);

    // Assign the role and ensure its now checked and assigned.
    $this->submitForm([$field_name => $rid1], 'Save');
    $user_storage->resetCache();
    $account = $user_storage->load($account->id());
    self::assertTrue($account->hasRole($rid1), 'The target user has been granted the role.');
    $this->assertSession()->checkboxChecked($field_id);

    // Revoke the role.
    $this->submitForm([$field_name => FALSE], 'Save');
    $user_storage->resetCache();
    $account = $user_storage->load($account->id());
    self::assertFalse($account->hasRole($rid1), 'The target user has gotten the role revoked.');
    $this->assertSession()->checkboxNotChecked($field_id);
  }

  /**
   * Test that we can assign roles we have access to via the user edit form.
   */
  public function testRoleAssignUserForm() {
    $user_storage = \Drupal::entityTypeManager()->getStorage('user');
    $rid1 = $this->drupalCreateRole([]);
    $current_user = $this->drupalCreateUser([
      'administer users',
      'assign all roles',
    ]);
    $this->drupalLogin($current_user);

    // Go to the users roles edit page.
    $account = $this->drupalCreateUser();
    $this->drupalGet(sprintf('/user/%s/edit', $account->id()));

    // The form element field id and name.
    $field_id = sprintf('edit-role-change-%s', $rid1);
    $field_name = sprintf('role_change[%s]', $rid1);

    // Ensure its disabled by default.
    self::assertFalse($account->hasPermission(sprintf('assign %s role', $rid1)), 'The target user does not have the role by default.');
    $this->assertSession()->checkboxNotChecked($field_id);

    // Assign the role and ensure its now checked and assigned.
    $this->submitForm([$field_name => $rid1], 'Save');
    $user_storage->resetCache();
    $account = $user_storage->load($account->id());
    self::assertTrue($account->hasRole($rid1), 'The target user has been granted the role.');
    $this->assertSession()->checkboxChecked($field_id);

    // Revoke the role.
    $this->submitForm([$field_name => FALSE], 'Save');
    $user_storage->resetCache();
    $account = $user_storage->load($account->id());
    self::assertFalse($account->hasRole($rid1), 'The target user has gotten the role revoked.');
    $this->assertSession()->checkboxNotChecked($field_id);
  }

  /**
   * Test that the user has access to the role delegation page.
   */
  public function testRoleDelegationPageAccess() {
    $regular_user = $this->drupalCreateUser();

    // Anonymous users can never access the roles page.
    $this->drupalGet(sprintf('/user/%s/roles', $regular_user->id()));
    $this->assertSession()->statusCodeEquals(403);

    // Users with 'administer users' cannot view the page, they must use
    // the normal user edit page or also 'have assign all roles'.
    $account = $this->createUser(['administer users']);
    $this->drupalLogin($account);
    $this->drupalGet(sprintf('/user/%s/roles', $regular_user->id()));
    $this->assertSession()->statusCodeEquals(403);

    // Users with 'administer permissions' cannot view the page, they must use
    // the normal user edit page or also 'have assign all roles'.
    $account = $this->createUser(['administer permissions']);
    $this->drupalLogin($account);
    $this->drupalGet(sprintf('/user/%s/roles', $regular_user->id()));
    $this->assertSession()->statusCodeEquals(403);

    // Users with a custom 'assign %custom role' permission should be able to
    // see the role admin page.
    $role = $this->createRole([]);
    $account = $this->createUser([sprintf('assign %s role', $role)]);
    $this->drupalLogin($account);
    $this->drupalGet(sprintf('/user/%s/roles', $regular_user->id()));
    $this->assertSession()->statusCodeEquals(200);

    // Users with 'assign all roles' can view the page.
    $account = $this->createUser(['assign all roles']);
    $this->drupalLogin($account);
    $this->drupalGet(sprintf('/user/%s/roles', $regular_user->id()));
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Test access to the "Roles" entity operation.
   */
  public function testRoleDelegationEntityOperationAccess() {
    // Make sure the entity operation is only added to users.
    $node = $this->drupalCreateNode();
    $this->drupalGet('/admin/content');
    $this->assertSession()->linkByHrefNotExists(sprintf('/user/%s/roles', $node->id()));
  }

}
