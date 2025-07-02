<?php

namespace Drupal\entity_host_relationship_test\Entity;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\entity_test\Entity\EntityTestMulRev;

/**
 * Defines the test entity class.
 *
 * @ContentEntityType(
 *   id = "entity_host_relationship_test",
 *   label = @Translation("Test entity host"),
 *   base_table = "entity_test_host",
 *   revision_table = "entity_test_host_revision",
 *   data_table = "entity_test_host_field_data",
 *   revision_data_table = "entity_test_host_field_revision",
 *   content_translation_ui_skip = TRUE,
 *   translatable = TRUE,
 *   admin_permission = "administer entity_test host",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "revision" = "revision_id",
 *     "bundle" = "type",
 *     "label" = "name",
 *     "langcode" = "langcode",
 *   }
 * )
 */
class EntityTestHostRelationship extends EntityTestMulRev implements RevisionableInterface {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['entity'] = BaseFieldDefinition::create('entity_reference_revisions')
      ->setLabel(t('Entity test composite'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'entity_test_composite')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }
}
