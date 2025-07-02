<?php

/**
 * @file
 * Post-update functions for Smart Date module.
 */

use Drupal\Core\Config\FileStorage;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Entity\Exception\FieldStorageDefinitionUpdateForbiddenException;
use Drupal\Core\Entity\Schema\DynamicallyFieldableEntityStorageSchemaInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\smart_date\Plugin\Field\FieldType\SmartDateItem;

/**
 * Clear caches to ensure schema changes are read.
 */
function smart_date_post_update_translatable_separator() {
  // Empty post-update hook to cause a cache rebuild.
}

/**
 * Migrate smartdate_default field formatter settings to smartdate_custom.
 */
function smart_date_post_update_translatable_config() {

  // Loop through all configured entity view displays, and compile information
  // about the smartdate_default field settings.
  $displays = EntityViewDisplay::loadMultiple();
  foreach ($displays as $display) {
    if ($display instanceof EntityViewDisplay) {
      $components = $display->getComponents();
      foreach ($components as $fieldName => $component) {
        if (isset($component['type'])
          && $component['type'] === 'smartdate_default'
          && isset($component['settings'])
        ) {
          // Keep the settings the same but change it to the custom display.
          $component['type'] = 'smartdate_custom';
          $display->setComponent($fieldName, $component);
          $display->save();
        }
      }
    }
  }
  // Now ensure defaults are imported.
  // If there are already smart date format entities then nothing is needed.
  $storage = \Drupal::entityTypeManager()->getStorage('smart_date_format');
  $existing = $storage->loadMultiple();
  if ($existing) {
    return;
  }

  // Obtain configuration from yaml files.
  $config_path = \Drupal::service('extension.list.module')->getPath('smart_date') . '/config/install/';
  $source      = new FileStorage($config_path);

  // Load the provided default entities.
  $storage->create($source->read('smart_date.smart_date_format.compact'))
    ->save();
  $storage->create($source->read('smart_date.smart_date_format.date_only'))
    ->save();
  $storage->create($source->read('smart_date.smart_date_format.default'))
    ->save();
  $storage->create($source->read('smart_date.smart_date_format.time_only'))
    ->save();
}

/**
 * Increase the storage size to resolve the 2038 problem.
 */
function smart_date_post_update_increase_column_storage(&$sandbox): void {
  if (!isset($sandbox['items'])) {
    $items = _smart_date_update_get_smart_date_fields();
    $sandbox['items'] = $items;
    $sandbox['current'] = 0;
    $sandbox['num_processed'] = 0;
    $sandbox['max'] = count($items);
  }

  [$entity_type_id, $field_name] = $sandbox['items'][$sandbox['current']];
  if ($entity_type_id && $field_name) {
    $column_names = ['value', 'end_value'];
    $size = 'big';
    $success_message = "Successfully updated entity '@entity_type_id' field '@field_name' to remove year 2038 limitation.";
    _smart_date_update_process_smart_date_field($entity_type_id, $field_name, $column_names, $size, $success_message);
  }
  $sandbox['current']++;

  $sandbox['#finished'] = empty($sandbox['max']) ? 1 : ($sandbox['current'] / $sandbox['max']);
}

/**
 * Increase the storage size to resolve the 2038 problem on revisions.
 */
function smart_date_post_update_increase_column_revisions(&$sandbox): void {
  if (!isset($sandbox['items'])) {
    $items = _smart_date_update_get_smart_date_fields();
    $sandbox['items'] = $items;
    $sandbox['current'] = 0;
    $sandbox['num_processed'] = 0;
    $sandbox['max'] = count($items);
  }
  // If there are no items to process, then quit.
  if ($sandbox['max'] == 0) {
    return;
  }

  [$entity_type_id, $field_name] = $sandbox['items'][$sandbox['current']];
  if ($entity_type_id && $field_name) {
    $column_names = ['value', 'end_value'];
    $size = 'big';
    $success_message = "Successfully updated entity revisions '@entity_type_id' field '@field_name' to remove year 2038 limitation.";
    _smart_date_update_process_smart_date_field($entity_type_id, $field_name, $column_names, $size, $success_message);
  }
  $sandbox['current']++;

  $sandbox['#finished'] = empty($sandbox['max']) ? 1 : ($sandbox['current'] / $sandbox['max']);
}

/**
 * Increase the storage size to resolve the 2038 problem on revisions.
 */
function smart_date_post_update_refresh_value_schema(&$sandbox): void {
  _smart_date_update_schema(['value', 'end_value'], 'big');
}

/**
 * Increase the storage size for the duration column.
 */
