<?php

declare(strict_types=1);

namespace Drupal\Tests\linkit\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;

/**
 * Defines an abstract test base for entity kernel tests.
 */
abstract class LinkitKernelTestBase extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'system',
    'user',
    'filter',
    'text',
    'linkit',
    'linkit_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('user');
    $this->installConfig(['filter']);
    if ($this->container->get('entity_type.manager')->hasDefinition('path_alias')) {
      $this->installEntitySchema('path_alias');
    }
  }

  /**
   * Creates a user.
   *
   * @param array $values
   *   (optional) The values used to create the entity.
   * @param array $permissions
   *   (optional) Array of permission names to assign to user.
   *
   * @return \Drupal\user\Entity\User
   *   The created user entity.
   */
  protected function createUser(array $values = [], array $permissions = []) {
    if ($permissions) {
      // Create a new role and apply permissions to it.
      $role = Role::create([
        'id' => mb_strtolower($this->randomMachineName(8)),
        'label' => $this->randomMachineName(8),
      ]);
      $role->save();
      user_role_grant_permissions($role->id(), $permissions);
      $values['roles'][] = $role->id();
    }

    $account = User::create($values + [
      'name' => $this->randomMachineName(),
      'status' => 1,
    ]);
    $account->enforceIsNew();
    $account->save();
    return $account;
  }

}
