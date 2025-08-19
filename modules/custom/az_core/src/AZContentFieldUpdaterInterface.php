<?php

namespace Drupal\az_core;

/**
 * Interface for content field updater service.
 */
interface AZContentFieldUpdaterInterface {

  /**
   * Process field values with the given processor function.
   *
   * @param string $entity_type_id
   *   The entity type ID (e.g., 'node', 'paragraph').
   * @param string $field_name
   *   The name of the field to update.
   * @param callable $processor
   *   Function to process the field value. Should accept a string and return
   *   a string. If the returned value is different from the input, the field
   *   will be updated.
   * @param array &$sandbox
   *   The batch sandbox array. Must contain:
   *   - ids: Array of entity IDs to process
   *   - max: Total count of entities to process
   *   - progress: Current progress counter
   *   - updated_count: Number of entities updated.
   * @param array $options
   *   Additional options:
   *   - create_revisions: (bool) Whether to create new revisions. Defaults to
   *       FALSE.
   *   - description: (string|null) Optional description of what this update
   *       does. Used in revision messages and logs.
   *   - batch_size: (int) Number of entities to process per batch. Defaults to
   *       20.
   *
   * @return string|null
   *   A translated message when complete, NULL otherwise.
   */
  public function processFieldUpdates(
    string $entity_type_id,
    string $field_name,
    callable $processor,
    array &$sandbox,
    array $options = [],
  ): ?string;

}
