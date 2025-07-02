<?php

namespace Drupal\paragraphs\Entity;

use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Entity\Attribute\ContentEntityType;
use Drupal\Core\Entity\ContentEntityDeleteForm;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityPublishedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\ChangedFieldItemList;
use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\TranslatableInterface;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\entity_reference_revisions\EntityNeedsSaveTrait;
use Drupal\field\FieldConfigInterface;
use Drupal\paragraphs\ParagraphAccessControlHandler;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\paragraphs\ParagraphStorageSchema;
use Drupal\paragraphs\ParagraphViewBuilder;
use Drupal\user\EntityOwnerInterface;
use Drupal\user\UserInterface;
use Drupal\views\EntityViewsData;

/**
 * Defines the Paragraph entity.
 *
 * @ingroup paragraphs
 *
 * @ContentEntityType(
 *   id = "paragraph",
 *   label = @Translation("Paragraph"),
 *   label_collection = @Translation("Paragraphs"),
 *   label_singular = @Translation("Paragraph"),
 *   label_plural = @Translation("Paragraphs"),
 *   label_count = @PluralTranslation(
 *     singular = "@count Paragraph",
 *     plural = "@count Paragraphs",
 *   ),
 *   bundle_label = @Translation("Paragraph type"),
 *   handlers = {
 *     "view_builder" = "Drupal\paragraphs\ParagraphViewBuilder",
 *     "access" = "Drupal\paragraphs\ParagraphAccessControlHandler",
 *     "storage_schema" = "Drupal\paragraphs\ParagraphStorageSchema",
 *     "form" = {
 *       "default" = "Drupal\Core\Entity\ContentEntityForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *       "edit" = "Drupal\Core\Entity\ContentEntityForm"
 *     },
 *     "views_data" = "Drupal\views\EntityViewsData",
 *   },
 *   base_table = "paragraphs_item",
 *   data_table = "paragraphs_item_field_data",
 *   revision_table = "paragraphs_item_revision",
 *   revision_data_table = "paragraphs_item_revision_field_data",
 *   translatable = TRUE,
 *   entity_revision_parent_type_field = "parent_type",
 *   entity_revision_parent_id_field = "parent_id",
 *   entity_revision_parent_field_name_field = "parent_field_name",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "bundle" = "type",
 *     "langcode" = "langcode",
 *     "revision" = "revision_id",
 *     "published" = "status"
 *   },
 *   bundle_entity_type = "paragraphs_type",
 *   field_ui_base_route = "entity.paragraphs_type.edit_form",
 *   common_reference_revisions_target = TRUE,
 *   content_translation_ui_skip = TRUE,
 *   render_cache = FALSE,
 *   default_reference_revision_settings = {
 *     "field_storage_config" = {
 *       "cardinality" = -1,
 *       "settings" = {
 *         "target_type" = "paragraph"
 *       }
 *     },
 *     "field_config" = {
 *       "settings" = {
 *         "handler" = "default:paragraph"
 *       }
 *     },
 *     "entity_form_display" = {
 *       "type" = "paragraphs"
 *     },
 *     "entity_view_display" = {
 *       "type" = "entity_reference_revisions_entity_view"
 *     }
 *   },
 *   serialized_field_property_names = {
 *     "behavior_settings" = {
 *       "value"
 *     }
 *   }
 * )
 */
