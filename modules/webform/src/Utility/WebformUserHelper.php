<?php

namespace Drupal\webform\Utility;

use Drupal\user\Entity\Role;
use Drupal\user\RoleInterface;

/**
 * Helper class for webform user/role based methods.
 */
class WebformUserHelper {

  /**
   * Retrieves the names of roles matching specified conditions.
   *
   * @param bool $members_only
   *   (optional) Set this to TRUE to exclude the 'anonymous' role. Defaults to
   *   FALSE.
   *
   * @return array
   *   An associative array with the role id as the key and the role name as
   *   value.
   */
  public static function getRoleNames(bool $members_only = FALSE): array {
    $roles = Role::loadMultiple();
    if ($members_only) {
      unset($roles[RoleInterface::ANONYMOUS_ID]);
    }
    return array_map(fn ($role) => $role->label(), $roles);
  }

}
