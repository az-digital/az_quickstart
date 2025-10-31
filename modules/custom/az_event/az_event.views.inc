<?php

/**
 * @file
 * Provides views data for the calendar module.
 */

use Drupal\field\FieldStorageConfigInterface;

/**
 * Implements hook_field_views_data_alter().
 */
function az_event_field_views_data_alter(array &$data, FieldStorageConfigInterface $field_storage) {
  foreach ($data as $table_name => $table_data) {
    // Set the 'datetime' filter type.
    if (isset($data[$table_name][$field_storage->getName() . '_value'])
    && $data[$table_name][$field_storage->getName() . '_value']['filter']['id'] === 'date') {
      // Create year, month, and day arguments.
      $group = $data[$table_name][$field_storage->getName() . '_value']['group'];
      $data[$table_name][$field_storage->getName() . '_value_az_calendar'] = [
        'title' => 'Calendar Filter ' . $field_storage->getLabel(),
        'help' => t('Calendar Filter Widget.'),
        'filter' => [
          'field' => $field_storage->getName() . '',
          'id' => 'az_calendar_filter',
          'entity_type' => $field_storage->getTargetEntityTypeId(),
          'field_name' => $field_storage->getName(),
        ],
        'group' => $group,
      ];
    }
  }
}
