<?php

namespace Drupal\Tests\externalauth\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Tests the 'externalauth.externalauth' service.
 *
 * @group externalauth
 */
class ExternalAuthTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'externalauth',
    'system',
    'user',
  ];

  /**
   * Tests the local Drupal username on registration.
   */
  public function testRegisterDrupalUsername() {
    $this->installSchema('system', ['sequences']);
    $this->installEntitySchema('user');
    $this->installSchema('user', ['users_data']);
    $this->installSchema('externalauth', ['authmap']);

    $externalauth = $this->container->get('externalauth.externalauth');
    $authmap = $this->container->get('externalauth.authmap');
    $provider = 'arbitrary_provider';

    // Register a new account.
    $externalauth->register('external_name', $provider);

    // Check that the registered account username is prefixed with the provider.
    $account = user_load_by_name("{$provider}_external_name");
    $this->assertNotFalse($account);
    $this->assertSame('external_name', $authmap->get($account->id(), $provider));

    $account->delete();

    // Re-register the account but enforce a Drupal username.
    $externalauth->register('external_name', $provider, [
      'name' => 'enforced_name',
    ]);

    // Check that the registered account username match the enforced name.
    $account = user_load_by_name('enforced_name');
    $this->assertNotFalse($account);
    $this->assertSame('external_name', $authmap->get($account->id(), $provider));
  }

}
