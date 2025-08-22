<?php

namespace Drupal\az_core;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Entity\RevisionLogInterface;
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
    ];

    if (!isset($sandbox['progress'])) {
      $sandbox['progress'] = 0;
      $sandbox['updated_count'] = 0;
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
        continue;
      }

      $needs_update = FALSE;
      $field = $entity->get($field_name);
      $original_value = $field->value;

      if (!empty($original_value)) {
        $processed_value = $processor($original_value);
        if ($processed_value !== $original_value) {
          if (isset($field->format)) {
            $entity->set($field_name, [
              'value' => $processed_value,
              'format' => $field->format,
            ]);
          }
          else {
            $entity->set($field_name, $processed_value);
          }
          $needs_update = TRUE;
        }
      }

      if ($needs_update) {
        $callable_name = '';
        $message = $this->t('Updated @field_name on @bundle @type @id using @processor', [
          '@field_name' => $field_name,
          '@bundle' => $entity->bundle(),
          '@type' => $entity->getEntityTypeId(),
          '@id' => $entity->id(),
          '@processor' => is_callable($processor, FALSE, $callable_name) ? $callable_name : '(anonymous)',
        ]);

        if ($options['description']) {
          $message .= ': ' . $options['description'];
        }

        if ($options['create_revisions'] && $entity instanceof RevisionableInterface) {
          $entity->setNewRevision(TRUE);
          // Only set revision log message on supported content entities that
          // are not paragraphs.
          if ($entity instanceof RevisionLogInterface && !$entity instanceof ParagraphInterface) {
            $entity->setRevisionLogMessage($message);
          }
        }

        $entity->save();
        $logger->info($message);

        $sandbox['updated_count']++;
      }

      $sandbox['progress']++;
    }

    $sandbox['#finished'] = empty($sandbox['max']) ? 1 : ($sandbox['progress'] / $sandbox['max']);

    if ($sandbox['#finished'] >= 1) {
      return $this->t('Processed @count total entities, updated @updated.', [
        '@count' => $sandbox['progress'],
        '@updated' => $sandbox['updated_count'],
      ]);
    }

    return NULL;
  }

}
