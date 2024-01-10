<?php

/**
 * @file
 * Provide views data tied to the az_publication module.
 */

use Drupal\field\FieldStorageConfigInterface;

/**
 * Implements hook_views_data().
 */
function az_publication_views_data() {

  $data['views']['az_citation_style_argument'] = [
    'title' => t('Citation Style'),
    'help' => t('Allow a contextual argument to select citation style.'),
    'argument' => [
      'id' => 'az_citation_style_argument',
    ],
  ];

  return $data;
}

/**
 * Implements hook_field_views_data_alter().
 */
function az_publication_field_views_data_alter(array &$data, FieldStorageConfigInterface $field_storage) {
  $field_name = $field_storage->getName();
  if ($field_name === 'field_az_publication_type') {
    foreach ($data as $table_name => $table_data) {
      if (isset($data[$table_name][$field_name . '_value'])) {
        $group = $data[$table_name][$field_name . '_value']['group'];
        $data[$table_name][$field_name . '_value_selective'] = [
          'title' => 'Pre-filtered ' . $field_storage->getLabel(),
          'help' => t('Selective List Filter'),
          'filter' => [
            'field' => $field_name . '_value',
            'id' => 'az_publication_list_field',
            'entity_type' => $field_storage->getTargetEntityTypeId(),
            'field_name' => $field_name,
          ],
          'group' => $group,
        ];
      }
    }
  }
}
