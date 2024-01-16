<?php

declare(strict_types=1);

namespace Drupal\az_publication\Plugin\EntityReferenceSelection;

use Drupal\Core\Entity\Plugin\EntityReferenceSelection\DefaultSelection;

/**
 * Provides specific access control for the az_publication_type entity type.
 *
 * @EntityReferenceSelection(
 *   id = "default:az_publication_type",
 *   label = @Translation("AZ Publication Type selection"),
 *   entity_types = {"az_publication_type"},
 *   group = "default",
 *   weight = 1
 * )
 */
class AZPublicationTypeSelection extends DefaultSelection {

  /**
   * {@inheritdoc}
   */
  protected function buildEntityQuery($match = NULL, $match_operator = 'CONTAINS') {
    $query = parent::buildEntityQuery($match, $match_operator);

    // Add condition for status.
    $query->condition('status', TRUE);

    return $query;
  }

}
