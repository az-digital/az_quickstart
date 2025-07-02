<?php

namespace Drupal\role_delegation;

use Drupal\Core\Session\AccountInterface;

/**
 * Interface for the delegatable roles service.
 */
interface DelegatableRolesInterface {

  /**
   * Gets the roles a user is allowed to assing.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account for which you want to know which roles they can assign.
   *
   * @return array
   *   An array of roles with machine names as keys and labels as values.
   */
  public function getAssignableRoles(AccountInterface $account): array;

  /**
   * Gets all roles apart from anonymous and authenticated.
   *
   * @return \Drupal\user\RoleInterface[]
   *   An array of role objects.
   */
  public function getAllRoles(): array;

}
