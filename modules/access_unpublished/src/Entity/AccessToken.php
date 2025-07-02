<?php

namespace Drupal\access_unpublished\Entity;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\Crypt;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\access_unpublished\AccessTokenInterface;
use Drupal\Core\Site\Settings;
use Drupal\user\UserInterface;

/**
 * Defines the Access token entity.
 *
 * @ingroup access_unpublished
 *
 * @ContentEntityType(
 *   id = "access_token",
 *   label = @Translation("Access token"),
 *   label_collection = @Translation("Access token"),
 *   label_singular = @Translation("access token"),
 *   label_plural = @Translation("access tokens"),
 *   label_count = @PluralTranslation(
 *     singular = "@count access token",
 *     plural = "@count access tokens"
 *   ),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\access_unpublished\AccessTokenListBuilder",
 *     "entity_form_list_builder" = "Drupal\access_unpublished\EntityFormAccessTokenListBuilder",
 *     "access" = "Drupal\access_unpublished\AccessTokenAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\access_unpublished\Routing\AccessTokenRouteProvider",
 *     },
 *   },
 *   base_table = "access_token",
 *   admin_permission = "administer access token entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *   },
 *   links = {
 *     "delete" = "/access_token/{access_token}/delete",
 *     "renew" = "/access_token/{access_token}/renew",
 *   }
 * )
 */
class AccessToken extends ContentEntityBase implements AccessTokenInterface {
  use EntityChangedTrait;

  /**
   * The default time while the token is valid.
   *
   * @var int
   */
  const DEFAULT_EXPIRATION_PERIOD = 172800;

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += [
      'user_id' => \Drupal::currentUser()->id(),
    ];
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
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('user_id')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('user_id')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('user_id', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('user_id', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('ID'))
      ->setDescription(t('The ID of the Access token entity.'))
      ->setReadOnly(TRUE);
    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The UUID of the Access token entity.'))
      ->setReadOnly(TRUE);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of author of the Access token entity.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setDefaultValueCallback('Drupal\access_unpublished\Entity\AccessToken::getCurrentUserId')
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 5,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['value'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Token'))
      ->setDescription(t('The token value.'))
      ->setSettings([
        'max_length' => 128,
        'text_processing' => 0,
      ])
      ->setRequired(TRUE)
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'region' => 'hidden',
      ])
      ->setDisplayConfigurable('form', FALSE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['expire'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Expire'))
      ->setDefaultValueCallback(__CLASS__ . '::defaultExpiration')
      ->setDescription(t('The time when the token expires.'))
      ->setDisplayOptions('form', [
        'type' => 'datetime_timestamp',
        'weight' => 1,
      ])
      ->setDisplayOptions('view', [
        'label' => 'inline',
        'type' => 'timestamp',
        'weight' => 1,
      ])
      ->setRequired(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['entity_type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Entity type'))
      ->setDescription(t('The type of token belongs to.'))
      ->setRevisionable(TRUE)
      ->setReadOnly(TRUE);

    $fields['entity_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Entity id'))
      ->setDescription(t('The id of token belongs to.'))
      ->setRevisionable(TRUE)
      ->setReadOnly(TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultExpiration() {
    $defaultDuration = \Drupal::config('access_unpublished.settings')->get('duration') ?? static::DEFAULT_EXPIRATION_PERIOD;
    if ($defaultDuration === -1) {
      return [$defaultDuration];
    }
    else {
      return [\Drupal::time()->getRequestTime() + $defaultDuration];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);
    // Create the token value as a digestion of the values in the token. This
    // will allow us to check integrity of the token later.
    if ($this->get('value')->isEmpty()) {
      $value = Crypt::hmacBase64(Json::encode($this->normalize()), Settings::getHashSalt());
      $this->set('value', $value);
    }
  }

  /**
   * Normalize the entity by extracting its important values.
   *
   * @return array
   *   The normalized entity.
   */
  protected function normalize() {
    $keys = ['entity_type', 'expire', 'created', 'entity_id'];
    $values = array_map(function ($item) {
      return $this->get($item)->getValue();
    }, $keys);
    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function isExpired() {
    return ($this->get('expire')->value > 0 && $this->get('expire')->value < \Drupal::time()->getRequestTime());
  }

  /**
   * Default value callback for 'uid' base field definition.
   *
   * @see ::baseFieldDefinitions()
   *
   * @return array
   *   An array of default values.
   */
  public static function getCurrentUserId() {
    return [\Drupal::currentUser()->id()];
  }

  /**
   * {@inheritdoc}
   */
  public function getHost() {
    if ($this->get('entity_type')->value && $this->get('entity_id')->value) {
      try {
        return \Drupal::entityTypeManager()->getStorage($this->get('entity_type')->value)->load($this->get('entity_id')->value);
      }
      catch (\Exception $exception) {
        return NULL;
      }
    }
  }

}
