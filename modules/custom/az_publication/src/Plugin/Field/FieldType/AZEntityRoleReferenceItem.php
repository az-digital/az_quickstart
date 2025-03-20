<?php

namespace Drupal\az_publication\Plugin\Field\FieldType;

use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\Core\Field\Attribute\FieldType;
use Drupal\Core\Field\EntityReferenceFieldItemList;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Defines the 'az_entity_role_reference' entity field type.
 *
 * Supported settings (below the definition's 'settings' key) are:
 * - target_type: The entity type to reference. Required.
 */
#[FieldType(
  id: "az_entity_role_reference",
  label: new TranslatableMarkup("Entity Role reference"),
  description: new TranslatableMarkup("An entity field containing an entity reference and a contributor role."),
  category: "reference",
  default_widget: "az_entity_role_inline_entity_form_complex",
  default_formatter: "az_entity_role_reference_label",
  list_class: EntityReferenceFieldItemList::class,
)]
class AZEntityRoleReferenceItem extends EntityReferenceItem {

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties = parent::propertyDefinitions($field_definition);

    $properties['role'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('contributor Role'))
      ->setRequired(TRUE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $schema = parent::schema($field_definition);
    $schema['columns']['role'] = [
      'description' => 'The role of the target entity.',
      'type' => 'varchar_ascii',
      'length' => 255,
    ];
    return $schema;
  }

}
