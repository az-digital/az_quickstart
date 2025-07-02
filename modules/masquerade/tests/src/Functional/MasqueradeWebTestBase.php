<?php

namespace Drupal\Tests\masquerade\Functional;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\Crypt;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Tests\block\Functional\AssertBlockAppearsTrait;
use Drupal\Tests\BrowserTestBase;
use Drupal\user\Entity\Role;
use Drupal\Tests\system\Functional\Cache\AssertPageCacheContextsAndTagsTrait;

/**
 * Base test class for Masquerade module web tests.
 *
 * @todo Core: $this->session_id is reset to NULL upon every internal browser
 *   request.
 * @see http://drupal.org/node/1555862
 */
abstract class MasqueradeWebTestBase extends BrowserTestBase {

  use AssertBlockAppearsTrait;
  use StringTranslationTrait;
  use AssertPageCacheContextsAndTagsTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['masquerade', 'user', 'block'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Admin users for the tests.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * Admin users for the tests.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $authUser;

  /**
   * Editor users for the tests.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $editorUser;

  /**
   * Masquerade users for the tests.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $masqueradeUser;

  /**
   * Moderator users for the tests.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $moderatorUser;

  /**
   * Lead editor user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $leadEditorUser;

  /**
   * Super user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $superUser;

  /**
   * Admin roles for the tests.
   *
   * @var \Drupal\user\RoleInterface
   */
  protected $adminRole;

  /**
   * Editor roles for the tests.
   *
   * @var \Drupal\user\RoleInterface
   */
  protected $editorRole;

  /**
   * Masquerade roles for the tests.
   *
   * @var \Drupal\user\RoleInterface
   */
  protected $masqueradeRole;

  /**
   * Moderator roles for the tests.
   *
   * @var \Drupal\user\RoleInterface
   */
  protected $moderatorRole;

  /**
   * Lead role.
   *
   * @var \Drupal\user\RoleInterface
   */
  protected $leadRole;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create and configure User module's admin role.
    $this->adminRole = Role::create([
      'id' => 'administrator',
      'label' => 'Administrator',
    ]);
    // Users in this role get all permissions assigned by default.
    $this->adminRole->set('is_admin', TRUE)->save();

    // Create a 'masquerade' role to masquerade as users without roles.
    $this->masqueradeRole = Role::create([
      'id' => 'masquerade',
      'label' => 'Masquerade',
    ]);
    $this->masqueradeRole
      // Allow only authenticated (without any other roles).
      ->grantPermission('masquerade as authenticated')
      ->save();

    // Create an additional 'editor' role to masquerade as basic users.
    $this->editorRole = Role::create([
      'id' => 'editor',
      'label' => 'Editor',
    ]);
    $this->editorRole
      ->grantPermission('masquerade as masquerade')
      ->grantPermission('masquerade as authenticated')
      ->save();

    // Create a 'lead' role to masquerade as editors.
    $this->leadRole = Role::create([
      'id' => 'lead',
      'label' => 'Lead Editor',
    ]);
    $this->leadRole
      ->grantPermission('masquerade as editor')
      ->grantPermission('masquerade as authenticated')
      ->save();

    // Create an additional 'moderator' role to check 'masquerade as any user'.
    $this->moderatorRole = Role::create([
      'id' => 'moderator',
      'label' => 'Moderator',
    ]);
    $this->moderatorRole
      ->grantPermission('masquerade as any user')
      ->save();

    // Create test users with varying privilege levels.
    // Administrative user with User module's admin role *only*.
    $this->adminUser = $this->drupalCreateUser();
    $this->adminUser->setUsername('admin_user');
    $this->adminUser->addRole($this->adminRole->id());
    $this->adminUser->save();

    // Moderator user.
    $this->moderatorUser = $this->drupalCreateUser();
    $this->moderatorUser->setUsername('moderator_user');
    $this->moderatorUser->addRole($this->moderatorRole->id());
    $this->moderatorUser->save();

    // Editor user.
    $this->editorUser = $this->drupalCreateUser();
    $this->editorUser->setUsername('editor_user');
    $this->editorUser->addRole($this->editorRole->id());
    $this->editorUser->save();

    // Lead editor user.
    $this->leadEditorUser = $this->drupalCreateUser();
    $this->leadEditorUser->setUsername('lead_editor_user');
    $this->leadEditorUser->addRole($this->leadRole->id());
    $this->leadEditorUser->save();

    // Super user.
    $this->superUser = $this->drupalCreateUser();
    $this->superUser->setUsername('super_user');
    $this->superUser->addRole($this->editorRole->id());
    $this->superUser->addRole($this->adminRole->id());
    $this->superUser->save();

    // Masquerade user.
    $this->masqueradeUser = $this->drupalCreateUser();
    $this->masqueradeUser->setUsername('masquerade_user');
    $this->masqueradeUser->addRole($this->masqueradeRole->id());
    $this->masqueradeUser->save();

    // Authenticated user.
    $this->authUser = $this->drupalCreateUser();

    // Place block to allow unmasquerade link accessible.
    $this->drupalPlaceBlock('system_menu_block:account');
  }

