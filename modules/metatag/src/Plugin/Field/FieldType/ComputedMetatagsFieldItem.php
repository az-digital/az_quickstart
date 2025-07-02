<?php

namespace Drupal\metatag\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\MapDataDefinition;

/**
 * Plugin implementation of the 'metatag_computed' field type.
 *
 * @FieldType(
 *   id = "metatag_computed",
 *   label = @Translation("Meta tags (computed)"),
 *   description = @Translation("Computed meta tags"),
 *   no_ui = TRUE,
 *   list_class = "\Drupal\metatag\Plugin\Field\MetatagEntityFieldItemList",
 * )
 */
class ComputedMetatagsFieldItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['tag'] = DataDefinition::create('string')
      ->setLabel(t('Tag'))
      ->setRequired(TRUE);
    $properties['attributes'] = MapDataDefinition::create()
      ->setLabel(t('Name'))
      ->setRequired(TRUE);
    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('attributes')->getValue();
    return $value === NULL || $value === [];
  }

}
