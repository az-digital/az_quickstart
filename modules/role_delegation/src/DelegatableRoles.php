<?php

namespace Drupal\role_delegation;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\user\Entity\Role;
use Drupal\user\RoleInterface;

/**
 * Helper Service that loads all assignable roles for the given user.
 */
class DelegatableRoles implements DelegatableRolesInterface {

  use StringTranslationTrait;

  /**
   * A value used to indicate that nothing has been submitted.
   *
   * @var array
   */
  public static $emptyFieldValue = ['__role_delegation_empty_field_value__'];

  /**
   * {@inheritdoc}
   */
  public function getAssignableRoles(AccountInterface $account): array {
    $assignable_roles = [];
    foreach ($this->getAllRoles() as $role) {
      if ($account->hasPermission('assign all roles') || $account->hasPermission(sprintf('assign %s role', $role->id()))) {
        $assignable_roles[$role->id()] = $role->label();
      }
    }
    return $assignable_roles;
  }

  /**
   * {@inheritdoc}
   */
  public function getAllRoles(): array {
    $all_roles = Role::loadMultiple();
    unset($all_roles[RoleInterface::ANONYMOUS_ID], $all_roles[RoleInterface::AUTHENTICATED_ID]);
    return $all_roles;
  }

}