  /**
   * Masquerades as another user.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account to masquerade as.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   * @throws \Behat\Mink\Exception\ResponseTextException
   */
  protected function masqueradeAs(AccountInterface $account) {
    $this->drupalGet('user/' . $account->id());
    $this->clickLink($this->t('Masquerade as @name', ['@name' => $account->getDisplayName()]));
    // $this->drupalGet('user/' . $account->id() . '/masquerade', [
    // 'query' => [
    // 'token' =>
    // $this->drupalGetToken('user/' . $account->id() . '/masquerade'),
    // ],
    // ]);
    // $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()
      ->pageTextContains('You are now masquerading as ' . $account->label());

    // Update the logged in user account.
    // @see \Drupal\Tests\BrowserTestBase::drupalLogin()
    if (isset($this->session_id)) {
      // $this->loggedInUser = $account;
      // $this->loggedInUser->session_id = $this->session_id;
    }
  }

  /**
   * Unmasquerades the current user.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account to unmasquerade from.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   * @throws \Behat\Mink\Exception\ResponseTextException
   */
  protected function unmasquerade(AccountInterface $account) {
    $this->drupalGet('user/' . $account->id());
    $this->clickLink('Unmasquerade');
    // $this->drupalGet('unmasquerade', [
    // 'query' => [
    // 'token' => $this->drupalGetToken('unmasquerade'),
    // ],
    // ]);
    // $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()
      ->pageTextContains('You are no longer masquerading as ' . $account->label());

    // Update the logged in user account.
    // @see \Drupal\Tests\BrowserTestBase::drupalLogin()
    if (isset($this->session_id)) {
      // $this->loggedInUser = $account;
      // $this->loggedInUser->session_id = $this->session_id;
    }
  }

  /**
   * Asserts that there is a session for a given user ID.
   *
   * @param int $uid
   *   The user ID for which to find a session record.
   * @param int|null $masquerading
   *   (optional) The expected value of the 'masquerading' session data. Pass
   *   NULL to assert that the session data is not set.
   */
  protected function assertSessionByUid($uid, $masquerading = NULL) {
    $result = \Drupal::database()
      ->query('SELECT * FROM {sessions} WHERE uid = :uid', [
        ':uid' => $uid,
      ])->fetchAll();
    if (empty($result)) {
      $this->fail("No session found for uid $uid");
    }
    elseif (count($result) > 1) {
      // If there is more than one session, then that must be unexpected.
      $this->fail("Found more than 1 session for uid $uid.");
    }
    else {
      $session = reset($result);

      // Careful: PHP does not provide a utility function that decodes session
      // data only. Using string comparison because rely on default storage.
      if ($masquerading) {
        $expected = '"masquerading";s:' . strlen($masquerading) . ':"' . $masquerading . '"';
        self::assertNotFalse(strpos($session->session, $expected), new FormattableMarkup('Session flag "masquerading" equals @uid.', [
          '@uid' => $masquerading,
        ]));
      }
      else {
        $expected = empty($session->session) || strpos($session->session, 'masquerading') === FALSE;
        self::assertTrue($expected, 'Session flag "masquerading" is not set.');
      }
    }
  }

  /**
   * Asserts that no session exists for a given uid.
   *
   * @param int $uid
   *   The user ID to assert.
   */
  protected function assertNoSessionByUid($uid) {
    $result = \Drupal::database()->query('SELECT * FROM {sessions} WHERE uid = :uid', [
      ':uid' => $uid,
    ])->fetchAll();
    self::assertEmpty($result, "No session for uid $uid found.");
  }

  /**
   * Stop-gap fix.
   *
   * @see http://drupal.org/node/1555862
   */
  protected function drupalGetToken($value = '') {
    // Use the same code as \Drupal\Core\Access\CsrfTokenGenerator::get().
    $private_key = $this->container->get('private_key')->get();
    /** @var \Drupal\Core\Session\MetadataBag $session_metadata */
    $session_metadata = $this->container->get('session_manager.metadata_bag');
    // @todo Try to get seed from testing site, broken now.
    $seed = $session_metadata->getCsrfTokenSeed();
    return Crypt::hmacBase64($value, $seed . $private_key . Settings::getHashSalt());
  }

}
