<?php

declare(strict_types = 1);

namespace Drupal\Tests\cas\Functional;

use Drupal\contact\Entity\ContactForm;
use Drupal\Tests\cas\Traits\CasTestTrait;

/**
 * Tests the post-login destination.
 *
 * @group cas
 */
class CasPostLoginDestinationTest extends CasBrowserTestBase {

  use CasTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'cas',
    'cas_mock_server',
    'cas_test',
    'contact',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create a local user.
    $account = $this->createUser(['access site-wide contact form'], 'beavis');
    // Create a CAS user.
    $this->createCasUser('beavis', 'beavis@example.com', 'needtp', [
      'firstname' => 'Beavis',
      'lastname' => 'El Gran Cornholio',
    ], $account);

    // Create a contact form to redirect to after a successful login.
    ContactForm::create(['id' => 'feedback'])->save();
  }

  /**
   * Tests post-login destination.
   *
   * @group legacy
   */
  public function testDestination(): void {
    $this->casLogin('beavis@example.com', 'needtp', [
      'destination' => 'contact',
    ]);
    $this->assertSession()->addressEquals('/contact');
  }

  /**
   * Tests the cached redirect to CAS login.
   *
   * This is regression test for a complex edge case described in #3277861.
   *
   * @see https://www.drupal.org/project/cas/issues/3277861
   */
  public function testCachedRedirect(): void {
    // Simulate a failed ticket validation.
    // @see \Drupal\cas_test\CasTestSubscriber::onPreValidate()
    \Drupal::state()->set('cas_test.enable_ticket_validation_failure', TRUE);

    // Login with CAS. Expect ticket validation failure.
    $this->casLogin('beavis@example.com', 'needtp', [
      'destination' => 'contact',
    ]);
    $this->assertSession()->pageTextContains('There was a problem validating your login, please contact a site administrator.');

    // Simulate an out-of-sync {cachetags} table. Is known that the redirect
    // response cache is tagged also with 'http_response' cache tag.
    // @see https://www.drupal.org/project/cas/issues/3277861
    \Drupal::database()->query("UPDATE {cachetags} SET invalidations = invalidations + 1 WHERE tag = 'http_response'")->execute();

    // Disable ticket validation failure simulation.
    // @see \Drupal\cas_test\CasTestSubscriber::onPreValidate()
    \Drupal::state()->delete('cas_test.enable_ticket_validation_failure');

    // Try again to access login with CAS.
    $this->casLogin('beavis@example.com', 'needtp', [
      'destination' => 'contact',
    ]);
    $this->assertSession()->addressEquals('/contact');
  }

}
