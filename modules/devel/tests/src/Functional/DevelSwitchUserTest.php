<?php

namespace Drupal\Tests\devel\Functional;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Core\Session\AccountInterface;

/**
 * Tests switch user.
 *
 * @group devel
 */
class DevelSwitchUserTest extends DevelBrowserTestBase {

  /**
   * The block used by this test.
   *
   * @var \Drupal\block\BlockInterface
   */
  protected $block;

  /**
   * The switch user.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $switchUser;

  /**
   * The web user.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $webUser;

  /**
   * The long user with maximum length username.
   *
   * @var \Drupal\user\Entity\User
   */
  protected $longUser;

  /**
   * Set up test.
   */
  protected function setUp(): void {
    parent::setUp();

    $this->block = $this->drupalPlaceBlock('devel_switch_user', ['id' => 'switch-user', 'label' => 'Switch Hit']);

    $this->develUser = $this->drupalCreateUser(['access devel information', 'switch users'], 'Devel User Four');
    $this->switchUser = $this->drupalCreateUser(['switch users'], 'Switch User Five');
    $this->webUser = $this->drupalCreateUser([], 'Web User Six');
    $this->longUser = $this->drupalCreateUser([], 'Long User Seven name has the maximum length of 60 characters');
  }

  /**
   * Tests switch user basic functionality.
   */
  public function testSwitchUserFunctionality(): void {
    $this->drupalLogin($this->webUser);

    $this->drupalGet('');
    $this->assertSession()->pageTextNotContains($this->block->label());

    // Ensure that a token is required to switch user.
    $this->drupalGet('/devel/switch/' . $this->webUser->getDisplayName());
    $this->assertSession()->statusCodeEquals(403);

    $this->drupalLogin($this->develUser);

    $this->drupalGet('');
    $this->assertSession()->pageTextContains($this->block->label());

    // Ensure that if name in not passed the controller returns access denied.
    $this->drupalGet('/devel/switch');
    $this->assertSession()->statusCodeEquals(403);

    // Ensure that a token is required to switch user.
    $this->drupalGet('/devel/switch/' . $this->switchUser->getDisplayName());
    $this->assertSession()->statusCodeEquals(403);

    // Switch to another user account.
    $this->drupalGet('/user/' . $this->switchUser->id());
    $this->clickLink($this->switchUser->getDisplayName());
    $this->assertSessionByUid($this->switchUser->id());
    $this->assertNoSessionByUid($this->develUser->id());

    // Switch back to initial account.
    $this->clickLink($this->develUser->getDisplayName());
    $this->assertNoSessionByUid($this->switchUser->id());
    $this->assertSessionByUid($this->develUser->id());

    // Use the search form to switch to another account.
    $edit = ['userid' => $this->switchUser->getDisplayName()];
    $this->submitForm($edit, 'Switch');
    $this->assertSessionByUid($this->switchUser->id());
    $this->assertNoSessionByUid($this->develUser->id());

    // Use the form with username of the maximum length. Mimic the autofill
    // result by adding " (userid)" at the end.
    $edit = ['userid' => $this->longUser->getDisplayName() . sprintf(' (%s)', $this->longUser->id())];
    $this->submitForm($edit, 'Switch');
    $this->assertSessionByUid($this->longUser->id());
    $this->assertNoSessionByUid($this->switchUser->id());
  }

  /**
   * Tests the switch user block configuration.
   */
  public function testSwitchUserBlockConfiguration(): void {
    $anonymous = \Drupal::config('user.settings')->get('anonymous');

    // Create some users for the test.
    for ($i = 0; $i < 12; ++$i) {
      $this->drupalCreateUser();
    }

    $this->drupalLogin($this->develUser);

    $this->drupalGet('');
    $this->assertSession()->pageTextContains($this->block->label());

    // Ensure that block default configuration is effectively used. The block
    // default configuration is the following:
    // - list_size : 12.
    // - include_anon : FALSE.
    // - show_form : TRUE.
    $this->assertSwitchUserSearchForm();
    $this->assertSwitchUserListCount(12);
    $this->assertSwitchUserListNoContainsUser($anonymous);

    // Ensure that changing the list_size configuration property the number of
    // user displayed in the list change.
    $this->setBlockConfiguration('list_size', 4);
    $this->drupalGet('');
    $this->assertSwitchUserListCount(4);

    // Ensure that changing the include_anon configuration property the
    // anonymous user is displayed in the list.
    $this->setBlockConfiguration('include_anon', TRUE);
    $this->drupalGet('');
    $this->assertSwitchUserListContainsUser($anonymous);

    // Ensure that changing the show_form configuration property the
    // form is not displayed.
    $this->setBlockConfiguration('show_form', FALSE);
    $this->drupalGet('');
    $this->assertSwitchUserNoSearchForm();
  }

