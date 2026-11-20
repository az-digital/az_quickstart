<?php

namespace Drupal\az_publication\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for Author entities.
 */
class AZAuthorViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    // Additional information for Views integration, such as table joins, can be
    // put here.
    return $data;
  }

}
