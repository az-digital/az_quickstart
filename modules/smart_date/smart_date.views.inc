<?php

/**
 * @file
 * Provides views data for the smart_date module.
 */

use Drupal\field\FieldStorageConfigInterface;

/**
 * Implements hook_field_views_data().
 */
function smart_date_field_views_data(FieldStorageConfigInterface $field) {
  $data = views_field_default_views_data($field);
  $field_name = $field->getName();
  $entity_type_id = $field->getTargetEntityTypeId();
  $entity_manager = \Drupal::entityTypeManager();
  $entity_type = $entity_manager->getDefinition($entity_type_id);

  // Override the default handlers.
  $columns = [
    'value' => 'date',
    'end_value' => 'date',
    'duration' => 'numeric',
    'timezone' => 'standard',
    'rrule' => 'standard',
  ];
  // Provide human-readable property names.
  $labels = [
    'value' => t('Start'),
    'end_value' => t('End'),
    'duration' => t('Duration'),
    'timezone' => t('Timezone'),
    'rrule' => t('Recurring'),
  ];
  // Provide human-readable property help text.
  $desc = [
    'value' => t('The start of the specified date/time range.'),
    'end_value' => t('The end of the specified date/time range.'),
    'duration' => t('The duration of the specified date/time range.'),
    'timezone' => t('The timezone of the specified date/time range.'),
    'rrule' => t('The recurrence rule for the specified date/time range.'),
  ];
  // The set of views handlers we want to manipulate.
  $types = [
    'field',
    'filter',
    'sort',
    'argument',
  ];
  foreach ($data as $table_name => $table_data) {
    if (!isset($table_data[$field_name])) {
      continue;
    }
    $base = $table_data[$field_name];
    foreach ($columns as $column => $plugin_id) {
      foreach ($types as $type) {
        if (isset($data[$table_name][$field_name . '_' . $column][$type]) || $type == 'field') {
          $plugin_id_adjusted = $plugin_id;
          // For certain types, the plugin id needs to change.
          if ($plugin_id == 'standard' && in_array($type, ['filter', 'argument'])) {
            $plugin_id_adjusted = 'string';
          }
          // Override the default data with our custom values.
          $data[$table_name][$field_name . '_' . $column][$type]['title'] = $base['title'] . ' - ' . $labels[$column];
          $data[$table_name][$field_name . '_' . $column][$type]['id'] = $plugin_id_adjusted;
          $data[$table_name][$field_name . '_' . $column][$type]['help'] = $desc[$column];
          $data[$table_name][$field_name . '_' . $column][$type]['field_name'] = $field_name;
          $data[$table_name][$field_name . '_' . $column][$type]['property'] = $column;
        }
      }
    }
    // Provide a relationship for the entity type with the entity reference
    // revisions field.
    $args = [
      '@label' => t('Smart date recurring rule'),
      '@field_name' => $field_name,
    ];

    $data[$table_name][$field_name . '_rrule']['relationship'] = [
      'title' => t('@label referenced from @field_name', $args),
      'label' => t('@field_name: @label', $args),
      'group' => $entity_type->getLabel(),
      'help' => t('Appears in: @bundles.', ['@bundles' => implode(', ', $field->getBundles())]),
      'id' => 'standard',
      'base' => 'smart_date_rule',
      'entity type' => 'smart_date_rule',
      'base field' => 'rid',
      'relationship field' => $field_name . '_rrule',
    ];
  }

  return $data;
}
