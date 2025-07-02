<?php

namespace Drupal\workbench_access\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Defines the workbench_access SectionAssociation class.
 *
 * @ContentEntityType(
 *   id = "section_association",
 *   label = @Translation("Section association"),
 *   bundle_label = @Translation("Access scheme"),
 *   bundle_entity_type = "access_scheme",
 *   internal = TRUE,
 *   handlers = {
 *     "access" = "Drupal\workbench_access\SectionAssociationAccessControlHandler",
 *     "storage" = "Drupal\workbench_access\SectionAssociationStorage",
 *     "views_data" = "\Drupal\workbench_access\ViewsData"
 *   },
 *   admin_permission = "assign workbench access",
 *   base_table = "section_association",
 *   data_table = "section_association_field_data",
 *   revision_table = "section_association_revision",
 *   revision_data_table = "section_association_field_revision_data",
 *   translatable = FALSE,
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "revision" = "vid",
 *     "bundle" = "access_scheme",
 *   }
 * )
 *
 * @internal
 *   This entity is marked internal because it should not be used directly.
 */
class SectionAssociation extends ContentEntityBase implements SectionAssociationInterface {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    // Assigned users.
    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Users'))
      ->setDescription(t('The Name of the associated user.'))
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setRevisionable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'author',
        'weight' => -2,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => 60,
          'placeholder' => '',
        ],
        'weight' => -2,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Assigned roles.
    $fields['role_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Role'))
      ->setDescription(t('The roles associated with this section.'))
      ->setSetting('target_type', 'user_role')
      ->setSetting('handler', 'default')
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setRevisionable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -2,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => 60,
          'placeholder' => '',
        ],
        'weight' => -3,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);
    $fields['section_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Section ID'))
      ->setDescription(t('The id of the access section.'))
      ->setRequired(TRUE)
      ->setTranslatable(FALSE)
      ->setSetting('max_length', 255);

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getSchemeId() {
    return $this->bundle();
  }

  /**
   * {@inheritdoc}
   */
  public function getCurrentUserIds() {
    $user_ids = [];
    if ($values = $this->get('user_id')) {
      foreach ($values as $value) {
        $target = $value->getValue();
        $user_ids[] = $target['target_id'];
      }
    }
    return $user_ids;
  }

  /**
   * {@inheritdoc}
   */
  public function getCurrentRoleIds() {
    $role_ids = [];
    if ($values = $this->get('role_id')) {
      foreach ($values as $value) {
        $target = $value->getValue();
        $role_ids[] = $target['target_id'];
      }
    }
    return $role_ids;
  }

}
