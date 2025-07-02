<?php

declare(strict_types=1);

namespace Drupal\migmag_rollbackable\Traits;

use Drupal\Component\Utility\Variable;
use Drupal\migmag_rollbackable\RollbackableInterface;

/**
 * Flagging routines for tracking targets created by rollbackable destinations.
 */
trait RollbackableFlagTrait {

  /**
   * Determines whether the target object was flagged as new.
   *
   * For checking whether the whole target object was flagged, call this method
   * with an empty $component argument.
   *
   * @param string $target_object_id
   *   The ID of the target object (a configuration, entity etc).
   * @param string $component
   *   The name (an internal unique identifier) of the component to be flagged
   *   as new.
   * @param string $langcode
   *   The language code.
   *
   * @return bool
   *   TRUE if the target was created by a rollbackable migration, FALSE
   *   otherwise.
   */
  protected function targetHasNewFlag(string $target_object_id, string $component, string $langcode): bool {
    $count_query = $this->getConnection()->select(RollbackableInterface::ROLLBACK_STATE_TABLE)
      ->condition(RollbackableInterface::ROLLBACK_TARGET_ID_COL, $target_object_id)
      ->condition(RollbackableInterface::ROLLBACK_COMPONENT_COL, $component)
      ->condition(RollbackableInterface::ROLLBACK_LANGCODE_COL, $langcode)
      ->countQuery();
    return (int) $count_query->execute()->fetchField() !== 0;
  }

  /**
   * Marks the target as initially new.
   *
   * If the target was created by a migration, we want to delete it if all of
   * its related migrations were rolled back. We store this info in a 'new' flag
   * table.
   *
   * @param string $target_object_id
   *   The ID of the target object (a configuration, entity etc).
   * @param string $component
   *   The name (an internal unique identifier) of the component to be flagged
   *   as new.
   * @param string $langcode
   *   The language code.
   *
   * @throws \LogicException
   *   If the target is already flagged as new.
   */
  protected function flagTargetAsNew(string $target_object_id, string $component, string $langcode): void {
    // Assert that we don't have the 'new' flag for this ID.
    if ($this->targetHasNewFlag($target_object_id, $component, $langcode)) {
      throw new \LogicException(sprintf(
        "The target object with the following IDs is already marked as being new: %s",
        Variable::export(array_filter([
          'target_object_id' => $target_object_id,
          'component' => $component,
          'langcode' => $langcode,
        ]))
      ));
    }
    $this->getConnection()->insert(RollbackableInterface::ROLLBACK_STATE_TABLE)
      ->fields([
        RollbackableInterface::ROLLBACK_TARGET_ID_COL => $target_object_id,
        RollbackableInterface::ROLLBACK_COMPONENT_COL => $component,
        RollbackableInterface::ROLLBACK_LANGCODE_COL => $langcode,
      ])
      ->execute();
  }

  /**
   * Removes the new flag from a target.
   *
   * @param string $target_object_id
   *   The ID of the target object (a configuration, entity etc).
   * @param string $component
   *   The name (an internal unique identifier) of the component which was
   *   flagged as new.
   * @param string $langcode
   *   The language code.
   */
  protected function removeNewTargetFlag(string $target_object_id, string $component, string $langcode): void {
    $this->getConnection()->delete(RollbackableInterface::ROLLBACK_STATE_TABLE)
      ->condition(RollbackableInterface::ROLLBACK_TARGET_ID_COL, $target_object_id)
      ->condition(RollbackableInterface::ROLLBACK_COMPONENT_COL, $component)
      ->condition(RollbackableInterface::ROLLBACK_LANGCODE_COL, $langcode)
      ->execute();
  }

  /**
   * Removes EVERY new flag from a target.
   *
   * Removes every single new flag (including component flags) with the given
   * target object ID and language code, regardless of the actual migration
   * plugin ID.
   *
   * @param string $target_object_id
   *   The ID of the target object (a configuration, entity etc).
   * @param string $langcode
   *   The language code.
   */
  protected function removeEveryNewTargetFlag(string $target_object_id, string $langcode): void {
    $this->getConnection()->delete(RollbackableInterface::ROLLBACK_STATE_TABLE)
      ->condition(RollbackableInterface::ROLLBACK_TARGET_ID_COL, $target_object_id)
      ->condition(RollbackableInterface::ROLLBACK_LANGCODE_COL, $langcode)
      ->execute();
  }

}
