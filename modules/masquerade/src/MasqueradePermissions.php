<?php

namespace Drupal\masquerade;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\user\Entity\Role;
use Drupal\user\RoleInterface;

/**
 * Provides dynamic permissions of the masquerade module.
 */
class MasqueradePermissions {

  use StringTranslationTrait;

  /**
   * Returns an array of masquerade permissions.
   *
   * @todo Allow permissions for each role to masquerade as as subset of roles
   *   https://drupal.org/node/1171500
   *
   * @return array
   *   The permissions array.
   */
  public function permissions() {
    $permissions = [];

    // Anonymous was intentionally left out. Logout instead.
    $roles = $this->getUserRoles();
    foreach ($roles as $role) {
      $permissions['masquerade as ' . $role->id()] = [
        'title' => $this->t('Masquerade as @role', ['@role' => $role->label()]),
        'restrict access' => TRUE,
        'dependencies' => [
          $role->getConfigDependencyKey() => [$role->getConfigDependencyName()],
        ],
      ];
    }

    return $permissions;
  }

  /**
   * Returns role entities allowed to masquerade as.
   *
   * @return \Drupal\user\RoleInterface[]
   *   An associative array with the role id as the key and the role object as
   *   value.
   */
  protected function getUserRoles() {
    $roles = Role::loadMultiple();
    // Do not allow masquerade as anonymous user, use private browsing.
    unset($roles[RoleInterface::ANONYMOUS_ID]);
    return $roles;
  }

}
