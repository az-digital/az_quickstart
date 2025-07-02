<?php

namespace Drupal\Tests\cas\Functional;

/**
 * Tests adding bulk CAS users.
 *
 * @group cas
 */
class CasBulkAddCasUsersTest extends CasBrowserTestBase {

  /**
   * Tests the bulk add form.
   */
  public function testBulkAddForm() {
    // Create two users, one associated with CAS and one that's not.
    $this->createRole([], 'sample_role', 'sample role');
    $cas_user = $this->drupalCreateUser([], 'test1');
    \Drupal::service('cas.user_manager')->setCasUsernameForAccount($cas_user, 'test1');
    $this->drupalCreateUser([], 'test2');

    $this->drupalLogin($this->drupalCreateUser(['administer users']));
    $edit = [
      'cas_usernames' => " test1 \ntest2\n test 3\n\n\ntest4",
      'email_hostname' => 'sample.com',
      'roles[sample_role]' => TRUE,
    ];
    $this->drupalGet('/admin/people/create/cas-bulk');

    $this->submitForm($edit, 'Create new accounts');

    $casUserManager = \Drupal::service('cas.user_manager');

    // Assert that test3 and test4 accounts were created.
    $user_test3 = user_load_by_name('test 3');
    $this->assertNotFalse($user_test3, 'User with username "test 3" exists.');
    $this->assertTrue($user_test3->hasRole('sample_role'), 'The "test 3" user has role "sample_role"');
    $this->assertEquals('test 3@sample.com', $user_test3->get('mail')->value, 'The "test 3" user has the email "test 3@sample.com".');
    $this->assertEquals('test 3', $casUserManager->getCasUsernameForAccount($user_test3->id()));

    $user_test4 = user_load_by_name('test4');
    $this->assertNotFalse($user_test4, 'User with username "test4" exists.');
    $this->assertEquals('test4@sample.com', $user_test4->get('mail')->value, 'The "test4" user has the email "test4@sample.com".');
    $this->assertTrue($user_test4->hasRole('sample_role'), 'The "test4" user has role "sample_role"');
    $this->assertEquals('test4', $casUserManager->getCasUsernameForAccount($user_test4->id()));

    // test2 user should result in error, because a Drupal user account already
    // exists with that username.
    $this->assertSession()->responseContains('An error was encountered creating accounts for the following users (check logs for more details): <em class="placeholder">test2</em>');

    // test1 user should result in a failure because the CAS username is already
    // in use.
    $this->assertSession()->responseContains('The following accounts were not registered because existing accounts are already using the usernames: <em class="placeholder">test1</em>');

    // But the other accounts should register just fine.
    $this->assertSession()->responseContains('Successfully created accounts for the following usernames: <em class="placeholder"><a href="' . base_path() . 'user/' . $user_test3->id() . '" hreflang="en">test 3</a>, <a href="' . base_path() . 'user/' . $user_test4->id() . '" hreflang="en">test4</a></em>');
  }

}
