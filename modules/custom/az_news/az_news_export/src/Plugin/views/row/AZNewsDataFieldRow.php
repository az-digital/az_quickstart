<?php

namespace Drupal\az_news_export\Plugin\views\row;

use Drupal\az_news_export\AZNewsDataEmpty;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\file\FileInterface;
use Drupal\media\MediaInterface;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\rest\Plugin\views\row\DataFieldRow;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin which displays fields as raw data.
 *
 * @ingroup views_row_plugins
 *
 * @ViewsRow(
 *   id = "az_news_data_field",
 *   title = @Translation("Quickstart News Fields"),
 *   help = @Translation("Use News fields as row data."),
 *   display_types = {"data"}
 * )
 */
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
   * A static list of the types of entities that can be serialized.
   *
   * @var array
   */
  protected static $serializableEntityTypes = [
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
    $plugin_definition
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
      $value = !empty($this->rawOutputOptions[$field_name]) ? $field->getValue($row) : $field->advancedRender($row);
      if (empty($value) || !isset($field_definitions[$field_name])) {
        continue;
      }
      $field_definition = $field_definitions[$field_name];
      foreach (self::$serializableEntityTypes as $target_type) {
        if ($this->isReferenceFieldOfType($field_definition, $target_type)) {
          $value = $this->serializeField($value, $target_type);
        }
      }
      if ($this->isTextField($field_definition)) {
        $value = $this->processTokens($value, $entity);
      }
      // Omit excluded fields from the rendered output.
      if (empty($field->options['exclude'])) {
        $output[$this->getFieldKeyAlias($field_name)] = $value;
      }
    }

    return $output;
  }

  /**
   * Check if is text field.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition.
   *
   * @return bool
   *   True if the field is a text field, false otherwise.
   */
  protected function isTextField($field_definition): bool {
    return $field_definition->getType() === 'string';
  }

  /**
   * Process short title field.
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
    $item = "";
    if (!empty($value)) {
      $token_data = ['node' => $entity];
      $token_options = ['clear' => TRUE];
      // Perform token replacement.
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
   *   True if the field is an entity reference field with the specified target
   *   type, false otherwise.
   */
  protected function isReferenceFieldOfType(FieldDefinitionInterface $field_definition, string $target_type): bool {
    $reference_field_types = [
      'entity_reference',
      'entity_reference_revisions',
      'file',
    ];
    $field_type = $field_definition->getType();
    $field_target_type = $field_definition->getSetting('target_type');
    return (in_array($field_type, $reference_field_types) && $field_target_type === $target_type);
  }

  /**
   * Generalized method to serialize fields.
   *
   * @param mixed $value
   *   The field value(s), can be an array of entity IDs or a single entity ID.
   * @param string $target_type
   *   The type of entities the field references
   *   ('media', 'taxonomy_term', etc.).
   *
   * @return array
   *   The serialized field value.
   */
  protected function serializeField($value, $target_type): array {
    $items = [];
    // Normalize $value to an array to simplify processing.
    $values = is_array($value) ? $value : [$value];
    if (!empty($values)) {
      $storage = $this->entityTypeManager->getStorage($target_type);
      $entities = $storage->loadMultiple($values);

      foreach ($entities as $referencedEntity) {
        if (!$referencedEntity->access('view')) {
          continue;
        }

        $item = [];
        switch ($target_type) {
          case 'media':
            if ($referencedEntity instanceof MediaInterface) {
              $media_type = $referencedEntity->bundle();
              $fid = $referencedEntity->getSource()->getSourceFieldValue($referencedEntity);
              $file = $this->entityTypeManager->getStorage('file')->load($fid);
              $item['fid'] = $file->id();
              $item['uuid'] = $file->uuid();
              switch ($media_type) {
                case 'az_image':
                  $item['original'] = $file->createFileUrl(FALSE);
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
                  if (!empty($referencedEntity->field_media_az_image->alt)) {
                    $item['alt'] = $referencedEntity->field_media_az_image->alt;
                  }
                  break;

                case 'az_document':
                  $item['original'] = $file->createFileUrl(FALSE);
                  break;

                case 'default':
                  $item['original'] = $file->createFileUrl(FALSE);
                  break;
              }
            }
            break;

          case 'paragraph':
            if ($referencedEntity instanceof ParagraphInterface) {
              $paragraph_type = $referencedEntity->bundle();
              switch ($paragraph_type) {
                case 'az_contact':
                  $contact_fields = [
                    'field_az_email',
                    'field_az_title',
                    'field_az_phone',
                  ];
                  foreach ($contact_fields as $contact_field) {
                    if ($referencedEntity->hasField($contact_field) && !empty($referencedEntity->{$contact_field}->value)) {
                      $item[$contact_field] = $referencedEntity->{$contact_field}->value;
                    }
                  }
                  break;

                case 'default':
                  break;
              }
            }
            break;

          case 'file':
            if ($referencedEntity instanceof FileInterface) {
              $item = $referencedEntity->createFileUrl(FALSE);
            }
            break;

          default:
            $item = $referencedEntity->label();
            break;
        }
        if (!empty($item)) {
          $items[] = $item;
        }
      }
    }

    // Provide a default case for empty items, if necessary, for specific types.
    if (empty($items) && in_array($target_type, self::$serializableEntityTypes)) {
      // Ensure this class or fallback is defined.
      $items = new AZNewsDataEmpty();
    }

    return $items;
  }

}
