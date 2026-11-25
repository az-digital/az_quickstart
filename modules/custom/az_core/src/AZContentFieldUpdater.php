<?php

namespace Drupal\az_core;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Entity\RevisionableStorageInterface;
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
      'bundle_name' => '',
      'prefix' => '',
      'suffix' => '',
      'batch_size' => 50,
      'value_key' => 'value',
      'format_key' => 'format',
      'format_required' => FALSE,
      'allowed_formats' => ['az_standard', 'full_html'],
    ];

    if (!isset($sandbox['progress'])) {
      $sandbox['progress'] = 0;
      $sandbox['updated_count'] = 0;
      $sandbox['skipped'] = 0;
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

      // Check if the field exists on this entity.
      if (!$entity->hasField($field_name)) {
        $logger->warning("Field @field does not exist on @type @id (@bundle)", [
          '@field' => $field_name,
          '@type' => $entity_type_id,
          '@id' => $id,
          '@bundle' => $entity->bundle(),
        ]);
        $sandbox['skipped']++;
        $sandbox['progress']++;
        continue;
      }

      // For a paragraph, update the copy that its parent's current revision
      // shows, and skip a paragraph that the parent doesn't show. Without
      // this, a draft's edits can go live: a revert to a draft revision makes
      // the draft's copy of the paragraph its current revision, and the code
      // below points the parent's current revision at the copy it saves.
      if ($options['create_revisions'] && $entity instanceof ParagraphInterface) {
        $revision_id = $this->getUsedRevisionId($entity);
        if ($revision_id === NULL) {
          $logger->notice("Skipped paragraph @id, because the current revision of its parent doesn't use it", [
            '@id' => $id,
          ]);
          $sandbox['skipped']++;
          $sandbox['progress']++;
          continue;
        }
        if ($revision_id !== (int) $entity->getRevisionId()) {
          assert($storage instanceof RevisionableStorageInterface);
          /** @var \Drupal\Core\Entity\ContentEntityInterface|null $entity */
          $entity = $storage->loadRevision($revision_id);
          if (!$entity) {
            $logger->warning("Could not load revision @vid of paragraph @id", [
              '@vid' => $revision_id,
              '@id' => $id,
            ]);
            $sandbox['skipped']++;
            $sandbox['progress']++;
            continue;
          }
        }
      }

      // Check all languages that exist for entity.
      foreach ($entity->getTranslationLanguages() as $langcode => $language) {
        $entity = $entity->getTranslation($langcode);

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
              foreach ($field_item->getProperties() as $property_name => $property) {
                $new_value[$property_name] = ($property_name === $options['value_key']) ? $processed_value : $property->getValue();
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
                '@language' => $language->getName(),
              ];
              if (!empty($options['bundle_name'])) {
                $message_args['@bundle'] = $options['bundle_name'];
                $message = $this->t('Updated field @field on @bundle @type @id for @language language', $message_args);
              }
              else {
                $message = $this->t('Updated field @field on @type @id for @language language', $message_args);
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
          $parent_bundle = '';
          $parent_id = '';
          if ($options['create_revisions'] &&
              $entity instanceof ParagraphInterface &&
              $parent = $entity->getParentEntity()) {
            if ($parent instanceof RevisionableInterface) {
              // Load fresh version of parent.
              $parent_storage = $this->entityTypeManager->getStorage($parent->getEntityTypeId());
              /** @var \Drupal\Core\Entity\ContentEntityInterface $parent */
              $parent = $parent_storage->load($parent->id());

              if (!$parent->hasTranslation($langcode)) {
                $logger->warning("Parent @bundle @type @id for @pbundle paragraph @pid does not have a translation for the @language language", [
                  '@bundle' => $parent->bundle(),
                  '@type' => $parent->getEntityTypeId(),
                  '@id' => $parent->id(),
                  '@pbundle' => $entity->bundle(),
                  '@pid' => $entity->id(),
                  '@language' => $language->getName(),
                ]);
              }
              else {
                $parent = $parent->getTranslation($langcode);

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
                  $message = $this->t('Updated child @bundle paragraph @pid (revision: @vid) for @language language', [
                    '@bundle' => $entity->bundle(),
                    '@pid' => $entity->id(),
                    '@vid' => $entity->getRevisionId(),
                    '@language' => $language->getName(),
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

                // Make parent info available for log messages.
                $parent_bundle = $parent->bundle();
                $parent_id = $parent->id();

                // Save the parent entity.
                $parent->save();
              }
            }
          }

          // Build detailed log message.
          $context = [
            '@bundle' => $entity->bundle(),
            '@type' => $entity->getEntityTypeId(),
            '@id' => $entity->id(),
            '@vid' => $entity->getRevisionId(),
            '@language' => $language->getName(),
          ];
          $message = 'Updated @bundle @type @id (revision: @vid) for @language language';

          // Add parent entity information for paragraphs.
          if (!empty($parent_bundle) && !empty($parent_id)) {
            $message .= ' and parent @parent_bundle @parent_id';
            $context['@parent_bundle'] = $parent_bundle;
            $context['@parent_id'] = $parent_id;
          }

          // Format and add suffix to logger message if provided.
          if ($options['suffix']) {
            $message .= ' (' . $options['suffix'] . ')';
          }

          $logger->notice($message, $context);

          $sandbox['updated_count']++;
        }

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
      ];

      if (!empty($options['bundle_name'])) {
        $message_args['@bundle'] = $options['bundle_name'];
        $message = $this->t('Processed @count @bundle @type. @updated @type updated.', $message_args);
      }
      else {
        $message = $this->t('Processed @count @type. @updated @type updated.', $message_args);
      }

      return $message;
    }

    return NULL;
  }

  /**
   * Returns the paragraph revision that its parent's current revision uses.
   *
   * For example, a published page with a newer draft shows its own copy of
   * each paragraph. The paragraph's current revision can be the draft's copy.
   *
   * @param \Drupal\paragraphs\ParagraphInterface $paragraph
   *   The current revision of the paragraph.
   *
   * @return int|null
   *   The paragraph revision ID, or NULL if the paragraph has no parent or the
   *   parent's current revision doesn't use it.
   */
  protected function getUsedRevisionId(ParagraphInterface $paragraph): ?int {
    $parent = $paragraph->getParentEntity();
    $parent_field = $paragraph->get('parent_field_name')->value;
    if (!$parent instanceof ContentEntityInterface || !$parent_field || !$parent->hasField($parent_field)) {
      return NULL;
    }
    $revision_ids = [];
    foreach (array_keys($parent->getTranslationLanguages()) as $langcode) {
      /** @var \Drupal\entity_reference_revisions\Plugin\Field\FieldType\EntityReferenceRevisionsItem $item */
      foreach ($parent->getTranslation($langcode)->get($parent_field) as $item) {
        if ($item->target_id == $paragraph->id()) {
          $revision_ids[] = (int) $item->get('target_revision_id')->getValue();
        }
      }
    }
    // If the parent uses the paragraph's current revision, as it does for most
    // paragraphs, keep it. Then nothing changes for those paragraphs.
    if (in_array((int) $paragraph->getRevisionId(), $revision_ids, TRUE)) {
      return (int) $paragraph->getRevisionId();
    }
    return $revision_ids[0] ?? NULL;
  }

}