#[ContentEntityType(
  id: 'paragraph',
  label: new TranslatableMarkup('Paragraph'),
  label_collection: new TranslatableMarkup('Paragraphs'),
  label_singular: new TranslatableMarkup('Paragraph'),
  label_plural: new TranslatableMarkup('Paragraphs'),
  render_cache: FALSE,
  entity_keys: [
    'id' => 'id',
    'uuid' => 'uuid',
    'bundle' => 'type',
    'langcode' => 'langcode',
    'revision' => 'revision_id',
    'published' => 'status',
  ],
  handlers: [
    'view_builder' => ParagraphViewBuilder::class,
    'access' => ParagraphAccessControlHandler::class,
    'storage_schema' => ParagraphStorageSchema::class,
    'form' => [
      'default' => ContentEntityForm::class,
      'delete' => ContentEntityDeleteForm::class,
      'edit' => ContentEntityForm::class,
    ],
    'views_data' => EntityViewsData::class,
  ],
  bundle_entity_type: 'paragraphs_type',
  bundle_label: new TranslatableMarkup('Paragraph type'),
  base_table: 'paragraphs_item',
  data_table: 'paragraphs_item_field_data',
  revision_table: 'paragraphs_item_revision',
  revision_data_table: 'paragraphs_item_revision_field_data',
  translatable: TRUE,
  label_count: [
    'singular' => '@count Paragraph',
    'plural' => '@count Paragraphs',
  ],
  field_ui_base_route: 'entity.paragraphs_type.edit_form',
  additional: [
    'content_translation_ui_skip' => TRUE,
    'entity_revision_parent_type_field' => 'parent_type',
    'entity_revision_parent_id_field' => 'parent_id',
    'entity_revision_parent_field_name_field' => 'parent_field_name',
    'common_reference_revisions_target' => TRUE,
    'default_reference_revision_settings' => [
      'field_storage_config' => [
        'cardinality' => -1,
          'settings' => [
          'target_type' => 'paragraph'
        ],
      ],
      'field_config' => [
      'settings' => [
        'handler' => 'default:paragraph'
        ],
      ],
      'entity_form_display' => [
      'type' => 'paragraphs'
      ],
      'entity_view_display' => [
      'type' => 'entity_reference_revisions_entity_view'
      ],
    ],
    'serialized_field_property_names' => [
    'behavior_settings' => [
      'value'
      ],
    ],
  ]
)]
class Paragraph extends ContentEntityBase implements ParagraphInterface {

  use EntityNeedsSaveTrait;
  use EntityPublishedTrait;
  use StringTranslationTrait;

  /**
   * The behavior plugin data for the paragraph entity.
   *
   * @var array
   */
  protected $unserializedBehaviorSettings;

  /**
   * Number of summaries.
   *
   * @var int
   */
  protected $summaryCount;

  /**
   * {@inheritdoc}
   */
  public function getParentEntity() {
    if (!isset($this->get('parent_type')->value) || !isset($this->get('parent_id')->value)) {
      return NULL;
    }

    $entityTypeManager = \Drupal::entityTypeManager();
    if ($entityTypeManager->hasDefinition($this->get('parent_type')->value)) {
      $parent = $entityTypeManager
        ->getStorage($this->get('parent_type')->value)
        ->load($this->get('parent_id')->value);
    }

    // Return current translation of parent entity, if it exists.
    if ($parent != NULL && ($parent instanceof TranslatableInterface) && $parent->hasTranslation($this->language()->getId())) {
      return $parent->getTranslation($this->language()->getId());
    }

    return $parent;
  }