function smart_date_post_update_resize_duration(&$sandbox): void {
  if (!isset($sandbox['items'])) {
    $items = _smart_date_update_get_smart_date_fields();
    $sandbox['items'] = $items;
    $sandbox['current'] = 0;
    $sandbox['num_processed'] = 0;
    $sandbox['max'] = count($items);
  }
  // If there are no items to process, then quit.
  if ($sandbox['max'] == 0) {
    return;
  }

  [$entity_type_id, $field_name] = $sandbox['items'][$sandbox['current']];
  if ($entity_type_id && $field_name) {
    $column_names = ['duration'];
    $size = 'normal';
    $success_message = "Successfully updated '@entity_type_id' field '@field_name' to increase duration storage.";
    _smart_date_update_process_smart_date_field($entity_type_id, $field_name, $column_names, $size, $success_message);
  }
  $sandbox['current']++;

  $sandbox['#finished'] = empty($sandbox['max']) ? 1 : ($sandbox['current'] / $sandbox['max']);
}

/**
 * Increase the schema size for durations.
 */
function smart_date_post_update_resize_duration_schema(&$sandbox): void {
  _smart_date_update_schema(['duration'], 'normal');
}

/**
 * Gets a list fields that use the SmartDateItem class.
 *
 * @return string[]
 *   An array with two elements, an entity type ID and a field name.
 */
function _smart_date_update_get_smart_date_fields(): array {
  $items = [];

  // Get all the field definitions.
  $field_definitions = \Drupal::service('plugin.manager.field.field_type')->getDefinitions();

  // Get all the field types that use the SmartDateItem class.
  $field_types = array_keys(array_filter($field_definitions, function ($definition) {
    return is_a($definition['class'], SmartDateItem::class, TRUE);
  }));

  /** @var \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager */
  $entity_field_manager = \Drupal::service('entity_field.manager');

  // Build a list of all the Smart Date fields.
  foreach ($field_types as $field_type) {
    $entity_field_map = $entity_field_manager->getFieldMapByFieldType($field_type);
    foreach ($entity_field_map as $entity_type_id => $fields) {
      $storage = \Drupal::entityTypeManager()->getStorage($entity_type_id);
      if ($storage instanceof SqlContentEntityStorage) {
        foreach (array_keys($fields) as $field_name) {
          $items[] = [$entity_type_id, $field_name];
        }
        $storage->resetCache();
      }
    }
  }

  return $items;
}

/**
 * Update a Smart Date field to remove Y2038 limitation.
 *
 * @param string $entity_type_id
 *   The entity type ID.
 * @param string $field_name
 *   The name of the field that needs to be updated.
 * @param array $columns_to_resize
 *   The columns that should be resized.
 * @param string $size
 *   The new size to set for the columns.
 * @param string $success_message
 *   What message to log on a successful completion.
 *
 * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
 * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
 * @throws \Drupal\Core\Entity\Sql\SqlContentEntityStorageException
 */
function _smart_date_update_process_smart_date_field(string $entity_type_id, string $field_name, array $columns_to_resize, string $size, string $success_message): void {
  /** @var \Drupal\Core\Logger\LoggerChannel $logger */
  $logger = \Drupal::logger('update');

  $storage_definitions = \Drupal::service('entity_field.manager')->getFieldStorageDefinitions($entity_type_id);

  $entity_type_manager = \Drupal::entityTypeManager();
  $entity_type_manager->useCaches(FALSE);
  $entity_storage = $entity_type_manager->getStorage($entity_type_id);

  // Get the table mappings for this field.
  /** @var \Drupal\Core\Entity\Sql\DefaultTableMapping $table_mapping */
  $table_mapping = $entity_storage->getTableMapping($storage_definitions);

  // Field type column names map to real table column names.
  $columns = $table_mapping->getColumnNames($field_name);
  $column_names = [];
  foreach ($columns_to_resize as $column_to_resize) {
    if (!empty($columns[$column_to_resize])) {
      $column_names[$column_to_resize] = $columns[$column_to_resize];
    }
  }

  // We are allowed to change 'value' and 'end_value' columns, so if those do
  // not exist due contrib or custom alters leave everything unchanged.
  if (!$column_names) {
    $logger->notice("Smart Date column for entity '$entity_type_id' field '$field_name' not updated because database columns were not found.");
    return;
  }

  // Get the original storage definition for this field.
  $last_installed_schema_repository = \Drupal::service('entity.last_installed_schema.repository');
  $original_storage_definitions = $last_installed_schema_repository->getLastInstalledFieldStorageDefinitions($entity_type_id);
  $original_storage_definition = $original_storage_definitions[$field_name];

  // Get the current storage definition for this field.
  $storage_definition = $storage_definitions[$field_name];
  $storage = $entity_type_manager->getStorage($storage_definition->getTargetEntityTypeId());

  if (!($storage instanceof DynamicallyFieldableEntityStorageSchemaInterface
    && $storage->requiresFieldStorageSchemaChanges($storage_definition, $original_storage_definition))) {
    $logger->notice("Column for entity '$entity_type_id' field '$field_name' not updated because field size is already '$size'.");
    return;
  }

  $schema = \Drupal::database()->schema();
  $field_schema = $original_storage_definitions[$field_name]->getSchema() ?? $storage_definition->getSchema();
  $specification = $field_schema['columns']['value'];
  $specification['size'] = $size;
  foreach ($column_names as $column_name) {
    // Update the table specification for the column, setting to the size
    // provided.
    foreach ($table_mapping->getAllFieldTableNames($field_name) as $table) {
      $schema->changeField($table, $column_name, $column_name, $specification);
    }
  }

  // Update the tracked entity table schema, setting the size to 'big'.
  /** @var \Drupal\Core\Entity\EntityDefinitionUpdateManager $mgr */
  try {
    \Drupal::service('entity.definition_update_manager')->updateFieldStorageDefinition($storage_definition);
  }
  catch (FieldStorageDefinitionUpdateForbiddenException $e) {
  }

  $logger->notice($success_message, [
    '@entity_type_id' => $entity_type_id,
    '@field_name' => $field_name,
  ]);
}

