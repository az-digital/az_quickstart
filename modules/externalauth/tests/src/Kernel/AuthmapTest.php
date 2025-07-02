<?php

namespace Drupal\Tests\externalauth\Kernel;

use Drupal\externalauth\Authmap;
use Drupal\KernelTests\KernelTestBase;
use Drupal\user\Entity\User;

/**
 * Tests the Authmap service.
 *
 * @group externalauth
 *
 * @see \Drupal\externalauth\Authmap
 */
class AuthmapTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'user', 'field', 'externalauth'];

  /**
   * The Authmap service.
   *
   * @var \Drupal\externalauth\Authmap
   */
  protected Authmap $authmap;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->authmap = \Drupal::service('externalauth.authmap');
    $this->installSchema('externalauth', ['authmap']);
    $this->installEntitySchema('user');
  }

  /**
   * Test Authmap service functionality.
   */
  public function testAuthmap() {
    // Create a new user.
    $values = [
      'uid' => 2,
      'name' => $this->randomMachineName(),
    ];
    $account = User::create($values);
    $account->save();

    // Set up fake external IDs for this user.
    $external_ids = [
      'provider1' => [
        'authname' => $this->randomMachineName(),
        'data' => $this->randomMachineName(),
      ],
      'provider2' => [
        'authname' => $this->randomMachineName(),
        'data' => $this->randomMachineName(),
      ],
    ];

    // Test save() method.
    foreach ($external_ids as $provider => $auth_data) {
      $this->authmap->save($account, $provider, $auth_data['authname'], $auth_data['data']);
    }

    // Test get() method.
    $count = \Drupal::database()->query('SELECT COUNT(*) FROM {authmap}')->fetchField();
    $this->assertEquals($count, 2, 'Number of authmap entries is correct.');
    $this->assertEquals($this->authmap->get($account->id(), 'provider1'), $external_ids['provider1']['authname'], 'Authname can be retrieved for user via get().');
    $this->assertEquals($this->authmap->get($account->id(), 'provider2'), $external_ids['provider2']['authname'], 'Authname can be retrieved for user via get().');

    // Test getAuthData() method.
    $provider1_authdata = $this->authmap->getAuthData($account->id(), 'provider1');
    $this->assertEquals($provider1_authdata['authname'], $external_ids['provider1']['authname'], 'Authname can be retrieved via getAuthData().');
    $this->assertEquals($provider1_authdata['data'], $external_ids['provider1']['data'], 'Auth data can be retrieved via getAuthData().');
    $provider2_authdata = $this->authmap->getAuthData($account->id(), 'provider2');
    $this->assertEquals($provider2_authdata['authname'], $external_ids['provider2']['authname'], 'Authname can be retrieved via getAuthData().');
    $this->assertEquals($provider2_authdata['data'], $external_ids['provider2']['data'], 'Auth data can be retrieved via getAuthData().');

    // Test getAll() method.
    $all_authnames = $this->authmap->getAll($account->id());
    $expected_authnames = [
      'provider1' => $external_ids['provider1']['authname'],
      'provider2' => $external_ids['provider2']['authname'],
    ];
    $this->assertEquals($all_authnames, $expected_authnames, 'All authnames for user can be retrieved.');

    // Test getUid() method.
    $uid = $this->authmap->getUid($external_ids['provider1']['authname'], 'provider1');
    $this->assertEquals($uid, $account->id(), 'User ID can be retrieved based on authname & provider.');

    // Test deleteProvider() method.
    $this->authmap->deleteProvider('provider1');
    $count = \Drupal::database()->query('SELECT COUNT(*) FROM {authmap}')->fetchField();
    $this->assertEquals($count, 1, 'Provider data deleted successfully.');

    // Test delete() method.
    $this->authmap->delete($account->id());
    $count = \Drupal::database()->query('SELECT COUNT(*) FROM {authmap}')->fetchField();
    $this->assertEquals($count, 0, 'User authnames deleted successfully.');
  }

}
