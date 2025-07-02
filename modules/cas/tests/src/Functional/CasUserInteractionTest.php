<?php

namespace Drupal\Tests\cas\Functional;

use Drupal\Tests\cas\Traits\CasTestTrait;

/**
 * Tests inserting user interaction into the flow.
 *
 * @group cas
 */
class CasUserInteractionTest extends CasBrowserTestBase {

  use CasTestTrait {
    casLogin as traitCasLogin;
  }

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'block',
    'cas',
    'cas_mock_server',
    'cas_user_interaction_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create a local user.
    $account = $this->createUser([], 'beavis');
    // Create a CAS user.
    $this->createCasUser('beavis', 'beavis@example.com', 'needtp', [
      'firstname' => 'Beavis',
      'lastname' => 'El Gran Cornholio',
    ], $account);

    // Place the login/logout block so that we can check if user is logged in.
    $this->placeBlock('system_menu_block:account');
  }

  /**
   * Tests user interaction.
   */
  public function testUserInteraction() {
    // The 'Legal Notice' has not been changed.
    \Drupal::state()->set('cas_user_interaction_test.changed', FALSE);
    $this->casLogin();
    $this->assertUserLoggedIn();
    $this->drupalLogout();

    // The 'Legal Notice' has been changed. Login again with CAS.
    \Drupal::state()->set('cas_user_interaction_test.changed', TRUE);
    $this->casLogin();
    $this->assertUserNotLoggedIn();
    $this->assertSession()->pageTextContains("I agree with the 'Legal Notice'");

    // The user doesn't check the "I agree..." checkbox. Form doesn't validate.
    $page = $this->getSession()->getPage();
    $page->pressButton('I agree');
    $this->assertSession()->pageTextContains("I agree with the 'Legal Notice' field is required.");
    $this->assertUserNotLoggedIn();

    // The user checks the "I agree..." checkbox.
    $page->checkField("I agree with the 'Legal Notice'");
    $page->pressButton('I agree');
    $this->assertUserLoggedIn();
  }

  /**
   * Logs-in the user to the CAS mock server.
   */
  protected function casLogin() {
    $this->traitCasLogin('beavis@example.com', 'needtp');
  }

}