  /**
   * Test the user list items.
   */
  public function testSwitchUserListItems(): void {
    $anonymous = \Drupal::config('user.settings')->get('anonymous');

    $this->setBlockConfiguration('list_size', 2);

    // Login as web user so we are sure that this account is prioritized
    // in the list if not enough users with 'switch users' permission are
    // present.
    $this->drupalLogin($this->webUser);

    $this->drupalLogin($this->develUser);
    $this->drupalGet('');

    // Ensure that users with 'switch users' permission are prioritized.
    $this->assertSwitchUserListCount(2);
    $this->assertSwitchUserListContainsUser($this->develUser->getDisplayName());
    $this->assertSwitchUserListContainsUser($this->switchUser->getDisplayName());

    // Ensure that blocked users are not shown in the list.
    $this->switchUser->set('status', 0)->save();
    $this->drupalGet('');
    $this->assertSwitchUserListCount(2);
    $this->assertSwitchUserListContainsUser($this->develUser->getDisplayName());
    $this->assertSwitchUserListContainsUser($this->webUser->getDisplayName());
    $this->assertSwitchUserListNoContainsUser($this->switchUser->getDisplayName());

    // Ensure that anonymous user are prioritized if include_anon is set to
    // true.
    $this->setBlockConfiguration('include_anon', TRUE);
    $this->drupalGet('');
    $this->assertSwitchUserListCount(2);
    $this->assertSwitchUserListContainsUser($this->develUser->getDisplayName());
    $this->assertSwitchUserListContainsUser($anonymous);

    // Ensure that the switch user block works properly even if no prioritized
    // users are found (special handling for user 1).
    $this->drupalLogout();
    $this->develUser->delete();

    $this->drupalLogin($this->rootUser);
    $this->drupalGet('');
    $this->assertSwitchUserListCount(2);
    // Removed assertion on rootUser which causes random test failures.
    // @todo Adjust the tests when user 1 option is completed.
    // @see https://www.drupal.org/project/devel/issues/3097047
    // @see https://www.drupal.org/project/devel/issues/3114264
    $this->assertSwitchUserListContainsUser($anonymous);

    $roleStorage = \Drupal::entityTypeManager()->getStorage('user_role');

    // Ensure that the switch user block works properly even if no roles have
    // the 'switch users' permission associated (special handling for user 1).
    /** @var array<string, \Drupal\user\RoleInterface> $roles */
    $roles = $roleStorage->loadMultiple();
    unset($roles[AccountInterface::ANONYMOUS_ROLE]);
    $roles = array_filter($roles, static fn($role): bool => $role->hasPermission('switch users'));
    $roleStorage->delete($roles);

    $this->drupalGet('');
    $this->assertSwitchUserListCount(2);
    // Removed assertion on rootUser which causes random test failures.
    // @todo Adjust the tests when user 1 option is completed.
    // @see https://www.drupal.org/project/devel/issues/3097047
    // @see https://www.drupal.org/project/devel/issues/3114264
    $this->assertSwitchUserListContainsUser($anonymous);
  }

  /**
   * Helper function for verify the number of items shown in the user list.
   *
   * @param int $number
   *   The expected number of items.
   */
  public function assertSwitchUserListCount($number): void {
    $result = $this->xpath('//div[@id=:block]//ul/li/a', [':block' => 'block-switch-user']);
    $this->assertCount($number, $result);
  }

  /**
   * Helper function for verify if the user list contains a username.
   *
   * @param string $username
   *   The username to check.
   */
  public function assertSwitchUserListContainsUser($username): void {
    $result = $this->xpath('//div[@id=:block]//ul/li/a[normalize-space()=:user]', [':block' => 'block-switch-user', ':user' => $username]);
    $this->assertTrue(count($result) > 0, new FormattableMarkup('User "%user" is included in the switch user list.', ['%user' => $username]));
  }

  /**
   * Helper function for verify if the user list not contains a username.
   *
   * @param string $username
   *   The username to check.
   */
  public function assertSwitchUserListNoContainsUser($username): void {
    $result = $this->xpath('//div[@id=:block]//ul/li/a[normalize-space()=:user]', [':block' => 'block-switch-user', ':user' => $username]);
    $this->assertTrue(count($result) == 0, new FormattableMarkup('User "%user" is not included in the switch user list.', ['%user' => $username]));
  }

  /**
   * Helper function for verify if the search form is shown.
   */
  public function assertSwitchUserSearchForm(): void {
    $result = $this->xpath('//div[@id=:block]//form[contains(@class, :form)]', [':block' => 'block-switch-user', ':form' => 'devel-switchuser-form']);
    $this->assertTrue(count($result) > 0, 'The search form is shown.');
  }

  /**
   * Helper function for verify if the search form is not shown.
   */
  public function assertSwitchUserNoSearchForm(): void {
    $result = $this->xpath('//div[@id=:block]//form[contains(@class, :form)]', [':block' => 'block-switch-user', ':form' => 'devel-switchuser-form']);
    $this->assertTrue(count($result) == 0, 'The search form is not shown.');
  }

  /**
   * Protected helper method to set the test block's configuration.
   */
  protected function setBlockConfiguration($key, $value) {
    $block = $this->block->getPlugin();
    $block->setConfigurationValue($key, $value);
    $this->block->save();
  }

  /**
   * Asserts that there is a session for a given user ID.
   *
   * Based off masquarade module.
   *
   * @param int $uid
   *   The user ID for which to find a session record.
   *
   * @todo find a cleaner way to do this check.
   */
  protected function assertSessionByUid($uid) {
    $result = \Drupal::database()
      ->select('sessions')
      ->fields('sessions', ['uid'])
      ->condition('uid', $uid)
      ->execute()->fetchAll();

    // Check that we have some results.
    $this->assertNotEmpty($result, sprintf('No session found for uid %s', $uid));
    // If there is more than one session, then that must be unexpected.
    $this->assertCount(1, $result, sprintf('Found more than one session for uid %s', $uid));
  }

  /**
   * Asserts that no session exists for a given uid.
   *
   * Based off masquarade module.
   *
   * @param int $uid
   *   The user ID to assert.
   *
   * @todo find a cleaner way to do this check.
   */
  protected function assertNoSessionByUid($uid) {
    $result = \Drupal::database()
      ->select('sessions')
      ->fields('sessions', ['uid'])
      ->condition('uid', $uid)
      ->execute()->fetchAll();

    $this->assertEmpty($result, sprintf('No session for uid %d found.', $uid));
  }

}
