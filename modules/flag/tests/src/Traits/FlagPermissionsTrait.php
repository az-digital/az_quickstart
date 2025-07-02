<?php

declare(strict_types=1);

namespace Drupal\Tests\flag\Traits;

use Drupal\flag\FlagInterface;
use Drupal\user\Entity\Role;
use Drupal\user\RoleInterface;

/**
 * Trait for programmatically creating Flags.
 */
trait FlagPermissionsTrait {

  /**
   * Grants flag and unflag permission to the given flag.
   *
   * @param \Drupal\flag\FlagInterface $flag
   *   The flag on which to grant permissions.
   * @param array|string $role_id
   *   (optional) The ID of the role to grant permissions. If omitted, the
   *   authenticated role is assumed.
   * @param bool $can_flag
   *   (optional) TRUE to grant the role flagging permission, FALSE to not grant
   *   flagging permission to the role. If omitted, TRUE is assumed.
   * @param bool $can_unflag
   *   Optional TRUE to grant the role unflagging permission, FALSE to not grant
   *   unflagging permission to the role. If omitted, TRUE is assumed.
   */
  protected function grantFlagPermissions(
    FlagInterface $flag,
    $role_id = RoleInterface::AUTHENTICATED_ID,
    $can_flag = TRUE,
    $can_unflag = TRUE,
  ) {

    // Grant the flag permissions to the authenticated role, so that both
    // users have the same roles and share the render cache.
    $role = Role::load($role_id);
    if ($can_flag) {
      $role->grantPermission('flag ' . $flag->id());
    }

    if ($can_unflag) {
      $role->grantPermission('unflag ' . $flag->id());
    }

    $role->save();
  }

}