/**
 * Process schema updates to the specified column.
 */
function _smart_date_update_schema(array $column_names, string $size) {
  /** @var \Drupal\Core\Logger\LoggerChannel $logger */
  $logger = \Drupal::logger('update');

  $entity_type_manager = \Drupal::entityTypeManager();
  $entity_field_manager = \Drupal::service('entity_field.manager');
  $entity_field_map = $entity_field_manager->getFieldMapByFieldType('smartdate');
  // The key-value collection for tracking installed storage schema.
  $entity_storage_schema_sql = \Drupal::keyValue('entity.storage_schema.sql');
  $entity_definitions_installed = \Drupal::keyValue('entity.definitions.installed');

  foreach ($entity_field_map as $entity_type_id => $field_map) {
    $entity_storage = $entity_type_manager->getStorage($entity_type_id);
    // Only SQL storage based entities are supported / throw known exception.
    if (!($entity_storage instanceof SqlContentEntityStorage)) {
      continue;
    }

    $entity_type = $entity_type_manager->getDefinition($entity_type_id);
    $field_storage_definitions = $entity_field_manager->getFieldStorageDefinitions($entity_type_id);
    /** @var Drupal\Core\Entity\Sql\DefaultTableMapping $table_mapping */
    $table_mapping = $entity_storage->getTableMapping($field_storage_definitions);
    // Only need field storage definitions of smart_date fields.
    /** @var \Drupal\Core\Field\FieldStorageDefinitionInterface $field_storage_definition */
    foreach (array_intersect_key($field_storage_definitions, $field_map) as $field_storage_definition) {
      $field_name = $field_storage_definition->getName();
      $table = $table_mapping->getFieldTableName($field_name);
      // See if the field has a revision table.
      $revision_table = NULL;
      if ($entity_type->isRevisionable() && $field_storage_definition->isRevisionable()) {
        if ($table_mapping->requiresDedicatedTableStorage($field_storage_definition)) {
          $revision_table = $table_mapping->getDedicatedRevisionTableName($field_storage_definition);
        }
        elseif ($table_mapping->allowsSharedTableStorage($field_storage_definition)) {
          $revision_table = $entity_type->getRevisionDataTable() ?: $entity_type->getRevisionTable();
        }
      }
      // Load the installed field schema so that it can be updated.
      $schema_key = "$entity_type_id.field_schema_data.$field_name";
      $field_schema_data = $entity_storage_schema_sql->get($schema_key);

      foreach ($column_names as $column_name) {
        $column = $table_mapping->getFieldColumnName($field_storage_definition, $column_name);
        // Update the column, including its revision counterpart.
        if ($size == 'normal') {
          unset($field_schema_data[$table]['fields'][$column]['size']);
        }
        else {
          $field_schema_data[$table]['fields'][$column]['size'] = $size;
        }
        if ($revision_table) {
          if ($size == 'normal') {
            unset($field_schema_data[$revision_table]['fields'][$column]['size']);
          }
          else {
            $field_schema_data[$revision_table]['fields'][$column]['size'] = $size;
          }
        }
      }

      // Save changes to the installed field schema.
      if ($field_schema_data) {
        $recipient_column = $table_mapping->getFieldColumnName($field_storage_definition, 'recipient');
        unset($field_schema_data[$table]['fields'][$recipient_column]);
        if ($revision_table) {
          unset($field_schema_data[$revision_table]['fields'][$recipient_column]);
        }
        $entity_storage_schema_sql->set($schema_key, $field_schema_data);
      }
      if ($table_mapping->allowsSharedTableStorage($field_storage_definition)) {
        $key = "$entity_type_id.field_storage_definitions";
        if ($definitions = $entity_definitions_installed->get($key)) {
          $definitions[$field_name] = $field_storage_definition;
          $entity_definitions_installed->set($key, $definitions);
        }
      }
      $logger->notice("Successfully updated stored schema for '$entity_type_id' field '$field_name'.");
    }
  }
}
