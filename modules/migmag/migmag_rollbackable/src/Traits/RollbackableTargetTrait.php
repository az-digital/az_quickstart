<?php

declare(strict_types=1);

namespace Drupal\migmag_rollbackable\Traits;

use Drupal\Core\Config\Config;
use Drupal\migrate\Plugin\migrate\destination\Config as DefaultConfigDestination;
use Drupal\migrate\Row;

/**
 * Target related common operations for rollbackable migration destinations.
 */
trait RollbackableTargetTrait {

  /**
   * Determines the target ID from the given destination IDs or migration row.
   *
   * @param \Drupal\migrate\Row|array $row_or_destination_ids
   *   A migration row or an array of the destination IDs.
   *
   * @return string
   *   The ID of the target (object).
   */
  protected function getTargetObjectId($row_or_destination_ids): string {
    if ($row_or_destination_ids instanceof Row) {
      $destination_ids = [];
      foreach (array_keys($this->getIds()) as $id) {
        $destination_ids[] = $row_or_destination_ids->getDestinationProperty($id);
      }
    }

    return implode('.', array_slice($destination_ids ?? $row_or_destination_ids, 0, 3));
  }

  /**
   * Determines the component from the given destination IDs or migration row.
   *
   * @param \Drupal\migrate\Row|array $row_or_destination_ids
   *   A migration row or an array of the destination IDs.
   *
   * @return string
   *   The component of the target (object).
   */
  protected function getTargetComponent($row_or_destination_ids): string {
    return '';
  }

  /**
   * Determines the language code of the actual target.
   *
   * @param \Drupal\migrate\Row|array $row_or_destination_ids
   *   A migration row or an array of the destination IDs.
   *
   * @return string
   *   The language code. Optional, defaults to ''.
   */
  protected function getTargetLangcode($row_or_destination_ids): string {
    return '';
  }

  /**
   * Returns the target object of the rollbackable destination plugin.
   *
   * @param string $target_object_id
   *   The ID of the target object (a configuration, entity etc).
   * @param string $langcode
   *   The language code.
   *
   * @return object|null
   *   The target object (a config entity or module settings). NULL is allowed
   *   because not every destination plugin operates on objects.
   *
   * @see \Drupal\shortcut\Plugin\migrate\destination\ShortcutSetUsers
   */
  protected function getTargetObject(string $target_object_id, string $langcode) {
    return $this->configFactory->getEditable($target_object_id);
  }

  /**
   * Returns the destination IDs to save.
   *
   * @param true|array $parent_destination_ids
   *   The destination IDs returned by the original destination plugin.
   * @param string $target_object_id
   *   The ID of the target object.
   * @param string $component
   *   The ID of the target component. Might be an empty string.
   * @param string $langcode
   *   The language code of the target object. Might be an empty string.
   *
   * @return array
   *   The destination IDs to save.
   */
  protected function generateDestinationIds($parent_destination_ids, string $target_object_id, string $component, string $langcode): array {
    return $parent_destination_ids;
  }

  /**
   * Deletes the target object of the rollbackable destination plugin.
   *
   * @param string $target_object_id
   *   The ID of the target object (a configuration, entity etc).
   * @param string $langcode
   *   The language code.
   */
  protected function deleteTargetObject(string $target_object_id, string $langcode): void {
    $this->getTargetObject($target_object_id, $langcode)->delete();
  }

  /**
   * Determines whether the target is new.
   *
   * @param string $target_object_id
   *   The ID of the target object (a configuration, entity etc).
   * @param string $langcode
   *   The language code.
   *
   * @return bool
   *   TRUE when the target is evaluated as being new, FALSE otherwise.
   */
  protected function targetObjectIsNew(string $target_object_id, string $langcode): bool {
    return $this->getTargetObject($target_object_id, $langcode)->isNew();
  }

  /**
   * Determines whether the target component is new.
   *
   * @param string $target_object_id
   *   The ID of the target object (a configuration, entity etc).
   * @param string $component
   *   The name (an internal unique identifier) of the component.
   * @param string $langcode
   *   The language code.
   *
   * @return bool
   *   TRUE when the target component is evaluated as being new, FALSE
   *   otherwise.
   */
  protected function targetObjectComponentIsNew(string $target_object_id, string $component, string $langcode): bool {
    $target_object = $this->getTargetObject($target_object_id, $langcode);
    return !array_key_exists($component, $target_object->get());
  }

  /**
   * Returns the previous value of the target before the actual migration.
   *
   * @param \Drupal\migrate\Row $row
   *   The processed migration row.
   * @param string $target_object_id
   *   The ID of the target (object).
   * @param string $component
   *   The component.
   * @param string $langcode
   *   The language code.
   *
   * @return mixed
   *   The previous value of the target before the actual migration.
   */
  protected function getPreviousValues(Row $row, string $target_object_id, string $component, string $langcode) {
    if ($this->targetObjectIsNew($target_object_id, $langcode)) {
      return NULL;
    }
    $target_object = $this->getTargetObject($target_object_id, $langcode);

    // If we have a component, we have to return only the component's
    // previous value.
    if ($component) {
      return $target_object instanceof Config
        ? $target_object->getOriginal($component, FALSE)
        : $target_object->get($component);
    }

    // If the current configuration is a pre-existing config, we only have to
    // restore the original values if the migration is rolled back. However, if
    // the config was created by a migration, then we have to delete it if we
    // cannot find any other rollbackable data that was stored by other
    // migrations.
    $rollback_data = [];
    foreach ($row->getRawDestination() as $key => $value) {
      // Destination which are instances of "config" have a special
      // configuration for storing null values.
      if (
        $this instanceof DefaultConfigDestination &&
        is_null($value) &&
        empty($this->configuration['store null'])
      ) {
        continue;
      }

      if ($mapped_key = $this->getMappedKey($key)) {
        $top_level_key = explode(Row::PROPERTY_SEPARATOR, $this->getMappedKey($key))[0];

        $rollback_data[$top_level_key] = $target_object instanceof Config
          ? $target_object->getOriginal($top_level_key, FALSE)
          : $target_object->get($top_level_key);
      }
    }

    return $rollback_data;
  }

  /**
   * Returns a key of the target object based on the migration destination key.
   *
   * @param string $destination_key
   *   The destination property of the actual migration.
   *
   * @return string
   *   The corresponding configuration key of the target object.
   */
  protected function getMappedKey(string $destination_key): ?string {
    return $destination_key;
  }

  /**
   * Restores the target's previous status and persists the change.
   *
   * @param mixed $rollback_data
   *   The data of the given target (or target component) before this migration
   *   was executed.
   * @param string $target_object_id
   *   The ID of the target (object).
   * @param string $component
   *   The component.
   * @param string $langcode
   *   The language code.
   */
  protected function doDataRollback($rollback_data, string $target_object_id, string $component, string $langcode): void {
    // If there are previous values (aka "rollback data"), restore those. If
    // there aren't, the component may be removed.
    $target_object = $this->getTargetObject($target_object_id, $langcode);

    if ($component) {
      $rollback_data = [
        $component => $rollback_data,
      ];
    }

    if (is_array($rollback_data)) {
      foreach ($rollback_data as $config_key => $original_value) {
        if ($original_value === NULL) {
          $target_object->clear($config_key);
          continue;
        }

        $target_object->set($config_key, $original_value);
      }

      $target_object->save();
    }
  }

}
