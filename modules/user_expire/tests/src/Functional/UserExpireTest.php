<?php

namespace Drupal\Tests\user_expire\Functional;

use Drupal\Core\Database\Database;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Tests\BrowserTestBase;
use Drupal\user\Entity\Role;
use Drupal\user\RoleInterface;

/**
 * Tests for User expire module.
 *
 * @group user_expire
 */
class UserExpireTest extends BrowserTestBase {

  use StringTranslationTrait;

  /**
   * Default theme.
   *
   * @var string
   */
  protected $defaultTheme = 'stark';

  /**
   * Modules to install.
   *
   * @var array
   */
  protected static $modules = ['user_expire'];

  /**
   * Tests user expiration functionality.
   */
  public function testUserExpire(): void {
    $connection = Database::getConnection();
    $basic_account = $this->drupalCreateUser();
    $this->assertTrue($basic_account->isActive(), $this->t('User account is currently enabled.'));
    user_expire_set_expiration($basic_account, \Drupal::time()->getRequestTime() - 1);
    user_expire_expire_users([$basic_account]);
    $this->assertFalse($basic_account->isActive(), $this->t('User account has been successfully disabled.'));

    // Admin sets expiry, it's saved properly.
    $admin_user = $this->drupalCreateUser([
      'administer permissions', 'administer users',
      'set user expiration', 'view expiring users report',
      'administer user expire settings',
    ]);
    $this->drupalLogin($admin_user);

    // Ensure the report is clear.
    $this->drupalGet('admin/reports/expiring-users');
    $this->assertSession()->responseNotContains('0 sec from now', $this->t('Processed expiration does not show in Expiring users report'));

    // Make them active again.
    $edit = [];
    $edit['status'] = 1;
    // And set the expiration to something passed.
    $edit['user_expiration'] = 1;
    $edit['user_expiration_date[date]'] = "2002-08-18";

    $this->drupalGet("user/" . $basic_account->id() . "/edit");
    $this->submitForm($edit, $this->t('Save')->render());
    // Ensure it was re-activated.
    $this->assertSession()->responseContains('type="radio" id="edit-status-1" name="status" value="1" checked="checked" class="form-radio"', $this->t('User account is currently enabled.'));

    // And the expiration was really really saved.
    $this->assertSession()->responseContains('expiration date is set to ' . \Drupal::service('date.formatter')->format(strtotime('2002-08-18')) . '.');
    $this->drupalGet('admin/reports/expiring-users');
    $this->assertSession()->responseContains('0 sec from now', 'Expiration shows in Expiring users report');
    $this->drupalLogout();

    // User edits account, expiry is still set.
    $this->drupalLogin($basic_account);
    $edit = [];
    $edit['pass[pass1]'] = $new_pass = $this->randomMachineName();
    $edit['pass[pass2]'] = $new_pass;

    $edit['current_pass'] = $basic_account->pass_raw;
    $this->drupalGet("user/" . $basic_account->id() . "/edit");
    $this->submitForm($edit, $this->t('Save')->render());
    $this->assertSession()->responseContains($this->t("The changes have been saved."));
    $this->drupalLogout();

    // Admin looks again and expiry is still set.
    $this->drupalLogin($admin_user);
    $this->drupalGet('admin/reports/expiring-users');
    $this->assertSession()->responseContains('0 sec from now', 'Expiration shows in Expiring users report');

    // Cron runs, account is locked, removed from expiry.
    user_expire_cron();
    $this->drupalGet('admin/reports/expiring-users');
    $this->assertSession()->responseNotContains('0 sec from now', $this->t('Processed expiration does not show in Expiring users report'));
    $this->drupalGet("user/" . $basic_account->id() . "/edit");
    $this->assertSession()->responseContains('type="radio" id="edit-status-0" name="status" value="0" checked="checked" class="form-radio"', $this->t('User account is currently disabled.'));

    // Testing account expiry by role.
    // Create a role.
    $rid = $this->drupalCreateRole([]);
    $edit = ['label' => $rid, 'id' => $rid . '_role'];
    $this->drupalGet('admin/people/roles/add');
    $this->submitForm($edit, $this->t('Save')->render());
    $this->assertSession()->statusMessageContains($this->t('Role @name has been added.', ['@name' => $rid]), 'status');
    $role = Role::load($rid);
    $this->assertTrue(is_object($role), 'The role was successfully retrieved from the database.');

    // Grant that role to the basic user.
    $edit = [];
    $edit['status'] = 1;
    // And definitely unset the expiration.
    $edit['user_expiration'] = FALSE;
    $edit['roles[' . $rid . ']'] = $rid;
    $this->drupalGet("user/" . $basic_account->id() . "/edit");
    $this->submitForm($edit, $this->t('Save')->render());
    $this->assertSession()->responseContains('type="radio" id="edit-status-1" name="status" value="1" checked="checked" class="form-radio"', $this->t('User account is currently enabled.'));

    // Confirm there are no per-user expiration records.
    $this->drupalGet('admin/reports/expiring-users');
    $this->assertSession()->responseNotContains('0 sec from now', $this->t('Processed expiration does not show in Expiring users report'));

    // Fake that their access time is 90 days and 2 seconds.
    // Be sure to use REQUEST_TIME because the query to identify uses
    // REQUEST_TIME and that value gets pretty old in the context of simpletest.
    $connection->query('UPDATE {users_field_data} SET access = :time WHERE uid = :uid', [
      ':time' => \Drupal::time()->getRequestTime() - 7776002,
      ':uid' => $basic_account->id(),
    ]);

    // Set it to expire after 90 days of inactivity.
    $edit = ['user_expire_' . $rid => 7776000];
    $this->drupalGet("admin/config/people/user-expire");
    $this->submitForm($edit, $this->t('Save configuration')->render());

    // Process it.
    user_expire_expire_by_role();

    // Ensure they are disabled.
    $this->drupalGet("user/" . $basic_account->id() . "/edit");
    $this->assertSession()->responseContains('type="radio" id="edit-status-0" name="status" value="0" checked="checked" class="form-radio"', $this->t('User account is currently disabled.'));

    // Ensure a brand new user is not blocked (i.e. access = 0).
    $new_basic_account = $this->drupalCreateUser();

    // Set auth users to expire after 90 days of inactivity.
    $edit = ['user_expire_' . RoleInterface::AUTHENTICATED_ID => 7776000];
    $this->drupalGet("admin/config/people/user-expire");
    $this->submitForm($edit, $this->t('Save configuration'));

    // Process it.
    user_expire_expire_by_role();

    // Ensure they are still enabled.
    $this->drupalGet("user/" . $new_basic_account->id() . "/edit");
    $this->assertSession()->responseContains('type="radio" id="edit-status-1" name="status" value="1" checked="checked" class="form-radio"', $this->t('New user account stays active.'));

    // Age the new user's created by 90+ days.
    $connection->query('UPDATE {users_field_data} SET created = :time WHERE uid = :uid', [
      ':time' => \Drupal::time()->getRequestTime() - 7776002,
      ':uid' => $new_basic_account->id(),
    ]);

    // Process it.
    user_expire_expire_by_role();

    // Ensure they are disabled.
    $this->drupalGet("user/" . $new_basic_account->id() . "/edit");
    $this->assertSession()->responseContains('type="radio" id="edit-status-0" name="status" value="0" checked="checked" class="form-radio"', $this->t('User account is currently disabled.'));
  }

}
