<?php

namespace Drupal\az_core;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Entity\RevisionLogInterface;
use Drupal\Core\Entity\TranslatableRevisionableInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\paragraphs\ParagraphInterface;

/**
 * Service for updating content field values.
 */
final class AZContentFieldUpdater implements AZContentFieldUpdaterInterface {

  use StringTranslationTrait;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * Constructs a new ContentFieldUpdater.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    LoggerChannelFactoryInterface $logger_factory,
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->loggerFactory = $logger_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function processFieldUpdates(
    string $entity_type_id,
    string $field_name,
    callable $processor,
    array &$sandbox,
    array $options = [],
  ): ?string {
    // Set default options.
    $options += [
      'create_revisions' => FALSE,
      'description' => NULL,
      'batch_size' => 20,
      'value_key' => 'value',
      'format_key' => 'format',
      'format_required' => TRUE,
      'allowed_formats' => ['az_standard', 'full_html'],
      'conditions' => [],
    ];

    if (!isset($sandbox['progress'])) {
      $sandbox['progress'] = 0;
      $sandbox['updated_count'] = 0;
      $sandbox['skipped_count'] = 0;
    }

    $storage = $this->entityTypeManager->getStorage($entity_type_id);
    $logger = $this->loggerFactory->get('az_core');

    // Process entities in batches.
    $ids = array_slice($sandbox['ids'], $sandbox['progress'], $options['batch_size']);

    foreach ($ids as $id) {
      /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
      $entity = $storage->load($id);
      if (!$entity) {
        $logger->warning("Could not load @type @id", [
          '@type' => $entity_type_id,
          '@id' => $id,
        ]);
        $sandbox['skipped_count']++;
        $sandbox['progress']++;
        continue;
      }

      $needs_update = FALSE;
      $field = $entity->get($field_name);

      // Skip if field doesn't exist or is empty.
      if (!$field || $field->isEmpty()) {
        continue;
      }

      // Single-value vs multi-value field handling.
      if ($field->getFieldDefinition()->getFieldStorageDefinition()->getCardinality() === 1) {
        // Single-value field.
        $field_item = $field->first();
        if (!$field_item) {
          continue;
        }

        // Check format if required.
        if ($options['format_required']) {
          $format = $field_item->{$options['format_key']} ?? NULL;
          if (!in_array($format, $options['allowed_formats'])) {
            continue;
          }
        }

        // Check additional conditions.
        $meets_conditions = TRUE;
        foreach ($options['conditions'] as $property => $allowed_values) {
          if (!isset($field_item->$property) || !in_array($field_item->$property, $allowed_values)) {
            $meets_conditions = FALSE;
            break;
          }
        }

        if (!$meets_conditions) {
          continue;
        }

        // Get current value using configured key.
        $original_value = $field_item->{$options['value_key']} ?? NULL;
        if (empty($original_value)) {
          continue;
        }

        // Process the value.
        $processed_value = $processor($original_value);
        if ($processed_value !== $original_value) {
          $field_item->{$options['value_key']} = $processed_value;
          $needs_update = TRUE;
        }
      }
      else {
        // Multi-value field.
        foreach ($field as $delta => $field_item) {
          // Check format if required.
          if ($options['format_required']) {
            $format = $field_item->{$options['format_key']} ?? NULL;
            if (!in_array($format, $options['allowed_formats'])) {
              continue;
            }
          }

          // Check additional conditions.
          $meets_conditions = TRUE;
          foreach ($options['conditions'] as $property => $allowed_values) {
            if (!isset($field_item->$property) || !in_array($field_item->$property, $allowed_values)) {
              $meets_conditions = FALSE;
              break;
            }
          }

          if (!$meets_conditions) {
            continue;
          }

          // Get current value using configured key.
          $original_value = $field_item->{$options['value_key']} ?? NULL;
          if (empty($original_value)) {
            continue;
          }

          // Process the value.
          $processed_value = $processor($original_value);
          if ($processed_value !== $original_value) {
            // For multi-value fields we need to preserve all properties.
            $new_value = [];
            foreach ($field_item->getProperties() as $property => $value) {
              $new_value[$property] = ($property === $options['value_key']) ? $processed_value : $value;
            }
            $field->get($delta)->setValue($new_value);
            $needs_update = TRUE;
          }
        }
      }

      if ($needs_update) {
        // Create new revision if requested.
        if ($options['create_revisions'] && $entity instanceof RevisionableInterface) {
          $entity->setNewRevision(TRUE);
          $entity->isDefaultRevision(TRUE);

          // Add revision metadata for non-paragraph entities.
          if ($entity instanceof RevisionLogInterface && !$entity instanceof ParagraphInterface) {
            $time = \Drupal::time()->getRequestTime();
            $entity->setRevisionCreationTime($time);
            $entity->setRevisionUserId(1);
            $message = $this->stringTranslation->translate('Updated field @field on @bundle @type @id', [
              '@field' => $field_name,
              '@bundle' => $entity->bundle(),
              '@type' => $entity->getEntityTypeId(),
              '@id' => $entity->id(),
            ]);
            if ($options['description']) {
              $message .= ': ' . $options['description'];
            }
            $entity->setRevisionLogMessage($message);
          }
        }

        // Save the entity.
        $entity->save();

        // Handle parent entity revisions for paragraphs.
        if ($options['create_revisions'] &&
            $entity instanceof ParagraphInterface &&
            $parent = $entity->getParentEntity()) {
          if ($parent instanceof RevisionableInterface) {
            // Load fresh version of parent.
            $parent_storage = $this->entityTypeManager->getStorage($parent->getEntityTypeId());
            /** @var \Drupal\Core\Entity\ContentEntityInterface $parent */
            $parent = $parent_storage->load($parent->id());

            // Create new revision.
            $parent->setNewRevision(TRUE);
            $parent->isDefaultRevision(TRUE);

            // Update revision metadata.
            if ($parent instanceof RevisionLogInterface && !$parent instanceof ParagraphInterface) {
              $time = \Drupal::time()->getRequestTime();
              $parent->setRevisionCreationTime($time);
              $parent->setRevisionUserId(1);
              if ($parent instanceof TranslatableRevisionableInterface) {
                $parent->setRevisionTranslationAffected(TRUE);
              }
              $message = $this->stringTranslation->translate('Updated child paragraph @pid (revision: @vid)', [
                '@pid' => $entity->id(),
                '@vid' => $entity->getRevisionId(),
              ]);
              if ($options['description']) {
                $message .= ': ' . $options['description'];
              }
              $parent->setRevisionLogMessage($message);
            }

            // Update parent's reference to the paragraph.
            $parent_field = $entity->get('parent_field_name')->value;
            $parent_items = $parent->get($parent_field);
            /** @var \Drupal\entity_reference_revisions\Plugin\Field\FieldType\EntityReferenceRevisionsItem $item */
            foreach ($parent_items as $delta => $item) {
              if ($item->target_id == $entity->id()) {
                $parent_items->get($delta)->setValue([
                  'target_id' => $entity->id(),
                  'target_revision_id' => $entity->getRevisionId(),
                ]);
                break;
              }
            }

            // Save the parent entity.
            $parent->save();
          }
        }

        // Build detailed log message.
        $context = [
          '@type' => $entity->bundle(),
          '@id' => $entity->id(),
          '@vid' => $entity->getRevisionId(),
        ];
        $message = 'Updated @type paragraph @id (revision: @vid)';

        // Add parent entity information for paragraphs.
        if ($entity instanceof ParagraphInterface && ($parent = $entity->getParentEntity())) {
          $message .= ' and parent @parent_type @parent_id';
          $context['@parent_type'] = $parent->bundle();
          $context['@parent_id'] = $parent->id();
        }

        $logger->info($message, $context);

        $sandbox['updated_count']++;
      }

      $sandbox['progress']++;
    }

    $sandbox['#finished'] = empty($sandbox['max']) ? 1 : ($sandbox['progress'] / $sandbox['max']);

    if ($sandbox['#finished'] >= 1) {
      return $this->t('Processed @count total entities, updated @updated, skipped @skipped.', [
        '@count' => $sandbox['progress'],
        '@updated' => $sandbox['updated_count'],
        '@skipped' => $sandbox['skipped_count'],
      ]);
    }

    return NULL;
  }

}
