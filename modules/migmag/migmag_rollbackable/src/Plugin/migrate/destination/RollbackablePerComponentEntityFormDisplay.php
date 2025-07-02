<?php

declare(strict_types=1);

namespace Drupal\migmag_rollbackable\Plugin\migrate\destination;

/**
 * Rollbackable entity form display component destination.
 *
 * Provides a rollbackable entity form display destination plugin per component
 * (field).
 *
 * @see \Drupal\migrate\Plugin\migrate\destination\PerComponentEntityFormDisplay
 *
 * @MigrateDestination(
 *   id = "migmag_rollbackable_component_entity_form_display"
 * )
 */
final class RollbackablePerComponentEntityFormDisplay extends RollbackableComponentEntityDisplayBase {

  /**
   * {@inheritdoc}
   */
  const MODE_NAME = 'form_mode';

  /**
   * {@inheritdoc}
   */
  protected function getEntity($entity_type, $bundle, $form_mode) {
    return $this->entityDisplayRepository->getFormDisplay($entity_type, $bundle, $form_mode);
  }

}
