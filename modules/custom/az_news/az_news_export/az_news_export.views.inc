<?php

/**
 * @file
 * Provides views data for az_news_export.module.
 */

use Drupal\Core\Entity\Sql\SqlEntityStorageInterface;
use Drupal\field\FieldStorageConfigInterface;

/**
 * Implements hook_field_views_data_alter().
 *
 * Views integration for taxonomy reference fields with attribute keys.
 *
 * @see views_field_default_views_data()
 */
function az_news_export_field_views_data_alter(array &$data, FieldStorageConfigInterface $field_storage) {
  $field_name = $field_storage->getName();
  if ($field_name === 'field_az_enterprise_attributes') {
    // Compute entity database table and field information.
    $entity_type_id = $field_storage->getTargetEntityTypeId();
    $storage = \Drupal::entityTypeManager()->getStorage($entity_type_id);
    // Only defined if the field is on a database-backed entity.
    if ($storage instanceof SqlEntityStorageInterface) {
      $entity_type = \Drupal::entityTypeManager()->getDefinition($entity_type_id);
      $attribute_field_key = $field_name . '_az_attribute_key';
      /** @var \Drupal\Core\Entity\Sql\DefaultTableMapping $table_mapping */
      $table_mapping = $storage->getTableMapping();
      $table = $table_mapping->getDedicatedDataTableName($field_storage);
      $label = views_entity_field_label($entity_type_id, $field_name);
      $label = reset($label);
      $entity_label = $entity_type->getLabel();
      // Signal we provide a filter on the field.
      $data[$table][$attribute_field_key] = [
        'title' => t('@entity with @field attribute key', [
          '@entity' => $entity_label,
          '@field' => $label,
        ]),
        'help' => t('@entity with @field attribute key', [
          '@entity' => $entity_label,
          '@field' => $label,
        ]),
        'group' => $entity_label,
        'filter' => [
          'field' => $field_name . '_target_id',
          'table' => $table_mapping->getDedicatedDataTableName($field_storage),
          'id' => 'az_attribute_tid',
          'additional_fields' => $table_mapping->getAllColumns($table),
          'field_name' => $field_name,
          'entity_type' => $entity_type_id,
          // Needed to allow empty operations.
          'allow empty' => TRUE,
        ],
      ];
    }

  }

}
