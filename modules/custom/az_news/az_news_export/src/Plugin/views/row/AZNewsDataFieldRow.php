<?php

namespace Drupal\az_news_export\Plugin\views\row;

use Drupal\az_news_export\AZNewsDataEmpty;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\file\FileInterface;
use Drupal\media\MediaInterface;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\rest\Plugin\views\row\DataFieldRow;
use Drupal\taxonomy\TermInterface;
use Drupal\views\Attribute\ViewsRow;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin which displays fields as raw data.
 *
 * @ingroup views_row_plugins
 */
#[ViewsRow(
  id: "az_news_data_field",
  title: new TranslatableMarkup("Quickstart News Fields"),
  help: new TranslatableMarkup("Use News fields as row data."),
  display_types: ["data"]
)]
class AZNewsDataFieldRow extends DataFieldRow {

  /**
   * Drupal\Core\Entity\EntityFieldManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Drupal\Core\Utility\Token definition.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * A static list of the types of referenced entities that can be serialized.
   *
   * @var string[]
   */
  protected static $serializableReferencedEntityTypes = [
    'media',
    'taxonomy_term',
    'paragraph',
    'file',
  ];

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition,
  ) {
    $instance = parent::create(
      $container,
      $configuration,
      $plugin_id,
      $plugin_definition,
    );
    $instance->entityFieldManager = $container->get('entity_field.manager');
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->token = $container->get('token');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function render($row) {
    $output = [];
    $entity = $row->_entity;
    $entity_type = $entity->getEntityTypeId();
    $entity_bundle = $entity->bundle();
    $field_definitions = $this->entityFieldManager->getFieldDefinitions($entity_type, $entity_bundle);
    foreach ($this->view->field as $field_name => $field) {
      $value = !empty($this->rawOutputOptions[$field_name]) ?
        $field->getValue($row) :
        $field->advancedRender($row);
      if (empty($value) || !isset($field_definitions[$field_name])) {
        continue;
      }
      $field_definition = $field_definitions[$field_name];
      foreach (self::$serializableReferencedEntityTypes as $target_type) {
        if ($this->isReferenceFieldOfType($field_definition, $target_type)) {
          $value = $this->serializeReferenceField($value, $target_type);
        }
      }
      if ($this->isTextField($field_definition)) {
        $value = $this->processTokens($value, $entity);
      }
      if (empty($field->options['exclude'])) {
        $output[$this->getFieldKeyAlias($field_name)] = $value;
      }
    }

    return $output;
  }

  /**
   * Determines whether a field is a text field.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition.
   *
   * @return bool
   *   TRUE if the field is a text field, FALSE otherwise.
   */
  protected function isTextField(FieldDefinitionInterface $field_definition): bool {
    return $field_definition->getType() === 'string';
  }

  /**
   * Performs token replacement for tokenized field values.
   *
   * @param mixed $value
   *   The field value.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity.
   *
   * @return string
   *   The processed field value.
   */
  protected function processTokens($value, EntityInterface $entity): string {
    $item = '';
    if (!empty($value)) {
      $token_data = ['node' => $entity];
      $token_options = ['clear' => TRUE];
      $item = $this->token->replacePlain($value, $token_data, $token_options);
    }
    return $item;
  }

  /**
   * Checks if the specified field is of a given reference type.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition.
   * @param string $target_type
   *   The target entity type.
   *
   * @return bool
   *   TRUE if the field references the specified type, FALSE otherwise.
   */
  protected function isReferenceFieldOfType(FieldDefinitionInterface $field_definition, string $target_type): bool {
    $reference_field_types = [
      'entity_reference',
      'entity_reference_revisions',
      'file',
    ];
    $field_type = $field_definition->getType();
    $field_target_type = $field_definition->getSetting('target_type');
    return in_array($field_type, $reference_field_types, TRUE) && $field_target_type === $target_type;
  }

  /**
   * Serializes entity reference fields into exportable values.
   *
   * @param mixed $value
   *   The field value(s), can be an array of entity IDs or a single entity ID.
   * @param string $target_type
   *   The type of entities the field references.
   *
   * @return array|\Drupal\az_news_export\AZNewsDataEmpty
   *   The serialized field value.
   */
  protected function serializeReferenceField($value, string $target_type) {
    $items = [];
    $values = is_array($value) ? $value : [$value];
    $values = array_filter($values, static fn($item) => !empty($item));
    if (!empty($values)) {
      $storage = $this->entityTypeManager->getStorage($target_type);
      $entities = $storage->loadMultiple($values);

      foreach ($entities as $referenced_entity) {
        if (!$referenced_entity->access('view')) {
          continue;
        }

        $item = [];
        switch ($target_type) {
          case 'media':
            if ($referenced_entity instanceof MediaInterface) {
              $media_type = $referenced_entity->bundle();
              $source = $referenced_entity->getSource();
              $fid = $source->getSourceFieldValue($referenced_entity);
              $file = $this->entityTypeManager->getStorage('file')->load($fid);
              if (!$file instanceof FileInterface) {
                break;
              }
              $item['fid'] = $file->id();
              $item['uuid'] = $file->uuid();
              $item['original'] = $file->createFileUrl(FALSE);

              if ($media_type === 'az_image') {
                $uri = $file->getFileUri();
                $styles = [
                  'thumbnail' => 'az_enterprise_thumbnail',
                  'thumbnail_small' => 'az_enterprise_thumbnail_small',
                ];
                foreach ($styles as $key => $style_id) {
                  $image_style = $this->entityTypeManager->getStorage('image_style')->load($style_id);
                  if (!empty($image_style)) {
                    $item[$key] = $image_style->buildUrl($uri);
                  }
                }
                if (!empty($referenced_entity->field_media_az_image->alt)) {
                  $item['alt'] = $referenced_entity->field_media_az_image->alt;
                }
              }
            }
            break;

          case 'paragraph':
            if ($referenced_entity instanceof ParagraphInterface) {
              $paragraph_type = $referenced_entity->bundle();
              if ($paragraph_type === 'az_contact') {
                $contact_fields = [
                  'field_az_email',
                  'field_az_title',
                  'field_az_phone',
                ];
                foreach ($contact_fields as $contact_field) {
                  if ($referenced_entity->hasField($contact_field) && !$referenced_entity->{$contact_field}->isEmpty()) {
                    $item[$contact_field] = $referenced_entity->{$contact_field}->value;
                  }
                }
              }
            }
            break;

          case 'file':
            if ($referenced_entity instanceof FileInterface) {
              $item = [
                'fid' => $referenced_entity->id(),
                'uuid' => $referenced_entity->uuid(),
                'original' => $referenced_entity->createFileUrl(FALSE),
              ];
            }
            break;

          case 'taxonomy_term':
            if ($referenced_entity instanceof TermInterface) {
              $item = $referenced_entity->label();
            }
            break;

          default:
            $item = $referenced_entity->label();
            break;
        }

        if (!empty($item)) {
          $items[] = $item;
        }
      }
    }

    if (empty($items) && in_array($target_type, self::$serializableReferencedEntityTypes, TRUE)) {
      $items = new AZNewsDataEmpty();
    }

    return $items;
  }

}
