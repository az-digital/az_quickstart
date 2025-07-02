<?php

declare(strict_types=1);

namespace Drupal\migmag_rollbackable\Traits;

use Drupal\migmag_rollbackable\RollbackableInterface;

/**
 * Rollback data related operations.
 */
trait RollbackableDataTrait {

  /**
   * Saves rollback data for the given target if there are no previous records.
   *
   * @param string $target_object_id
   *   The ID of the target object (a configuration, entity etc).
   * @param mixed $data
   *   The data to save.
   * @param string $component
   *   The name (an internal unique identifier) of the component.
   * @param string $langcode
   *   The language code.
   */
  protected function saveTargetRollbackData(string $target_object_id, $data, string $component, string $langcode): void {
    // Subsequent migrations (with change tracking) shouldn't save previous
    // values.
    if ($this->targetHasRollbackData($target_object_id, $component, $langcode)) {
      return;
    }

    $this->getConnection()->insert(RollbackableInterface::ROLLBACK_DATA_TABLE)
      ->fields([
        RollbackableInterface::ROLLBACK_MIGRATION_PLUGIN_ID_COL => $this->migration->getPluginId(),
        RollbackableInterface::ROLLBACK_TARGET_ID_COL => $target_object_id,
        RollbackableInterface::ROLLBACK_COMPONENT_COL => $component,
        RollbackableInterface::ROLLBACK_LANGCODE_COL => $langcode,
        RollbackableInterface::ROLLBACK_DATA_COL => serialize($data),
      ])
      ->execute();
  }

  /**
   * Determines whether the target has any rollback data.
   *
   * @param string $target_object_id
   *   The ID of the target object (a configuration, entity etc).
   * @param string $component
   *   The name (an internal unique identifier) of the component.
   * @param string $langcode
   *   The language code.
   * @param bool $filter_for_current_migration
   *   Whether the rollback data check should be restricted to the current
   *   migration or not. Defaults to TRUE.
   *
   * @return bool
   *   TRUE if some rollback data exists, FALSE if not.
   */
  protected function targetHasRollbackData(string $target_object_id, string $component, string $langcode, $filter_for_current_migration = TRUE): bool {
    $count_query = $this->getConnection()->select(RollbackableInterface::ROLLBACK_DATA_TABLE)
      ->condition(RollbackableInterface::ROLLBACK_TARGET_ID_COL, $target_object_id)
      ->condition(RollbackableInterface::ROLLBACK_COMPONENT_COL, $component)
      ->condition(RollbackableInterface::ROLLBACK_LANGCODE_COL, $langcode);

    if ($filter_for_current_migration) {
      $count_query->condition(RollbackableInterface::ROLLBACK_MIGRATION_PLUGIN_ID_COL, $this->migration->getPluginId());
    }

    return (int) $count_query->countQuery()->execute()->fetchField() !== 0;
  }

  /**
   * Determines whether the target has any rollback data.
   *
   * @param string $target_object_id
   *   The ID of the target object (a configuration, entity etc).
   * @param string $langcode
   *   The language code.
   *
   * @return bool
   *   TRUE if some rollback data exists, FALSE if not.
   */
  protected function targetHasAnyRollbackData(string $target_object_id, string $langcode): bool {
    $count_query = $this->getConnection()->select(RollbackableInterface::ROLLBACK_DATA_TABLE)
      ->condition(RollbackableInterface::ROLLBACK_TARGET_ID_COL, $target_object_id)
      ->condition(RollbackableInterface::ROLLBACK_LANGCODE_COL, $langcode)
      ->countQuery();

    return (int) $count_query->execute()->fetchField() !== 0;
  }

  /**
   * Returns the rollback data of the given target.
   *
   * @param string $target_object_id
   *   The ID of the target object (a configuration, entity etc).
   * @param string $component
   *   The name (an internal unique identifier) of the component.
   * @param string $langcode
   *   The language code.
   *
   * @return mixed
   *   The rollback data stored by the current migration plugin (this may be
   *   even NULL or an empty array).
   */
  protected function getTargetRollbackData(string $target_object_id, string $component, string $langcode) {
    $statement = $this->getConnection()->select(RollbackableInterface::ROLLBACK_DATA_TABLE, 'rd')
      ->fields('rd', [
        RollbackableInterface::ROLLBACK_MIGRATION_PLUGIN_ID_COL,
        RollbackableInterface::ROLLBACK_DATA_COL,
      ])
      ->condition('rd.' . RollbackableInterface::ROLLBACK_MIGRATION_PLUGIN_ID_COL, $this->migration->getPluginId())
      ->condition('rd.' . RollbackableInterface::ROLLBACK_TARGET_ID_COL, $target_object_id)
      ->condition('rd.' . RollbackableInterface::ROLLBACK_COMPONENT_COL, $component)
      ->condition('rd.' . RollbackableInterface::ROLLBACK_LANGCODE_COL, $langcode)
      ->execute();

    $config_rollback_data = $statement->fetchAllAssoc(RollbackableInterface::ROLLBACK_MIGRATION_PLUGIN_ID_COL);
    array_walk($config_rollback_data, function (&$row) {
      $row = unserialize($row->{RollbackableInterface::ROLLBACK_DATA_COL});
    });

    return $config_rollback_data[$this->migration->getPluginId()] ?? NULL;
  }

  /**
   * Deletes the rollback data of the given target, for the current migration.
   *
   * @param string $target_object_id
   *   The ID of the target object (a configuration, entity etc).
   * @param string $component
   *   The name (an internal unique identifier) of the component.
   * @param string $langcode
   *   The language code.
   */
  protected function deleteTargetRollbackData(string $target_object_id, string $component, string $langcode): void {
    $this->getConnection()->delete(RollbackableInterface::ROLLBACK_DATA_TABLE)
      ->condition(RollbackableInterface::ROLLBACK_MIGRATION_PLUGIN_ID_COL, $this->migration->getPluginId())
      ->condition(RollbackableInterface::ROLLBACK_TARGET_ID_COL, $target_object_id)
      ->condition(RollbackableInterface::ROLLBACK_COMPONENT_COL, $component)
      ->condition(RollbackableInterface::ROLLBACK_LANGCODE_COL, $langcode)
      ->execute();
  }

  /**
   * Deletes every rollback data of the given target, for any migration.
   *
   * @param string $target_object_id
   *   The ID of the target object (a configuration, entity etc).
   * @param string $langcode
   *   The language code.
   */
  protected function deleteAllTargetRollbackData(string $target_object_id, string $langcode): void {
    $this->getConnection()->delete(RollbackableInterface::ROLLBACK_DATA_TABLE)
      ->condition(RollbackableInterface::ROLLBACK_TARGET_ID_COL, $target_object_id)
      ->condition(RollbackableInterface::ROLLBACK_LANGCODE_COL, $langcode)
      ->execute();
  }

}
