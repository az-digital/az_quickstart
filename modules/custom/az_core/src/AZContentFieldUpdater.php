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
      'prefix' => NULL,
      'suffix' => NULL,
      'batch_size' => 20,
      'value_key' => 'value',
      'format_key' => 'format',
      'format_required' => TRUE,
      'allowed_formats' => ['az_standard', 'full_html'],
      'bundle_name' => NULL,
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
        $sandbox['skipped']++;
        $sandbox['progress']++;
        continue;
      }

      $needs_update = FALSE;
      $field = $entity->get($field_name);

      // Skip if field doesn't exist or is empty.
      if (!$field || $field->isEmpty()) {
        $sandbox['skipped']++;
        $sandbox['progress']++;
        continue;
      }

      // Single-value vs multi-value field handling.
      if ($field->getFieldDefinition()->getFieldStorageDefinition()->getCardinality() === 1) {
        // Single-value field.
        $field_item = $field->first();
        if (!$field_item) {
          $sandbox['skipped']++;
          $sandbox['progress']++;
          continue;
        }

        // Check format if required.
        if ($options['format_required']) {
          $format = $field_item->{$options['format_key']} ?? NULL;
          if (!in_array($format, $options['allowed_formats'])) {
            $sandbox['skipped']++;
            $sandbox['progress']++;
            continue;
          }
        }

        // Get current value using configured key.
        $original_value = $field_item->{$options['value_key']} ?? NULL;
        if (empty($original_value)) {
          $sandbox['skipped']++;
          $sandbox['progress']++;
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
            $message_args = [
              '@field' => $field_name,
              '@type' => $entity->getEntityTypeId(),
              '@id' => $entity->id(),
            ];
            if (!empty($options['bundle_name'])) {
              $message_args['@bundle'] = $options['bundle_name'];
              $message = $this->t('Updated field @field on @bundle @type @id', $message_args);
            }
            else {
              $message = $this->t('Updated field @field on @type @id', $message_args);
            }
            // Format and add prefix/suffix to revision log message.
            if ($options['prefix']) {
              $message = $options['prefix'] . ': ' . $message;
            }
            if ($options['suffix']) {
              $message .= ' (' . $options['suffix'] . ')';
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
              $message = $this->t('Updated child @bundle paragraph @pid (revision: @vid)', [
                '@bundle' => $entity->bundle(),
                '@pid' => $entity->id(),
                '@vid' => $entity->getRevisionId(),
              ]);
              // Format and add prefix/suffix to revision log message.
              if ($options['prefix']) {
                $message = $options['prefix'] . ': ' . $message;
              }
              if ($options['suffix']) {
                $message .= ' (' . $options['suffix'] . ')';
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
          '@bundle' => $entity->bundle(),
          '@id' => $entity->id(),
          '@vid' => $entity->getRevisionId(),
        ];
        $message = 'Updated @bundle paragraph @id (revision: @vid)';

        // Add parent entity information for paragraphs.
        if ($entity instanceof ParagraphInterface && ($parent = $entity->getParentEntity())) {
          $message .= ' and parent @parent_type @parent_id';
          $context['@parent_type'] = $parent->bundle();
          $context['@parent_id'] = $parent->id();
        }

        // Format and add suffix to logger message if provided.
        if ($options['suffix']) {
          $message .= ' (' . $options['suffix'] . ')';
        }

        $logger->notice($message, $context);

        $sandbox['updated_count']++;
      }

      $sandbox['progress']++;
    }

    $sandbox['#finished'] = empty($sandbox['max']) ? 1 : ($sandbox['progress'] / $sandbox['max']);

    if ($sandbox['#finished'] >= 1) {
      // Get the entity type definition for better labels.
      $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);
      $type_label = mb_strtolower($entity_type->getPluralLabel());

      $message_args = [
        '@count' => $sandbox['progress'],
        '@type' => $type_label,
        '@updated' => $sandbox['updated_count'],
        '@skipped' => $sandbox['skipped_count'],
      ];

      if (!empty($options['bundle_name'])) {
        $message_args['@bundle'] = $options['bundle_name'];
        $message = $this->t('Processed @count @bundle @type. @updated @type updated. @skipped unused @type skipped.', $message_args);
      }
      else {
        $message = $this->t('Processed @count @type. @updated @type updated. @skipped unused @type skipped.', $message_args);
      }

      // Log the summary message at notice level.
      $logger->notice($message);

      return $message;
    }

    return NULL;
  }

}
