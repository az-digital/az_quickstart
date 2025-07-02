<?php

declare(strict_types=1);

namespace Drupal\migmag_rollbackable\Plugin\migrate\destination;

/**
 * Rollbackable entity view display component destination.
 *
 * Provides a rollbackable entity view display destination plugin per component
 * (field).
 *
 * @see \Drupal\migrate\Plugin\migrate\destination\PerComponentEntityDisplay
 *
 * @MigrateDestination(
 *   id = "migmag_rollbackable_component_entity_display"
 * )
 */
final class RollbackablePerComponentEntityDisplay extends RollbackableComponentEntityDisplayBase {

  /**
   * {@inheritdoc}
   */
  const MODE_NAME = 'view_mode';

  /**
   * {@inheritdoc}
   */
  protected function getEntity($entity_type, $bundle, $form_mode) {
    return $this->entityDisplayRepository->getViewDisplay($entity_type, $bundle, $form_mode);
  }

}
