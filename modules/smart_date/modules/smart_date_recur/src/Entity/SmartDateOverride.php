<?php

namespace Drupal\smart_date_recur\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the Smart Date override entity.
 *
 * @ingroup smart_date_recur
 *
 * @ContentEntityType(
 *   id = "smart_date_override",
 *   label = @Translation("Smart Date override"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\smart_date_recur\Form\SmartDateOverrideForm",
 *       "add" = "Drupal\smart_date_recur\Form\SmartDateOverrideForm",
 *       "edit" = "Drupal\smart_date_recur\Form\SmartDateOverrideForm",
 *       "delete" = "Drupal\smart_date_recur\Form\SmartDateOverrideDeleteForm",
 *     },
 *   },
 *   base_table = "smart_date_override",
 *   data_table = "smart_date_override_field_data",
 *   translatable = FALSE,
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "value",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "delete-form" = "/admin/content/smart_date_recur/overrides/{smart_date_override}/delete"
 *   }
 * )
 */
class SmartDateOverride extends ContentEntityBase {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['rrule'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('RRule ID'))
      ->setSetting('unsigned', TRUE)
      ->setRequired(TRUE);

    $fields['rrule_index'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Index of the targeted instance withing the RRule'))
      ->setSetting('unsigned', TRUE)
      ->setRequired(TRUE);

    $fields['value'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Start timestamp value'))
      ->setRequired(TRUE);

    $fields['end_value'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('End timestamp value'))
      ->setRequired(TRUE);

    $fields['duration'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Duration, in minutes'))
      ->setRequired(FALSE);

    // @todo figure out a way to validate as required but accept zero.
    // Allow an instance to be overridden by a full entity.
    // NOTE: entity_type is skipped here because it will always match the rule.
    $fields['entity_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Entity ID'))
      ->setDescription(t('The ID of the entity which has been created as a full override.'))
      ->setRequired(FALSE);

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function onChange($property_name, $notify = TRUE) {
    // @todo trigger own notification based on $notify.
    // Enforce that the computed date is recalculated.
    if ($property_name == 'value') {
      $this->start_time = NULL;
    }
    elseif ($property_name == 'end_value') {
      $this->end_time = NULL;
    }
    parent::onChange($property_name);
  }

}
