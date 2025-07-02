<?php

namespace Drupal\webform\EntityListBuilder;

/**
 * Trait class for Webform Entity List Builder sorting by label.
 */
trait WebformEntityListBuilderSortLabelTrait {

  /**
   * Loads entity IDs using a pager sorted by the entity label (instead of id).
   *
   * @return array
   *   An array of entity IDs.
   *
   * @see \Drupal\Core\Entity\EntityListBuilder::getEntityIds
   */
  protected function getEntityIds() {
    $query = $this->getStorage()->getQuery()->accessCheck(TRUE)
      ->sort($this->entityType->getKey('label'));

    // Only add the pager if a limit is specified.
    if ($this->limit) {
      $query->pager($this->limit);
    }
    return $query->execute();
  }

}
