<?php

namespace Drupal\Tests\cas\Functional;

use Drupal\Core\Test\AssertMailTrait;
use Drupal\Tests\cas\Traits\CasTestTrait;
use Drupal\user\UserInterface;

/**
 * Tests auto-registration when Drupal requires admin approval on registration.
 *
 * @group cas
 */
class CasAdminApprovalRegistrationTest extends CasBrowserTestBase {

  use CasTestTrait;
  use AssertMailTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block',
    'cas_mock_server',
  ];

  /**
   * Tests the case when Drupal requires admin approval on registration.
   */
  public function testAdminApproval(): void {
    $assert = $this->assertSession();

    $this->config('cas.settings')
      ->set('user_accounts.auto_register', TRUE)
      ->set('user_accounts.email_assignment_strategy', 1)
      ->set('user_accounts.email_attribute', 'email')
      ->save();
    $this->config('user.settings')
      ->set('register', UserInterface::REGISTER_VISITORS_ADMINISTRATIVE_APPROVAL)
      ->save();
    $this->createCasUser('beavis', 'beavis@example.com', 'needtp', [
      'firstname' => 'Beavis',
      'lastname' => 'El Gran Cornholio',
    ]);
    // Place the login/logout block so that we can check if user is logged in.
    $this->placeBlock('system_menu_block:account');

    // Account registratio policy is not yet enforced.
    $this->casLogin('beavis@example.com', 'needtp');
    $this->assertUserLoggedIn();
    $account = user_load_by_name('beavis');
    $this->assertInstanceOf(UserInterface::class, $account);
    $this->assertTrue($account->isActive());

    // Delete the local account and enforce site's registration policy.
    $this->drupalLogout();
    $account->delete();
    $this->config('cas.settings')
      ->set('user_accounts.auto_register_follow_registration_policy', TRUE)
      ->save();

    // Account is created and pending approval.
    $this->casLogin('beavis@example.com', 'needtp');
    $this->assertUserNotLoggedIn();
    $assert->statusMessageContains('Thank you for applying for an account. Your account is currently pending approval by the site administrator.');
    $assert->statusMessageContains('In the meantime, a welcome message with further instructions has been sent to your email address.');
    $account = user_load_by_name('beavis');
    $this->assertInstanceOf(UserInterface::class, $account);
    $this->assertTrue($account->isBlocked());
    [$email] = $this->getMails(['to' => 'beavis@example.com']);
    $this->assertSame('Account details for beavis at Drupal (pending admin approval)', $email['subject']);
    $this->assertStringContainsString('Thank you for registering at Drupal. Your application for an account is', $email['body']);
    $this->assertStringContainsString('currently pending approval', $email['body']);
    [$email] = $this->getMails(['to' => 'simpletest@example.com']);
    $this->assertSame('Account details for beavis at Drupal (pending admin approval)', $email['subject']);
    $this->assertStringContainsString('beavis has applied for an account.', $email['body']);

    // The user comes back and tries again to login. The account is registered
    // but still blocked. Check that it gets the proper error message.
    $this->casLogin('beavis@example.com', 'needtp');
    $this->assertUserNotLoggedIn();
    $assert->statusMessageContains('Your account is blocked or has not been activated. Please contact a site administrator.', 'error');

    // Delete the local account and drop admin approval.
    $account->delete();
    $this->config('user.settings')
      ->set('register', UserInterface::REGISTER_VISITORS)
      ->save();

    // Account is created and the user is logged in.
    $this->casLogin('beavis@example.com', 'needtp');
    $this->assertUserLoggedIn();
    $account = user_load_by_name('beavis');
    $this->assertInstanceOf(UserInterface::class, $account);
    $this->assertTrue($account->isActive());
  }

  /**
   * Tests the setting form.
   */
  public function testCasSettings(): void {
    $this->config('user.settings')
      ->set('register', UserInterface::REGISTER_ADMINISTRATORS_ONLY)
      ->save();
    $page = $this->getSession()->getPage();
    $this->drupalLogin($this->createUser(['administer account settings']));
    $this->drupalGet('/admin/config/people/cas');
    $page->checkField("Automatically register users");
    $page->checkField("Follow site's account registration policy");
    $page->pressButton('Save configuration');
    $this->assertSession()->statusMessageContains("Auto-registering accounts is not possible while following the account registration policy because the policy requires that new accounts to be created only by administrators. Either uncheck Follow site's account registration policy or change the policy at account settings.");
  }

}
