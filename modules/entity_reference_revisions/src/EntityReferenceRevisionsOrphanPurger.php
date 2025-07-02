<?php

namespace Drupal\entity_reference_revisions;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Manages orphan composite revision deletion.
 */
class EntityReferenceRevisionsOrphanPurger {

  use StringTranslationTrait;

  /**
   * Parent is valid.
   */
  const PARENT_VALID = 0;

  /**
   * Parent is invalid and usage can not be verified.
   */
  const PARENT_INVALID_SKIP = 1;

  /**
   * Parent is invalid and paragraph is safe to delete.
   */
  const PARENT_INVALID_DELETE = 2;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The time service.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * The database service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * List of already checked parents.
   *
   * @var bool[][]
   */
  protected $validParents = [];

  /**
   * Constructs a EntityReferenceRevisionsOrphanManager object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Drupal\Core\Database\Connection $database
   *   The database service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $entity_field_manager, DateFormatterInterface $date_formatter, TimeInterface $time, Connection $database, MessengerInterface $messenger) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
    $this->dateFormatter = $date_formatter;
    $this->time = $time;
    $this->database = $database;
    $this->messenger = $messenger;
  }

  /**
   * Deletes unused revision or an entity if there are no revisions remaining.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $composite_revision
   *   The composite revision.
   *
   * @return bool
   *   TRUE if an entity revision was deleted. Otherwise, FALSE.
   */
  public function deleteUnusedRevision(ContentEntityInterface $composite_revision) {
    // If this is the default revision of the composite entity, check if there
    // are other revisions. If there are not, delete the composite entity.
    $composite_storage = $this->entityTypeManager->getStorage($composite_revision->getEntityTypeId());

    if ($composite_revision->isDefaultRevision()) {
      $count = $composite_storage
        ->getQuery()
        ->accessCheck(FALSE)
        ->allRevisions()
        ->condition($composite_storage->getEntityType()->getKey('id'), $composite_revision->id())
        ->count()
        ->execute();
      if ($count <= 1) {
        $composite_revision->delete();
        return TRUE;
      }
    }
    else {
      // Delete the revision if this is not the default one.
      $composite_storage->deleteRevision($composite_revision->getRevisionId());
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Batch operation for checking orphans for a given entity type.
   *
   * @param string $entity_type_id
   *   The entity type id, for example 'paragraph'.
   * @param Iterable|array $context
   *   The context array.
   */
  public function deleteOrphansBatchOperation($entity_type_id, &$context) {
    $composite_type = $this->entityTypeManager->getDefinition($entity_type_id);
    $composite_revision_key = $composite_type->getKey('revision');
    /** @var \Drupal\Core\Entity\ContentEntityStorageInterface $composite_storage */
    $composite_storage = $this->entityTypeManager->getStorage($entity_type_id);
    $batch_size = Settings::get('entity_update_batch_size', 50);

    if (empty($context['sandbox']['total'])) {
      $context['sandbox']['progress'] = 0;
      $context['sandbox']['current_revision_id'] = -1;
      $context['sandbox']['total'] = (int) $composite_storage->getQuery()
        ->allRevisions()
        ->accessCheck(FALSE)
        ->count()
        ->execute();
    }

    if (!isset($context['results'][$entity_type_id])) {
      $context['results'][$entity_type_id]['entity_count'] = 0;
      $context['results'][$entity_type_id]['revision_count'] = 0;
      $context['results'][$entity_type_id]['start'] = $this->time->getRequestTime();
    }

    // Get the next batch of revision ids from the selected entity type.
    // @todo Replace with an entity query on all revisions with a revision ID
    //   condition after https://www.drupal.org/project/drupal/issues/2766135.
    $revision_table = $composite_type->getRevisionTable();
    $entity_revision_ids = $this->database->select($revision_table, 'r')
      ->fields('r', [$composite_revision_key])
      ->range(0, $batch_size)
      ->orderBy($composite_revision_key)
      ->condition($composite_revision_key, $context['sandbox']['current_revision_id'], '>')
      ->execute()
      ->fetchCol();

    /** @var \Drupal\Core\Entity\ContentEntityInterface $composite_revision */
    foreach ($composite_storage->loadMultipleRevisions($entity_revision_ids) as $composite_revision) {
      $context['sandbox']['progress']++;
      $context['sandbox']['current_revision_id'] = $composite_revision->getRevisionId();

      if ($this->isUsed($composite_revision)) {
        continue;
      }

      if ($this->deleteUnusedRevision($composite_revision)) {
        $context['results'][$entity_type_id]['revision_count']++;
        if ($composite_revision->isDefaultRevision()) {
          $context['results'][$entity_type_id]['entity_count']++;
        }
      }
    }

    // This entity type is completed if no new revision ids were found or the
    // total is reached.
    if ($entity_revision_ids && $context['sandbox']['progress'] < $context['sandbox']['total']) {
      $context['finished'] = $context['sandbox']['progress'] / $context['sandbox']['total'];
    }
    else {
      $context['finished'] = 1;
      $context['results'][$entity_type_id]['end'] = $this->time->getRequestTime();
    }

    $interval = $this->dateFormatter->formatInterval($this->time->getRequestTime() - $context['results'][$entity_type_id]['start']);
    $context['message'] = t('Checked @entity_type revisions for orphans: @current of @total in @interval (@deletions deleted)', [
      '@entity_type' => $composite_type->getLabel(),
      '@current' => $context['sandbox']['progress'],
      '@total' => $context['sandbox']['total'],
      '@interval' => $interval,
      '@deletions' => $context['results'][$entity_type_id]['revision_count'],
    ]);
  }

  /**
   * Batch dispatch submission finished callback.
   */
  public static function batchSubmitFinished($success, $results, $operations) {
    return \Drupal::service('entity_reference_revisions.orphan_purger')->doBatchSubmitFinished($success, $results, $operations);
  }

  /**
   * Sets a batch for executing deletion of the orphaned composite entities.
   *
   * @param array $composite_entity_type_ids
   *   An array of composite entity type IDs to remove orphaned items for.
   */
  public function setBatch(array $composite_entity_type_ids) {
    if (empty($composite_entity_type_ids)) {
      return;
    }

    $operations = [];
    foreach ($composite_entity_type_ids as $entity_type_id) {
      $operations[] = ['_entity_reference_revisions_orphan_purger_batch_dispatcher',
        [
          'entity_reference_revisions.orphan_purger:deleteOrphansBatchOperation',
          $entity_type_id,
        ],
      ];
    }

    $batch = [
      'operations' => $operations,
      'finished' => [EntityReferenceRevisionsOrphanPurger::class, 'batchSubmitFinished'],
      'title' => $this->t('Removing orphaned entities.'),
      'progress_message' => $this->t('Processed @current of @total entity types.'),
      'error_message' => $this->t('This batch encountered an error.'),
    ];
    batch_set($batch);
  }

  /**
   * Finished callback for the batch process.
   *
   * @param bool $success
   *   Whether the batch completed successfully.
   * @param array $results
   *   The results array.
   * @param array $operations
   *   The operations array.
   */
  public function doBatchSubmitFinished($success, $results, $operations) {
    if ($success) {
      foreach ($results as $entity_type_id => $result) {
        if ($this->entityTypeManager->hasDefinition($entity_type_id)) {
          $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);
          $interval = $this->dateFormatter->formatInterval($result['end'] - $result['start']);
          $this->messenger->addMessage($this->t('@label: Deleted @revision_count revisions (@entity_count entities) in @interval.', [
            '@label' => $entity_type->getLabel(),
            '@revision_count' => $result['revision_count'],
            '@entity_count' => $result['entity_count'],
            '@interval' => $interval,
          ]));
        }
      }
    }
    else {
      // $operations contains the operations that remained unprocessed.
      $error_operation = reset($operations);
      $this->messenger->addError($this->t('An error occurred while processing @operation with arguments : @args', [
        '@operation' => $error_operation[0],
        '@args' => print_r($error_operation[0], TRUE),
      ]));
    }
  }

  /**
   * Checks if the composite entity is used.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $composite_revision
   *   The composite revision.
   *
   * @return bool
   *   Whether the composite entity is used, FALSE if it is safe to delete.
   */
  public function isUsed(ContentEntityInterface $composite_revision) {
    $composite_type = $this->entityTypeManager->getDefinition($composite_revision->getEntityTypeId());

    $parent_type_field = $composite_type->get('entity_revision_parent_type_field');
    $parent_type = $composite_revision->get($parent_type_field)->value;
    $parent_field_name_field = $composite_type->get('entity_revision_parent_field_name_field');
    $parent_field_name = $composite_revision->get($parent_field_name_field)->value;

    $status = $this->isValidParent($parent_type, $parent_field_name);
    if ($status !== static::PARENT_VALID) {
      return $status == static::PARENT_INVALID_SKIP ? TRUE : FALSE;
    }

    // Check if the revision is used in any revision of the parent, if that
    // entity type supports revisions.
    $query = $this->entityTypeManager->getStorage($parent_type)
      ->getQuery()
      ->condition("$parent_field_name.target_revision_id", $composite_revision->getRevisionId())
      ->range(0, 1)
      ->accessCheck(FALSE);

    if ($this->entityTypeManager->getDefinition($parent_type)->isRevisionable()) {
      $query = $query->allRevisions();
    }

    $revisions = $query->execute();
    // If there are parent revisions where this revision is used, skip it.
    return !empty($revisions);
  }

  /**
   * Checks if the parent type/field is a valid combination that can be queried.
   *
   * @param string $parent_type
   *   Parent entity type ID.
   * @param string $parent_field_name
   *   Parent field name.
   *
   * @return int
   *   static::PARENT_VALID, static::PARENT_INVALID_SKIP or
   *   static::PARENT_INVALID_DELETE.
   */
  protected function isValidParent($parent_type, $parent_field_name) {
    // There is not certainty that this revision is not used because we do not
    // know what to query for if the parent fields are empty.
    if ($parent_type == NULL) {
      return static::PARENT_INVALID_SKIP;
    }

    if (isset($this->validParents[$parent_type][$parent_field_name])) {
      return $this->validParents[$parent_type][$parent_field_name];
    }

    $status = static::PARENT_VALID;

    // If the parent type does not exist anymore, the composite is not used.
    if (!$this->entityTypeManager->hasDefinition($parent_type)) {
      $status = static::PARENT_INVALID_DELETE;
    }
    else {
      $parent_field_definitions = $this->entityFieldManager->getFieldStorageDefinitions($parent_type);
      if (!isset($parent_field_definitions[$parent_field_name])) {
        $status = static::PARENT_INVALID_DELETE;
      }
      // In case the parent field has no target revision ID key we can not be
      // sure that this revision is not used anymore.
      elseif (empty($parent_field_definitions[$parent_field_name]->getSchema()['columns']['target_revision_id'])) {
        $status = static::PARENT_INVALID_SKIP;
      }
    }
    $this->validParents[$parent_type][$parent_field_name] = $status;

    return $status;
  }

  /**
   * Returns a list of composite entity types.
   *
   * @return \Drupal\Core\Entity\EntityTypeInterface[]
   *   An array of composite entity types.
   */
  public function getCompositeEntityTypes() {
    $composite_entity_types = [];
    $entity_types = $this->entityTypeManager->getDefinitions();
    foreach ($entity_types as $entity_type) {
      $has_parent_type_field = $entity_type->get('entity_revision_parent_type_field');
      $has_parent_id_field = $entity_type->get('entity_revision_parent_id_field');
      $has_parent_field_name_field = $entity_type->get('entity_revision_parent_field_name_field');
      if ($has_parent_type_field && $has_parent_id_field && $has_parent_field_name_field) {
        $composite_entity_types[] = $entity_type;
      }
    }
    return $composite_entity_types;
  }

}
