<?php

namespace Drupal\az_core;

/**
 * Interface for content field updater service.
 */
interface AZContentFieldUpdaterInterface {

  /**
   * Process field values with a field processor and field conditions.
   *
   * @param string $entity_type_id
   *   The entity type ID (e.g., 'node', 'paragraph').
   * @param string $bundle
   *   The bundle name (e.g., 'az_text', 'az_person').
   * @param string $field_name
   *   The name of the field to update.
   * @param callable $processor
   *   Function to process field values. For simple fields, should accept a
   *   string and return a string. For complex fields, may accept the entire
   *   field item and return processed values.
   * @param array &$sandbox
   *   The batch sandbox array. Must contain:
   *   - ids: Array of entity IDs to process
   *   - max: Total count of entities to process
   *   - progress: Current progress counter
   *   - updated_count: Number of entities updated
   *   - skipped_count: Number of entities skipped.
   * @param array $options
   *   Additional options:
   *   - create_revisions: (bool) Whether to create new revisions. When TRUE,
   *     will also make them the default revision and update parent entity
   *     revisions for paragraphs. Defaults to FALSE.
   *   - prefix: (string|null) Optional prefix for revision log messages. Useful
   *     for including the update hook name.
   *   - suffix: (string|null) Optional suffix to append to both revision log
   *     messages and logger messages.
   *   - batch_size: (int) Number of entities per batch. Defaults to 20.
   *   - value_key: (string) The property containing the value to process within
   *     the field item. Defaults to 'value'.
   *   - format_key: (string) The property containing the text format within the
   *     field item. Defaults to 'format'.
   *   - format_required: (bool) Whether to check the format property against
   *     allowed_formats before processing. Defaults to TRUE.
   *   - allowed_formats: (array) Text formats that are allowed to be processed.
   *     Only used if format_required is TRUE. Defaults to ['az_standard',
   *     'full_html'].
   *
   * @return string|null
   *   A translated message when complete, NULL otherwise.
   */
  public function processFieldUpdates(
    string $entity_type_id,
    string $bundle,
    string $field_name,
    callable $processor,
    array &$sandbox,
    array $options = [],
  ): ?string;

}
