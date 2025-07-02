<?php

namespace Drupal\Tests\cas\Functional;

use Drupal\cas\CasPropertyBag;
use Drupal\cas\Exception\CasLoginException;
use Drupal\Tests\cas\Traits\CasTestTrait;

/**
 * Tests CAS events.
 *
 * @group cas
 */
class CasEventsTest extends CasBrowserTestBase {

  use CasTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['block', 'cas', 'cas_mock_server', 'cas_test'];

  /**
   * Tests we can use the CasPreRegisterEvent to alter user properties.
   */
  public function testSettingPropertiesOnRegistration() {
    /* The "cas_test" module includes a subscriber to CasPreRegisterEvent
     * which will prefix all auto-registered users with "testing_"
     */
    $this->drupalLogin($this->drupalCreateUser(['administer account settings']));
    $edit = [
      'user_accounts[auto_register]' => TRUE,
      'user_accounts[email_hostname]' => 'sample.com',
    ];
    $this->drupalGet('/admin/config/people/cas');
    $this->submitForm($edit, 'Save configuration');

    $cas_property_bag = new CasPropertyBag('foo');
    \Drupal::service('cas.user_manager')->login($cas_property_bag, 'fake_ticket_string');

    $this->assertFalse(user_load_by_name('foo'), 'User with name "foo" exists, but should not.');
    /** @var \Drupal\user\UserInterface $account */
    $account = user_load_by_name('testing_foo');
    $this->assertNotFalse($account, 'User with name "testing_foo" was not found.');
    $this->assertTrue($account->isActive());

    /** @var \Drupal\externalauth\AuthmapInterface $authmap */
    $authmap = \Drupal::service('externalauth.authmap');

    // Check that the external name has been registered correctly.
    $this->assertSame('foo', $authmap->get($account->id(), 'cas'));

    // Assert the status property is maintained during the registration process.
    \Drupal::state()->set('cas_test.blocked_status', TRUE);
    $cas_property_bag = new CasPropertyBag('blocked_foo');
    try {
      // We will get a login exception due to the blocked status.
      \Drupal::service('cas.user_manager')->login($cas_property_bag, 'fake_ticket_string');
    }
    catch (CasLoginException $e) {
      // We do nothing with the exception.
    }
    $account = user_load_by_name('testing_blocked_foo');
    $this->assertFalse($account->isActive());
  }

  /**
   * Tests cancelling the login process from a subscriber.
   */
  public function testLoginCancelling() {
    // Create a local user.
    $account = $this->createUser([], 'Antoine Batiste');
    // And a linked CAS user.
    $this->createCasUser('Antoine Batiste', 'antoine@example.com', 'baTistE', [], $account);
    // Place the login/logout block so that we can check if user is logged in.
    $this->placeBlock('system_menu_block:account');

    // Check the case when the subscriber didn't set a reason message.
    \Drupal::state()->set('cas_test.flag', 'cancel login without message');
    $this->casLogin('antoine@example.com', 'baTistE');
    $this->assertSession()->pageTextContains('You do not have access to log in to this website. Please contact a site administrator if you believe you should have access.');
    $this->assertSession()->linkExists('Log in');

    // Check the case when the subscriber has set a reason message.
    \Drupal::state()->set('cas_test.flag', 'cancel login with message');
    $this->casLogin('antoine@example.com', 'baTistE');
    $this->assertSession()->pageTextContains('Cancelled with a custom message.');
    $this->assertSession()->linkExists('Log in');
  }

  /**
   * Tests cancelling the login with auto register process from a subscriber.
   */
  public function testRegistrationCancelling(): void {
    // Get auto register from cas settings.
    $settings = $this->config('cas.settings');
    $settings->set('user_accounts.auto_register', TRUE)->save();
    // Add a CAS user.
    $this->createCasUser('Antoine Batiste', 'antoine@example.com', 'baTistE', []);
    // Place the login/logout block so that we can check if user is logged in.
    $this->placeBlock('system_menu_block:account');

    // Check the case when the subscriber didn't set a reason message.
    \Drupal::state()->set('cas_test.flag', 'cancel register without message');
    $this->casLogin('antoine@example.com', 'baTistE');

    $this->assertSession()->pageTextContains('You do not have access to log in to this website. Please contact a site administrator if you believe you should have access.');
    $this->assertSession()->linkExists('Log in');

    // Check the case when the subscriber has set a reason message.
    \Drupal::state()->set('cas_test.flag', 'cancel register with message');
    $this->casLogin('antoine@example.com', 'baTistE');

    $this->assertSession()->pageTextContains('Cancelled with a custom message.');
    $this->assertSession()->linkExists('Log in');
  }

}
