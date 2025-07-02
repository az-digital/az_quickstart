<?php

declare(strict_types=1);

namespace Drupal\migmag_rollbackable\Plugin\migrate\destination;

use Drupal\migmag_rollbackable\Traits\RollbackableTrait;
use Drupal\migrate\Plugin\migrate\destination\ComponentEntityDisplayBase;
use Drupal\migrate\Row;

/**
 * Base class for rollbackable per-component entity display destination plugins.
 *
 * Provides a rollbackable base class for entity display destination plugins per
 * component (field).
 *
 * @internal
 *
 * @see \Drupal\migrate\Plugin\migrate\destination\ComponentEntityDisplay
 */
abstract class RollbackableComponentEntityDisplayBase extends ComponentEntityDisplayBase {

  use RollbackableTrait;

  /**
   * {@inheritdoc}
   */
  protected $supportsRollback = TRUE;

  /**
   * {@inheritdoc}
   */
  protected function getTargetObjectId($row_or_destination_ids): string {
    if ($row_or_destination_ids instanceof Row) {
      $destination_ids = [];
      foreach (array_keys($this->getIds()) as $id) {
        $destination_ids[] = $row_or_destination_ids->getDestinationProperty($id);
      }
    }

    [
      $entity_type,
      $bundle,
      $mode,
    ] = array_values($destination_ids ?? $row_or_destination_ids);

    return $this->getEntity($entity_type, $bundle, $mode)->getConfigDependencyName();
  }

  /**
   * {@inheritdoc}
   */
  protected function getTargetComponent($row_or_destination_ids): string {
    if ($row_or_destination_ids instanceof Row) {
      return $row_or_destination_ids->getDestinationProperty('field_name');
    }

    return $row_or_destination_ids['field_name'];
  }

  /**
   * {@inheritdoc}
   *
   * @return \Drupal\Core\Entity\Display\EntityDisplayInterface
   *   An entity display object.
   */
  protected function getTargetObject(string $target_object_id, string $langcode = '') {
    [
      $entity_type,
      $bundle,
      $mode,
    ] = array_slice(
      explode('.', $target_object_id),
      2
    );
    return $this->getEntity($entity_type, $bundle, $mode);
  }

  /**
   * {@inheritdoc}
   */
  protected function targetObjectComponentIsNew(string $target_object_id, string $component, string $langcode = ''): bool {
    return !array_key_exists(
      $component,
      $this->getTargetObject($target_object_id, $langcode)->getComponents()
    );
  }

  /**
   * {@inheritdoc}
   *
   * @throws \LogicException
   *   When the method was called with an empty $component.
   */
  protected function doDataRollback($rollback_data, string $target_object_id, string $component, string $langcode): void {
    if (empty($component)) {
      throw new \LogicException('The component (field name) of a formatter or widget settings migration cannot be empty');
    }

    // If there are previous values (aka "rollback data"), restore those. If
    // there aren't, the component may be removed.
    $target_object = $this->getTargetObject($target_object_id, $langcode);

    // If there are previous values (aka "rollback data") for the current
    // migration's (e.g. d7_field_instance:node:blog) actual component's
    // (e.g. body field) destination, restore those.
    if ($rollback_data !== NULL) {
      $target_object->setComponent($component, $rollback_data);
    }

    // Remove rollback data.
    $this->deleteTargetRollbackData($target_object_id, $component, $langcode);

    // If no other migration stored rollback data for the current component,
    // The component should be hidden only if no other migrations has stored
    // rollback data for the same target's same component.
    if (
      !$this->targetHasRollbackData($target_object_id, $component, $langcode, FALSE) &&
      $this->targetHasNewFlag($target_object_id, $component, $langcode)
    ) {
      $target_object->removeComponent($component);
    }

    $target_object->save();
  }

  /**
   * {@inheritdoc}
   *
   * @throws \LogicException
   *   When the method was called with an empty $component.
   */
  protected function getPreviousValues(Row $row, string $target_object_id, string $component = '', string $langcode = '') {
    if (empty($component)) {
      throw new \LogicException('The component (field name) of a formatter or widget settings migration cannot be empty');
    }
    if ($this->targetObjectIsNew($target_object_id, $langcode)) {
      return NULL;
    }

    return $this->getTargetObject($target_object_id, $langcode)->getComponent($component);
  }

}