  /**
   * {@inheritdoc}
   */
  public function setParentEntity(ContentEntityInterface $parent, $parent_field_name) {
    $this->set('parent_type', $parent->getEntityTypeId());
    $this->set('parent_id', $parent->id());
    $this->set('parent_field_name', $parent_field_name);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    if (($parent = $this->getParentEntity()) && $parent->hasField($this->get('parent_field_name')->value)) {
      $parent_field = $this->get('parent_field_name')->value;
      $field = $parent->get($parent_field);
      $label = $parent->label() . ' > ' . $field->getFieldDefinition()->getLabel();
      // A previous or draft revision or a deleted stale Paragraph.
      $postfix = ' (previous revision)';
      foreach ($field as $value) {
        if ($value->entity && $value->entity->getRevisionId() == $this->getRevisionId()) {
          $postfix = '';
          break;
        }
      }
      if ($postfix) {
        $label .= $postfix;
      }
    }
    else {
      $label = $this->t('Orphaned @type: @summary', ['@summary' => Unicode::truncate(strip_tags($this->getSummary()), 50, FALSE, TRUE), '@type' => $this->get('type')->entity->label()]);
    }
    return $label;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    parent::preSave($storage);

    // If behavior settings are not set then get them from the entity.
    if ($this->unserializedBehaviorSettings !== NULL) {
      $this->set('behavior_settings', serialize($this->unserializedBehaviorSettings));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getAllBehaviorSettings() {
    if ($this->unserializedBehaviorSettings === NULL) {
      $this->unserializedBehaviorSettings = unserialize($this->get('behavior_settings')->value ?? '');
     }
    if (!is_array($this->unserializedBehaviorSettings)) {
      $this->unserializedBehaviorSettings = [];
    }
    return $this->unserializedBehaviorSettings;
  }

  /**
   * {@inheritdoc}
   */
  public function &getBehaviorSetting($plugin_id, $key, $default = NULL) {
    $settings = $this->getAllBehaviorSettings();
    $exists = NULL;
    $value = &NestedArray::getValue($settings, array_merge((array) $plugin_id, (array) $key), $exists);
    if (!$exists) {
      $value = $default;
    }
    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function setAllBehaviorSettings(array $settings) {
    // Set behavior settings fields.
    $this->unserializedBehaviorSettings = $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function setBehaviorSettings($plugin_id, array $settings) {
    // Get existing behaviors first.
    $this->getAllBehaviorSettings();
    // Set behavior settings fields.
    $this->unserializedBehaviorSettings[$plugin_id] = $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    $this->setNeedsSave(FALSE);
    parent::postSave($storage, $update);
  }

  /**
   * {@inheritdoc}
   */
  public function preSaveRevision(EntityStorageInterface $storage, \stdClass $record) {
    parent::preSaveRevision($storage, $record);
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated Paragraphs no longer have their own author,
   *  check the parent entity instead.
   */
  public function getOwner() {
    $parent = $this->getParentEntity();
    if ($parent instanceof EntityOwnerInterface) {
      return $parent->getOwner();
    }
    else {
      return NULL;
    }
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated Paragraphs no longer have their own author,
   *  check the parent entity instead.
   */
  public function getOwnerId() {
    $parent = $this->getParentEntity();
    if ($parent instanceof EntityOwnerInterface) {
      return $parent->getOwnerId();
    }
    else {
      return NULL;
    }
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated Paragraphs no longer have their own author,
   *  check the parent entity instead.
   */
  public function setOwnerId($uid) {
    return $this;
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated Paragraphs no longer have their own author,
   *  check the parent entity instead.
   */
  public function setOwner(UserInterface $account) {
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return $this->bundle();
  }

  /**
   * {@inheritdoc}
   */
  public function getParagraphType() {
    return $this->type->entity;
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated Paragraphs no longer have their own author,
   *  check the parent entity instead.
   */
  public function getRevisionAuthor() {
    $parent = $this->getParentEntity();

    if ($parent) {
      return $parent->get('revision_uid')->entity;
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   *
   * @deprecated Paragraphs no longer have their own author,
   *  check the parent entity instead.
   */
  public function setRevisionAuthorId($uid) {
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getRevisionLog() {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function setRevisionLog($revision_log) {
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['langcode'] = BaseFieldDefinition::create('language')
      ->setLabel(t('Language code'))
      ->setDescription(t('The paragraphs entity language code.'))
      ->setRevisionable(TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Published'))
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setDefaultValue(TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Authored on'))
      ->setDescription(t('The time that the Paragraph was created.'))
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setDisplayOptions('form', array(
        'region' => 'hidden',
        'weight' => 0,
      ))
      ->setDisplayConfigurable('form', TRUE);

    $fields['parent_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Parent ID'))
      ->setDescription(t('The ID of the parent entity of which this entity is referenced.'))
      ->setSetting('is_ascii', TRUE)
      ->setRevisionable(TRUE);

    $fields['parent_type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Parent type'))
      ->setDescription(t('The entity parent type to which this entity is referenced.'))
      ->setSetting('is_ascii', TRUE)
      ->setSetting('max_length', EntityTypeInterface::ID_MAX_LENGTH)
      ->setRevisionable(TRUE);

    $fields['parent_field_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Parent field name'))
      ->setDescription(t('The entity parent field name to which this entity is referenced.'))
      ->setSetting('is_ascii', TRUE)
      ->setSetting('max_length', FieldStorageConfig::NAME_MAX_LENGTH)
      ->setRevisionable(TRUE);

    $fields['behavior_settings'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Behavior settings'))
      ->setDescription(t('The behavior plugin settings'))
      ->setRevisionable(TRUE)
      ->setDefaultValue(serialize([]));

    return $fields;
  }

  /**
  * {@inheritdoc}
  */
 public function createDuplicate() {
   $duplicate = parent::createDuplicate();
   // Loop over entity fields and duplicate nested paragraphs.
   foreach ($duplicate->getFields() as $fieldItemList) {
     if ($fieldItemList instanceof EntityReferenceFieldItemListInterface && $fieldItemList->getSetting('target_type') === $this->getEntityTypeId()) {
       foreach ($fieldItemList as $delta => $item) {
         // Duplicate child paragraphs, remove when requiring 10.2+.
         // @see \Drupal\paragraphs\Hook\EntityHooks::duplicate()
         if ($item->entity && !$item->entity->isNew()) {
           $fieldItemList[$delta] = $item->entity->createDuplicate();
         }
       }
     }
   }
   return $duplicate;
 }

  /**
   * {@inheritdoc}
   */
  public function getSummary(array $options = []) {
    $summary_items = $this->getSummaryItems($options);
    $summary = [
      '#theme' => 'paragraphs_summary',
      '#summary' => $summary_items,
      '#expanded' => isset($options['expanded']) ? $options['expanded'] : FALSE,
    ];

    return \Drupal::service('renderer')->renderPlain($summary);
  }

  /**
   * {@inheritdoc}
   */
  public function getSummaryItems(array $options = []) {
    $summary = ['content' => [], 'behaviors' => []];
    $show_behavior_summary = isset($options['show_behavior_summary']) ? $options['show_behavior_summary'] : TRUE;
    $depth_limit = isset($options['depth_limit']) ? $options['depth_limit'] : 1;

    // Add content summary items.
    $this->summaryCount = 0;
    $components = \Drupal::service('entity_display.repository')->getFormDisplay('paragraph', $this->getType())->getComponents();
    uasort($components, 'Drupal\Component\Utility\SortArray::sortByWeightElement');
    foreach (array_keys($components) as $field_name) {
      // Components can be extra fields, check if the field really exists.
      if (!$this->hasField($field_name)) {
        continue;
      }
      $field_definition = $this->getFieldDefinition($field_name);
      // We do not add content to the summary from base fields, skip them
      // keeps performance while building the paragraph summary.
      if (!($field_definition instanceof FieldConfigInterface) || !$this->get($field_name)->access('view')) {
        continue;
      }

      if ($field_definition->getType() == 'image' || $field_definition->getType() == 'file') {
        $file_summary = $this->getFileSummary($field_name);
        if ($file_summary != '') {
          $summary['content'][] = $file_summary;
        }
      }

      $text_summary = $this->getTextSummary($field_name, $field_definition);
      if ($text_summary != '') {
        $summary['content'][] = $text_summary;
      }

      if ($field_definition->getType() == 'entity_reference_revisions') {
        // Decrease the depth, since we are entering a nested paragraph.
        $nested_summary = $this->getNestedSummary($field_name, [
          'show_behavior_summary' => FALSE,
          'depth_limit' => $depth_limit - 1
        ]);
        $summary['content'] = array_merge($summary['content'], $nested_summary);
      }

      if ($field_definition->getType() === 'entity_reference') {
        $referenced_entities = $this->get($field_name)->referencedEntities();
        /** @var \Drupal\Core\Entity\EntityInterface[] $referenced_entities */
        foreach ($referenced_entities as $referenced_entity) {
          if ($referenced_entity->access('view label')) {
            // Switch to the entity translation in the current context.
            $entity = \Drupal::service('entity.repository')->getTranslationFromContext($referenced_entity, $this->language()->getId());
            $summary['content'][] = $entity->label();
          }
        }
      }

      // Add the Block admin label referenced by block_field.
      if ($field_definition->getType() == 'block_field') {
        if (!empty($this->get($field_name)->first())) {
          if ($block = $block_admin_label = $this->get($field_name)->first()->getBlock()) {
            $block_admin_label = $block->getPluginDefinition()['admin_label'];
          }
          $summary['content'][] = $block_admin_label;
        }
      }

      if ($field_definition->getType() == 'link') {
        if (!empty($this->get($field_name)->first())) {
          // If title is not set, fallback to the uri.
          if ($title = $this->get($field_name)->title) {
            $summary['content'][] = $title;
          }
          else {
            $summary['content'][] = $this->get($field_name)->uri;
          }
        }
      }
    }

    // Add behaviors summary items.
    if ($show_behavior_summary) {
      $paragraphs_type = $this->getParagraphType();
      foreach ($paragraphs_type->getEnabledBehaviorPlugins() as $plugin) {
        if ($plugin_summary = $plugin->settingsSummary($this)) {
          foreach ($plugin_summary as $plugin_summary_element) {
            if (!is_array($plugin_summary_element)) {
              $plugin_summary_element = ['value' => $plugin_summary_element];
            }
            $summary['behaviors'][] = $plugin_summary_element;
          }
        }
      }
    }

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function getIcons(array $options = []) {
    $show_behavior_info = isset($options['show_behavior_icon']) ? $options['show_behavior_icon'] : TRUE;
    $icons = [];

    // For now we depend here on the fact that summaryCount is already
    // initialized. That means that getSummary() should be called before
    // getIcons().
    // @todo - should we fix this dependency somehow?
    if ($this->summaryCount) {
      $icons['count'] = [
        '#markup' => $this->summaryCount,
        '#prefix' => '<span class="paragraphs-badge" title="' . (string) \Drupal::translation()->formatPlural($this->summaryCount, '1 child', '@count children') . '">',
        '#suffix' => '</span>',
      ];
    }

    if ($show_behavior_info) {
      $paragraphs_type = $this->getParagraphType();
      foreach ($paragraphs_type->getEnabledBehaviorPlugins() as $plugin) {
        if ($plugin_info = $plugin->settingsIcon($this)) {
          $icons = array_merge($icons, $plugin_info);
        }
      }
    }

    return $icons;
  }

  /**
   * Returns an array of field names to skip in ::isChanged.
   *
   * @return array
   *   An array of field names.
   */
  protected function getFieldsToSkipFromChangedCheck() {
    // A list of revision fields which should be skipped from the comparision.
    $fields = [
      $this->getEntityType()->getKey('revision')
    ];

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function isChanged() {
    if ($this->isNew()) {
      return TRUE;
    }

    // $this->original only exists during save. If it exists we re-use it here
    // for performance reasons.
    /** @var \Drupal\paragraphs\ParagraphInterface $original */
    $original = $this->original ?: NULL;
    if (!$original) {
      $original = $this->entityTypeManager()->getStorage($this->getEntityTypeId())->loadRevision($this->getLoadedRevisionId());
    }

    // If the current revision has just been added, we have a change.
    if ($original->isNewRevision()) {
      return TRUE;
    }

    // The list of fields to skip from the comparision.
    $skip_fields = $this->getFieldsToSkipFromChangedCheck();

    // Compare field item current values with the original ones to determine
    // whether we have changes. We skip also computed fields as comparing them
    // with their original values might not be possible or be meaningless.
    foreach ($this->getFieldDefinitions() as $field_name => $definition) {
      if (in_array($field_name, $skip_fields, TRUE)) {
        continue;
      }
      $field = $this->get($field_name);
      // When saving entities in the user interface, the changed timestamp is
      // automatically incremented by ContentEntityForm::submitForm() even if
      // nothing was actually changed. Thus, the changed time needs to be
      // ignored when determining whether there are any actual changes in the
      // entity.
      if (!($field instanceof ChangedFieldItemList) && !$definition->isComputed()) {
        $items = $field->filterEmptyItems();
        $original_items = $original->get($field_name)->filterEmptyItems();
        if (!$items->equals($original_items)) {
          return TRUE;
        }
      }
    }

    return FALSE;
  }

  /**
   * Returns summary for file paragraph.
   *
   * @param string $field_name
   *   Field name from field definition.
   *
   * @return string
   *   Summary for image.
   */
  protected function getFileSummary($field_name) {
    $summary = '';
    if ($this->get($field_name)->entity) {
      foreach ($this->get($field_name) as $file_value) {

        $text = '';
        if ($file_value->description != '') {
          $text = $file_value->description;
        }
        elseif ($file_value->title != '') {
          $text = $file_value->title;
        }
        elseif ($file_value->alt != '') {
          $text = $file_value->alt;
        }
        elseif ($file_value->entity && $file_value->entity->getFileName()) {
          $text = $file_value->entity->getFileName();
        }

        if (strlen($text) > 150) {
          $text = Unicode::truncate($text, 150);
        }

        $summary = $text;
      }
    }

    return trim($summary);
  }

  /**
   * Returns summary items for nested paragraphs.
   *
   * @param string $field_name
   *   Field definition id for paragraph.
   * @param array $options
   *   (optional) An associative array of additional options.
   *   See \Drupal\paragraphs\ParagraphInterface::getSummary() for all of the
   *   available options.
   *
   * @return array
   *   List of content summary items for nested elements.
   */
  protected function getNestedSummary($field_name, array $options) {
    $summary_content = [];
    if ($options['depth_limit'] >= 0) {
      foreach ($this->get($field_name) as $item) {
        $entity = $item->entity;
        if ($entity instanceof ParagraphInterface) {
          // Switch to the entity translation in the current context if exists.
          $entity = \Drupal::service('entity.repository')->getTranslationFromContext($entity, $this->language()->getId());
          $content_summary_items = $entity->getSummaryItems($options)['content'];
          $summary_content = array_merge($summary_content, array_values($content_summary_items));
          $this->summaryCount++;
        }
      }
    }

    return $summary_content;
  }

  /**
   * Returns summary for all text type paragraph.
   *
   * @param string $field_name
   *   Field definition id for paragraph.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   Field definition for paragraph.
   *
   * @return string
   *   Short summary for text paragraph.
   */
  public function getTextSummary($field_name, FieldDefinitionInterface $field_definition) {
    $text_types = [
      'text_with_summary',
      'text',
      'text_long',
      'list_string',
      'string',
    ];

    $excluded_text_types = [
      'parent_id',
      'parent_type',
      'parent_field_name',
    ];

    $summary = '';
    if (in_array($field_definition->getType(), $text_types)) {
      if (in_array($field_name, $excluded_text_types)) {
        return '';
      }

      $text = $this->get($field_name)->value ?? '';
      $summary = Unicode::truncate(trim(html_entity_decode(strip_tags($text))), 150);
      if (empty($summary)) {
        // Autoescape is applied to the summary when it is rendered with twig,
        // make it a Markup object so HTML tags are displayed correctly.
        $summary = Markup::create(Unicode::truncate(htmlspecialchars(trim($text)), 150));
      }
    }

    return $summary;
  }
}
