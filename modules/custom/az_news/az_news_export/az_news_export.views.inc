<?php

/**
 * @file
 * Provides views data for az_news_export.module.
 */

use Drupal\Core\Entity\Sql\SqlEntityStorageInterface;

/**
 * Implements hook_views_data_alter().
 *
 * Avoid using hook_field_views_data_alter() because taxonomy module
 * replaces taxonomy term reference field plugins with its own.
 *
 *  @see taxonomy_field_views_data_alter()
 */
function az_news_export_views_data_alter(&$data) {

  $entity_type_manager = \Drupal::entityTypeManager();
  if ($entity_type_manager->hasDefinition('field_storage_config')) {
    /** @var \Drupal\field\FieldStorageConfigInterface $field_storage */
    $field_storage = $entity_type_manager->getStorage('field_storage_config')->load('node.field_az_enterprise_attributes');
    // Phpstan doesn't know this can be NULL.
    // @phpstan-ignore-next-line
    if (!empty($field_storage)) {
      $field_name = $field_storage->getName();
      // Compute entity database table and field information.
      $entity_type_id = $field_storage->getTargetEntityTypeId();
      $storage = $entity_type_manager->getStorage($entity_type_id);
      // Only defined if the field is on a database-backed entity.
      if ($storage instanceof SqlEntityStorageInterface) {
        $entity_type = $entity_type_manager->getDefinition($entity_type_id);
        $attribute_field_key = $field_name . '_az_attribute_key';
        /** @var \Drupal\Core\Entity\Sql\DefaultTableMapping $table_mapping */
        $table_mapping = $storage->getTableMapping();
        $table = $table_mapping->getDedicatedDataTableName($field_storage);
        $label = \Drupal::service('entity_field.manager')->getFieldLabels($entity_type_id, $field_name);
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

}
