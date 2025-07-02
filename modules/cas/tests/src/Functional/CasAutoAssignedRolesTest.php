<?php

namespace Drupal\Tests\cas\Functional;

use Drupal\cas\CasPropertyBag;
use Drupal\user\Entity\Role;

/**
 * Tests CAS admin settings form.
 *
 * @group cas
 */
class CasAutoAssignedRolesTest extends CasBrowserTestBase {

  /**
   * Tests Standard installation profile.
   */
  public function testCasAutoAssignedRoles() {
    $this->drupalLogin($this->drupalCreateUser(['administer account settings']));
    $role_1 = $this->drupalCreateRole([]);
    $role_2 = $this->drupalCreateRole([]);
    $edit = [
      'user_accounts[auto_register]' => TRUE,
      'user_accounts[auto_assigned_roles_enable]' => TRUE,
      'user_accounts[auto_assigned_roles][]' => [$role_1, $role_2],
      'user_accounts[email_hostname]' => 'sample.com',
    ];
    $this->drupalGet('/admin/config/people/cas');
    $this->submitForm($edit, 'Save configuration');

    $this->assertEquals([$role_1, $role_2], $this->config('cas.settings')->get('user_accounts.auto_assigned_roles'));

    $cas_property_bag = new CasPropertyBag('test_cas_user_name');
    // Until we come up with a good way to mock the cURL request that goes to
    // the CAS server to validate the ticket, we can instead just invoke the
    // login method of the user manager service directly. Not truly a functional
    // test, but good enough for now.
    \Drupal::service('cas.user_manager')->login($cas_property_bag, 'fake_ticket_string');
    $user = user_load_by_name('test_cas_user_name');
    $this->assertTrue($user->hasRole($role_1), 'The user has the auto assigned role: ' . $role_1);
    $this->assertTrue($user->hasRole($role_2), 'The user has the auto assigned role: ' . $role_2);

    // Removing the role should remove it from our configuration as well.
    Role::load($role_2)->delete();
    $this->assertEquals([$role_1], $this->config('cas.settings')->get('user_accounts.auto_assigned_roles'));

    // If we manually remove one of the roles from the user we just logged in
    // for the first time, it should not be re-added if we log them in again.
    $user = user_load_by_name('test_cas_user_name');
    $user->removeRole($role_1);
    $user->save();
    \Drupal::service('cas.user_manager')->login($cas_property_bag, 'fake_ticket_string2');
    $this->assertFalse($user->hasRole($role_1), 'The user should not have been re-assigned role: ' . $role_1);
  }

}
