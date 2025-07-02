<?php

declare(strict_types=1);

namespace Drupal\migmag_rollbackable\Traits;

use Drupal\migrate\Row;

/**
 * Trait for rollbackable destination plugins.
 */
trait RollbackableTrait {

  use RollbackableConnectionTrait;
  use RollbackableDataTrait;
  use RollbackableFlagTrait;
  use RollbackableTargetTrait;

  /**
   * {@inheritdoc}
   */
  public function import(Row $row, array $old_destination_id_values = []) {
    $target_object_id = $this->getTargetObjectId($row);
    $component = $this->getTargetComponent($row);
    $langcode = $this->getTargetLangcode($row);

    // We need to know whether the config was new BEFORE we called the original
    // import method of the parent class. If something goes wrong during the
    // actual import (e.g. we run into an exception), we shouldn't flag the
    // config as new.
    if ($component) {
      $component_was_new = $this->targetObjectComponentIsNew($target_object_id, $component, $langcode);
    }
    $target_was_new = $this->targetObjectIsNew($target_object_id, $langcode);
    $previous_values = $this->getPreviousValues($row, $target_object_id, $component, $langcode);

    // Parent import returns TRUE when an actual save happened. But since an
    // ID map entry is saved only if the returned value is not 'TRUE', we need a
    // meaningful value.
    if ($parent_destination_ids = parent::import($row, $old_destination_id_values)) {
      // Only flag the target component as new component if it is not considered
      // as being new after the import.
      if (
        !empty($component_was_new) &&
        !$this->targetObjectComponentIsNew($target_object_id, $component, $langcode)
      ) {
        $this->flagTargetAsNew($target_object_id, $component, $langcode);
      }

      if ($target_was_new) {
        $this->flagTargetAsNew($target_object_id, '', $langcode);
      }

      // We will save rollback data if there is no preexisting record saved by
      // the actual migration, even if the new config is exactly the same as the
      // old, because we want to restore the original state if every  related
      // migration was rolled back.
      $this->saveTargetRollbackData($target_object_id, $previous_values, $component, $langcode);

      return $this->generateDestinationIds($parent_destination_ids, $target_object_id, $component, $langcode);
    }

    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function rollback(array $destination_identifier) {
    $target_object_id = $this->getTargetObjectId($destination_identifier);
    $component = $this->getTargetComponent($destination_identifier);
    $langcode = $this->getTargetLangcode($destination_identifier);

    // If the config does not exist now, and was therefore generated on the fly,
    // there is nothing to roll back. We have to clean up all the related data
    // we stored before.
    if ($this->targetObjectIsNew($target_object_id, $langcode)) {
      $this->cleanUpAllLeftovers($target_object_id, $langcode);
      return;
    }

    // Act on the current migration plugin's rollback data.
    // This does the PARTIAL rollback: key-value pairs within the config.
    // Partial means that we might have keys that weren't touched during the
    // migration – we neither modify those on rollback.
    $this->performRollback($target_object_id, $component, $langcode);
    $this->postRollbackCleanup($target_object_id, $langcode);
  }

  /**
   * Removes ANY rollback data as well as the 'new' flags.
   *
   * This method shouldn't be used unless the target object was deleted before
   * rolling back any of the related migrations.
   *
   * @param string $target_object_id
   *   The ID of the target object (a configuration, entity etc).
   * @param string $langcode
   *   The language code.
   */
  protected function cleanUpAllLeftovers(string $target_object_id, string $langcode) {
    if ($this->targetHasAnyRollbackData($target_object_id, $langcode)) {
      $this->deleteAllTargetRollbackData($target_object_id, $langcode);
    }

    $this->removeEveryNewTargetFlag($target_object_id, $langcode);
  }

  /**
   * Performs the data rollback for the current target.
   *
   * @param string $target_object_id
   *   The ID of the target object (a configuration, entity etc).
   * @param string $component
   *   The name (an internal unique identifier) of the component.
   * @param string $langcode
   *   The language code.
   */
  protected function performRollback(string $target_object_id, string $component, string $langcode) {
    if ($this->targetHasRollbackData($target_object_id, $component, $langcode)) {
      $this->doDataRollback(
        $this->getTargetRollbackData($target_object_id, $component, $langcode),
        $target_object_id,
        $component,
        $langcode
      );
      $this->deleteTargetRollbackData($target_object_id, $component, $langcode);
    }
  }

  /**
   * Performs post-rollback cleanup.
   *
   * Deletes the given config if it was created by a rollbackable migration and
   * removes the related 'new' flag.
   *
   * @param string $target_object_id
   *   The ID of the target object (a configuration, entity etc).
   * @param string $langcode
   *   The language code.
   */
  protected function postRollbackCleanup(string $target_object_id, string $langcode) {
    if (
      !$this->targetHasAnyRollbackData($target_object_id, $langcode) &&
      $this->targetHasNewFlag($target_object_id, '', $langcode)
    ) {
      $this->deleteTargetObject($target_object_id, $langcode);
      $this->removeEveryNewTargetFlag($target_object_id, $langcode);
    }
  }

}
