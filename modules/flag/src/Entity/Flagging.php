<?php

namespace Drupal\flag\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\flag\Event\FlagEvents;
use Drupal\flag\Event\FlaggingEvent;
use Drupal\flag\Event\UnflaggingEvent;
use Drupal\flag\FlaggingInterface;
use Drupal\flag\Plugin\Field\FlaggedEntityFieldItemList;
use Drupal\user\UserInterface;

/**
 * Provides the flagging content entity.
 *
 * @ContentEntityType(
 *  id = "flagging",
 *  label = @Translation("Flagging"),
 *  label_singular = @Translation("flagging"),
 *  label_plural = @Translation("flaggings"),
 *  label_count = @PluralTranslation(
 *    singular = "@count flagging",
 *    plural = "@count flaggings",
 *  ),
 *  bundle_label = @Translation("Flag"),
 *  admin_permission = "administer flaggings",
 *  handlers = {
 *    "storage" = "Drupal\flag\Entity\Storage\FlaggingStorage",
 *    "storage_schema" = "Drupal\flag\Entity\Storage\FlaggingStorageSchema",
 *    "form" = {
 *      "add" = "Drupal\flag\Form\FlaggingForm",
 *      "edit" = "Drupal\flag\Form\FlaggingForm",
 *      "delete" = "Drupal\flag\Form\UnflagConfirmForm"
 *    },
 *    "views_data" = "Drupal\flag\FlaggingViewsData",
 *  },
 *  base_table = "flagging",
 *  entity_keys = {
 *    "id" = "id",
 *    "bundle" = "flag_id",
 *    "uuid" = "uuid",
 *    "uid" = "uid"
 *  },
 *  bundle_entity_type = "flag",
 *  field_ui_base_route = "entity.flag.edit_form",
 *  links = {
 *   "delete-form" = "/flag/details/delete/{flag}/{entity_id}",
 *  }
 * )
 */
class Flagging extends ContentEntityBase implements FlaggingInterface {

  // @todo should there be a data_table annotation?
  // phpcs:ignore
  // @todo should the bundle entity_key annotation be "flag" not "type"?

  /**
   * {@inheritdoc}
   */
  public function __construct(array $values, $entity_type, $bundle = FALSE, $translations = []) {
    if (isset($values['entity_id'])) {
      $values['flagged_entity'] = $values['entity_id'];
    }
    parent::__construct($values, $entity_type, $bundle, $translations);
  }

  /**
   * {@inheritdoc}
   */
  public function getFlagId() {
    return $this->get('flag_id')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getFlag() {
    return $this->entityTypeManager()->getStorage('flag')->load($this->getFlagId());
  }

  /**
   * {@inheritdoc}
   */
  public function getFlaggableType() {
    return $this->get('entity_type')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getFlaggableId() {
    return $this->get('entity_id')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getFlaggable() {
    $flaggable_type = $this->getFlaggableType();
    $flaggable_id = $this->getFlaggableId();
    return $this->entityTypeManager()->getStorage($flaggable_type)->load($flaggable_id);
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    // This field is on flaggings even though it duplicates the entity type
    // field on the flag so that flagging queries can use it.
    $fields['entity_type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Entity Type'))
      ->setDescription(t('The Entity Type.'));

    $fields['entity_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Entity ID'))
      ->setRequired(TRUE)
      ->setDescription(t('The Entity ID.'));

    $fields['flagged_entity'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Entity'))
      ->setDescription(t('The flagged entity.'))
      ->setComputed(TRUE)
      ->setClass(FlaggedEntityFieldItemList::class);

    // Also duplicates data on flag entity for querying purposes.
    $fields['global'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Global'))
      ->setDescription(t('A boolean indicating whether the flagging is global.'));

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('User ID'))
      ->setDescription(t('The user ID of the flagging user. This is recorded for both global and personal flags.'))
      ->setSettings([
        'target_type' => 'user',
        'default_value' => 0,
      ]);

    $fields['session_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Session ID'))
      ->setDescription(t('The session ID associated with an anonymous user.'));

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the flagging was created.'));

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function onChange($name) {
    if ($name == 'entity_id' && $this->get('flagged_entity')->isEmpty()) {
      $this->flagged_entity->target_id = $this->entity_id->value;
    }
    if (in_array($name, ['flagged_entity', 'entity_id']) && $this->flagged_entity->target_id != $this->entity_id->value) {
      throw new \LogicException("A flagging can't be moved to another entity.");
    }
    parent::onChange($name);
  }

  /**
   * {@inheritdoc}
   */
  public static function bundleFieldDefinitions(EntityTypeInterface $entity_type, $bundle, array $base_field_definitions) {
    $flag = Flag::load($bundle);
    if ($flag) {
      /** @var \Drupal\Core\Field\BaseFieldDefinition $flagged_entity */
      $flagged_entity = clone $base_field_definitions['flagged_entity'];
      $flagged_entity->setSetting('target_type', $flag->getFlaggableEntityTypeId());
      $fields['flagged_entity'] = $flagged_entity;
      return $fields;
    }
    return parent::bundleFieldDefinitions($entity_type, $bundle, $base_field_definitions);
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);

    if (!$update) {
      \Drupal::service('event_dispatcher')->dispatch(new FlaggingEvent($this), FlagEvents::ENTITY_FLAGGED);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function preDelete(EntityStorageInterface $storage, array $entities) {
    parent::preDelete($storage, $entities);

    $event = new UnflaggingEvent($entities);
    \Drupal::service('event_dispatcher')->dispatch($event, FlagEvents::ENTITY_UNFLAGGED);
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('uid')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('uid', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->getEntityKey('uid');
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('uid', $uid);
    return $this;
  }

}
