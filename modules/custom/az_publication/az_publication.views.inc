<?php

/**
 * @file
 * Provide views data tied to the az_publication module.
 */

use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
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
 * Implements hook_field_views_data().
 *
 * The function implements a hook for relationships on
 * az_entity_role_reference fields. This is distinct from core's
 * entity_reference relationship because publications have a special
 * reference field plugin that also contains role mapping.
 */
function az_publication_field_views_data(FieldStorageConfigInterface $field_storage) {
  $data = \Drupal::service('views.field_data_provider')->defaultFieldImplementation(($field_storage);

  // The code below only deals with the Entity Role referencefield type.
  if ($field_storage->getType() !== 'az_entity_role_reference') {
    return $data;
  }

  $entity_type_manager = \Drupal::entityTypeManager();
  $entity_type_id = $field_storage->getTargetEntityTypeId();
  $storage = $entity_type_manager->getStorage($entity_type_id);
  if (!($storage instanceof SqlContentEntityStorage)) {
    return $data;
  }
  /** @var \Drupal\Core\Entity\Sql\DefaultTableMapping $table_mapping */
  $table_mapping = $storage->getTableMapping();

  foreach ($data as $table_name => $table_data) {
    // Add a relationship to the target entity type.
    $target_entity_type_id = $field_storage->getSetting('target_type');
    $target_entity_type = $entity_type_manager->getDefinition($target_entity_type_id);
    $entity_type_id = $field_storage->getTargetEntityTypeId();
    $entity_type = $entity_type_manager->getDefinition($entity_type_id);
    $target_base_table = $target_entity_type->getDataTable() ?: $target_entity_type->getBaseTable();
    $field_name = $field_storage->getName();

    if ($target_entity_type instanceof ContentEntityTypeInterface) {
      // Provide a relationship for the entity type with the entity role
      // reference field.
      $args = [
        '@label' => $target_entity_type->getLabel(),
        '@field_name' => $field_name,
      ];
      $data[$table_name][$field_name]['relationship'] = [
        'title' => t('@label referenced from @field_name', $args),
        'label' => t('@field_name: @label', $args),
        'group' => $entity_type->getLabel(),
        'help' => t('Appears in: @bundles.', ['@bundles' => implode(', ', $field_storage->getBundles())]),
        'id' => 'standard',
        'base' => $target_base_table,
        'entity type' => $target_entity_type_id,
        'base field' => $target_entity_type->getKey('id'),
        'relationship field' => $field_name . '_target_id',
      ];

      // Provide a reverse relationship for the entity type that is referenced
      // by the field.
      $args['@entity'] = $entity_type->getLabel();
      $args['@label'] = $target_entity_type->getSingularLabel();
      $pseudo_field_name = 'reverse__' . $entity_type_id . '__' . $field_name;
      $data[$target_base_table][$pseudo_field_name]['relationship'] = [
        'title' => t('@entity using @field_name', $args),
        'label' => t('@field_name', ['@field_name' => $field_name]),
        'group' => $target_entity_type->getLabel(),
        'help' => t('Relate each @entity with a @field_name set to the @label.', $args),
        'id' => 'entity_reverse',
        'base' => $entity_type->getDataTable() ?: $entity_type->getBaseTable(),
        'entity_type' => $entity_type_id,
        'base field' => $entity_type->getKey('id'),
        'field_name' => $field_name,
        'field table' => $table_mapping->getDedicatedDataTableName($field_storage),
        'field field' => $field_name . '_target_id',
        'join_extra' => [
          [
            'field' => 'deleted',
            'value' => 0,
            'numeric' => TRUE,
          ],
        ],
      ];
    }

    // Provide an argument plugin that has a meaningful titleQuery()
    // implementation getting the entity label.
    $data[$table_name][$field_name . '_target_id']['argument']['id'] = 'entity_target_id';
    $data[$table_name][$field_name . '_target_id']['argument']['target_entity_type_id'] = $target_entity_type_id;
  }

  return $data;
}
