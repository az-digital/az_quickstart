<?php

namespace Drupal\access_unpublished;

use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Utility class.
 */
class AccessUnpublished {

  /**
   * Check if the entity type can be used with access unpublished.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entityType
   *   The entity type.
   *
   * @return bool
   *   TRUE if it can be used.
   */
  public static function applicableEntityType(EntityTypeInterface $entityType) {
    if (
      in_array(EntityPublishedInterface::class, class_implements($entityType->getClass())) &&
      $entityType->hasLinkTemplate('canonical')
    ) {
      return TRUE;
    }
    return FALSE;
  }

}
