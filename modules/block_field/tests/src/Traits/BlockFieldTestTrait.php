<?php

namespace Drupal\Tests\block_field\Traits;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Provides common functionality for the Block Field test classes.
 */
trait BlockFieldTestTrait {

  /**
   * Creates a block field on the specified bundle.
   *
   * @param string $entity_type
   *   The type of entity the field will be attached to.
   * @param string $bundle
   *   The bundle name of the entity the field will be attached to.
   * @param string $field_name
   *   The name of the field; if it already exists, a new instance of the
   *   existing field will be created.
   * @param string $field_label
   *   The label of the field.
   * @param string $selection_handler
   *   The selection handler used by this field.
   * @param array $selection_handler_settings
   *   An array of settings supported by the selection handler specified above.
   *   (e.g. 'plugin_ids').
   * @param int $cardinality
   *   The cardinality of the field.
   *
   * @see \Drupal\Core\Entity\Plugin\EntityReferenceSelection\SelectionBase::buildConfigurationForm()
   */
  protected function createBlockField($entity_type, $bundle, $field_name, $field_label, $selection_handler = 'blocks', array $selection_handler_settings = [], $cardinality = 1) {
    // Look for or add the specified field to the requested entity bundle.
    if (!FieldStorageConfig::loadByName($entity_type, $field_name)) {
      FieldStorageConfig::create([
        'field_name' => $field_name,
        'type' => 'block_field',
        'entity_type' => $entity_type,
        'cardinality' => $cardinality,
        'settings' => [],
      ])->save();
    }
    if (!FieldConfig::loadByName($entity_type, $bundle, $field_name)) {
      FieldConfig::create([
        'field_name' => $field_name,
        'entity_type' => $entity_type,
        'bundle' => $bundle,
        'label' => $field_label,
        'settings' => [
          'selection' => $selection_handler,
          'selection_settings' => $selection_handler_settings,
        ],
      ])->save();
    }
  }

}
