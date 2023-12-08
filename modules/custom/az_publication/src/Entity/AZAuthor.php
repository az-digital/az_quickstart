<?php

namespace Drupal\az_publication\Entity;

use Drupal\Core\Entity\EditorialContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityPublishedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\user\UserInterface;

/**
 * Defines the Author entity.
 *
 * @ingroup az_publication
 *
 * @ContentEntityType(
 *   id = "az_author",
 *   label = @Translation("Author"),
 *   label_collection = @Translation("Authors"),
 *   label_singular = @Translation("author"),
 *   label_plural = @Translation("authors"),
 *   label_count = @PluralTranslation(
 *     singular = "@count author",
 *     plural = "@count authors"
 *   ),
 *   handlers = {
 *     "storage" = "Drupal\az_publication\AZAuthorStorage",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\az_publication\AZAuthorListBuilder",
 *     "views_data" = "Drupal\az_publication\Entity\AZAuthorViewsData",
 *     "translation" = "Drupal\az_publication\AZAuthorTranslationHandler",
 *     "inline_form" = "Drupal\az_publication\Form\AZAuthorInlineForm",
 *
 *     "form" = {
 *       "default" = "Drupal\az_publication\Form\AZAuthorForm",
 *       "add" = "Drupal\az_publication\Form\AZAuthorForm",
 *       "edit" = "Drupal\az_publication\Form\AZAuthorForm",
 *       "delete" = "Drupal\az_publication\Form\AZAuthorDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\az_publication\AZAuthorHtmlRouteProvider",
 *     },
 *     "access" = "Drupal\az_publication\AZAuthorAccessControlHandler",
 *   },
 *   base_table = "az_author",
 *   data_table = "az_author_field_data",
 *   revision_table = "az_author_revision",
 *   revision_data_table = "az_author_field_revision",
 *   show_revision_ui = TRUE,
 *   translatable = TRUE,
 *   admin_permission = "administer author entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "revision" = "vid",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "langcode" = "langcode",
 *     "published" = "status",
 *   },
 *   revision_metadata_keys = {
 *     "revision_user" = "revision_uid",
 *     "revision_created" = "revision_timestamp",
 *     "revision_log_message" = "revision_log"
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/az_author/{az_author}",
 *     "add-form" = "/admin/structure/az_author/add",
 *     "edit-form" = "/admin/structure/az_author/{az_author}/edit",
 *     "delete-form" = "/admin/structure/az_author/{az_author}/delete",
 *     "version-history" = "/admin/structure/az_author/{az_author}/revisions",
 *     "revision" = "/admin/structure/az_author/{az_author}/revisions/{az_author_revision}/view",
 *     "revision_revert" = "/admin/structure/az_author/{az_author}/revisions/{az_author_revision}/revert",
 *     "revision_delete" = "/admin/structure/az_author/{az_author}/revisions/{az_author_revision}/delete",
 *     "translation_revert" = "/admin/structure/az_author/{az_author}/revisions/{az_author_revision}/revert/{langcode}",
 *     "collection" = "/admin/structure/az_author",
 *     "auto-label" = "/admin/structure/az_author/auto-label",
 *   },
 *   field_ui_base_route = "az_author.settings"
 * )
 */
class AZAuthor extends EditorialContentEntityBase implements AZAuthorInterface {

  use EntityChangedTrait;
  use EntityPublishedTrait;

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
  protected function urlRouteParameters($rel) {
    $uri_route_parameters = parent::urlRouteParameters($rel);

    if ($rel === 'revision_revert' && $this instanceof RevisionableInterface) {
      $uri_route_parameters[$this->getEntityTypeId() . '_revision'] = $this->getRevisionId();
    }
    elseif ($rel === 'revision_delete' && $this instanceof RevisionableInterface) {
      $uri_route_parameters[$this->getEntityTypeId() . '_revision'] = $this->getRevisionId();
    }

    return $uri_route_parameters;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    foreach (array_keys($this->getTranslationLanguages()) as $langcode) {
      /** @var \Drupal\az_publication\Entity\AZAuthorInterface $translation */
      $translation = $this->getTranslation($langcode);

      // If no owner has been set explicitly, make the anonymous user the owner.
      if (!$translation->getOwner()) {
        $translation->setOwnerId(0);
      }
    }

    // If no revision author has been set explicitly,
    // make the az_author owner the revision author.
    if (!$this->getRevisionUser()) {
      $this->setRevisionUserId($this->getOwnerId());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->get('name')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setName($name) {
    $this->set('name', $name);
    return $this;
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
    $fields = parent::baseFieldDefinitions($entity_type);

    // Add the published field.
    $fields += static::publishedBaseFieldDefinitions($entity_type);
    if (!empty($fields['status']) && $fields['status'] instanceof BaseFieldDefinition) {
      $fields['status']
        ->setDisplayOptions('form', [
          'type' => 'boolean_checkbox',
          'settings' => [
            'display_label' => TRUE,
          ],
          'weight' => 120,
        ])
        ->setDisplayConfigurable('form', TRUE);
    }

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of author of the Author entity.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
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

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The name of the Author entity.'))
      ->setRevisionable(TRUE)
      ->setSettings([
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(TRUE);

    $fields['status']->setDescription(t('A boolean indicating whether the Author is published.'))
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => -3,
      ]);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    $fields['revision_translation_affected'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Revision translation affected'))
      ->setDescription(t('Indicates if the last edit of a translation belongs to current revision.'))
      ->setReadOnly(TRUE)
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE);

    return $fields;
  }

}
