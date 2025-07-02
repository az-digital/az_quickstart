<?php

namespace Drupal\role_delegation\Plugin\EntityReferenceSelection;

use Drupal\Core\Entity\Plugin\EntityReferenceSelection\DefaultSelection;
use Drupal\role_delegation\DelegatableRoles;

/**
 * Entity reference implementation for the role_change field.
 *
 * @EntityReferenceSelection(
 *   id = "role_change:user_role",
 *   label = @Translation("Role change"),
 *   entity_types = {"user_role"},
 *   group = "role_change",
 *   weight = 0,
 * )
 */
class RoleChangeSelection extends DefaultSelection {

  /**
   * {@inheritdoc}
   */
  public function validateReferenceableEntities(array $ids): array {
    $result = parent::validateReferenceableEntities($ids);

    if ($ids) {
      $result = array_merge($result, DelegatableRoles::$emptyFieldValue);
    }

    return $result;
  }

}
