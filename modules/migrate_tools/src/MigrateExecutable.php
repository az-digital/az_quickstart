<?php

declare(strict_types = 1);

namespace Drupal\migrate_tools;

use Drupal\migrate\Event\MigrateEvents;
use Drupal\migrate\Event\MigrateImportEvent;
use Drupal\migrate\Event\MigrateMapDeleteEvent;
use Drupal\migrate\Event\MigrateMapSaveEvent;
use Drupal\migrate\Event\MigratePreRowSaveEvent;
use Drupal\migrate\Event\MigrateRollbackEvent;
use Drupal\migrate\Event\MigrateRowDeleteEvent;
use Drupal\migrate\MigrateExecutable as MigrateExecutableBase;
use Drupal\migrate\MigrateMessageInterface;
use Drupal\migrate\Plugin\MigrateIdMapInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate_plus\Event\MigrateEvents as MigratePlusEvents;
use Drupal\migrate_plus\Event\MigratePrepareRowEvent;

/**
 * Defines a migrate executable class for drush.
 */
class MigrateExecutable extends MigrateExecutableBase {

  /**
   * Counters of map statuses.
   *
   *   Set of counters, keyed by MigrateIdMapInterface::STATUS_* constant.
   */
  protected array $saveCounters = [
    MigrateIdMapInterface::STATUS_FAILED => 0,
    MigrateIdMapInterface::STATUS_IGNORED => 0,
    MigrateIdMapInterface::STATUS_IMPORTED => 0,
    MigrateIdMapInterface::STATUS_NEEDS_UPDATE => 0,
  ];

  /**
   * Counter of map saves, used to detect the item limit threshold.
   *
   * @var int
   */
  protected $itemLimitCounter = 0;

  /**
   * Counter of map deletions.
   */
  protected int $deleteCounter = 0;

  /**
   * Maximum number of items to process in this migration.
   *
   * 0 indicates no limit is to be applied.
   *
   * @var int
   */
  protected $itemLimit = 0;

  /**
   * Frequency (in items) at which progress messages should be emitted.
   *
   * @var int
   */
  protected $feedback = 0;

  /**
   * List of specific source IDs to import.
   */
  protected array $idlist = [];

  /**
   * Count of number of items processed so far in this migration.
   *
   * @var int
   */
  protected $counter = 0;

  /**
   * Whether the destination item exists before saving.
   */
  protected bool $preExistingItem = FALSE;

  /**
   * List of event listeners we have registered.
   */
  protected $listeners = [];

  /**
   * {@inheritdoc}
   */
  public function __construct(MigrationInterface $migration, MigrateMessageInterface $message = NULL, array $options = []) {
    parent::__construct($migration, $message);
    if (isset($options['limit'])) {
      $this->itemLimit = $options['limit'];
    }
    if (isset($options['feedback'])) {
      $this->feedback = $options['feedback'];
    }
    if (isset($options['sync'])) {
      $this->migration->set('syncSource', $options['sync']);
    }
    $this->idlist = MigrateTools::buildIdList($options);

    $this->listeners[MigrateEvents::MAP_SAVE] = [
      $this,
      'onMapSave',
    ];
    $this->listeners[MigrateEvents::MAP_DELETE] = [
      $this,
      'onMapDelete',
    ];
    $this->listeners[MigrateEvents::POST_IMPORT] = [
      $this,
      'onPostImport',
    ];
    $this->listeners[MigrateEvents::POST_ROLLBACK] = [
      $this,
      'onPostRollback',
    ];
    $this->listeners[MigrateEvents::PRE_ROW_SAVE] = [
      $this,
      'onPreRowSave',
    ];
    $this->listeners[MigrateEvents::POST_ROW_DELETE] = [
      $this,
      'onPostRowDelete',
    ];
    if (class_exists(MigratePlusEvents::class)) {
      $this->listeners[MigratePlusEvents::PREPARE_ROW] = [
        $this,
        'onPrepareRow',
      ];
    }
    foreach ($this->listeners as $event => $listener) {
      $this->resetListeners($event);
      $this->getEventDispatcher()->addListener($event, $listener);
    }
  }

  /**
   * Count up any map save events.
   *
   * @param \Drupal\migrate\Event\MigrateMapSaveEvent $event
   *   The map event.
   */
  public function onMapSave(MigrateMapSaveEvent $event) {
    // Only count saves for this migration.
    if ($event->getMap()->getQualifiedMapTableName() == $this->migration->getIdMap()->getQualifiedMapTableName()) {
      $fields = $event->getFields();
      $this->itemLimitCounter++;
      // Distinguish between creation and update.
      if ($fields['source_row_status'] == MigrateIdMapInterface::STATUS_IMPORTED &&
        $this->preExistingItem
      ) {
        $this->saveCounters[MigrateIdMapInterface::STATUS_NEEDS_UPDATE]++;
      }
      else {
        $this->saveCounters[$fields['source_row_status']]++;
      }
    }
  }

  /**
   * Count up any rollback events.
   *
   * @param \Drupal\migrate\Event\MigrateMapDeleteEvent $event
   *   The map event.
   */
  public function onMapDelete(MigrateMapDeleteEvent $event) {
    $this->deleteCounter++;
  }

  /**
   * Return the number of items created.
   *
   * @return int
   *   The number of items created.
   */
  public function getCreatedCount() {
    return $this->saveCounters[MigrateIdMapInterface::STATUS_IMPORTED];
  }

  /**
   * Return the number of items updated.
   *
   * @return int
   *   The updated count.
   */
  public function getUpdatedCount() {
    return $this->saveCounters[MigrateIdMapInterface::STATUS_NEEDS_UPDATE];
  }

  /**
   * Return the number of items ignored.
   *
   * @return int
   *   The ignored count.
   */
  public function getIgnoredCount() {
    return $this->saveCounters[MigrateIdMapInterface::STATUS_IGNORED];
  }

  /**
   * Return the number of items that failed.
   *
   * @return int
   *   The failed count.
   */
  public function getFailedCount() {
    return $this->saveCounters[MigrateIdMapInterface::STATUS_FAILED];
  }

  /**
   * Return the total number of items processed.
   *
   * Note that STATUS_NEEDS_UPDATE is not counted, since this is typically set
   * on stubs created as side effects, not on the primary item being imported.
   *
   * @return int
   *   The processed count.
   */
  public function getProcessedCount() {
    return $this->saveCounters[MigrateIdMapInterface::STATUS_IMPORTED] +
      $this->saveCounters[MigrateIdMapInterface::STATUS_NEEDS_UPDATE] +
      $this->saveCounters[MigrateIdMapInterface::STATUS_IGNORED] +
      $this->saveCounters[MigrateIdMapInterface::STATUS_FAILED];
  }

  /**
   * Return the number of items rolled back.
   *
   * @return int
   *   The rollback count.
   */
  public function getRollbackCount() {
    return $this->deleteCounter;
  }

  /**
   * Reset all the per-status counters to 0.
   */
  protected function resetCounters() {
    foreach ($this->saveCounters as $status => $count) {
      $this->saveCounters[$status] = 0;
    }
    $this->deleteCounter = 0;
  }

  /**
   * React to migration completion.
   *
   * @param \Drupal\migrate\Event\MigrateImportEvent $event
   *   The map event.
   */
  public function onPostImport(MigrateImportEvent $event) {
    $migrate_last_imported_store = \Drupal::keyValue('migrate_last_imported');
    $migrate_last_imported_store->set($event->getMigration()->id(), round(\Drupal::time()->getCurrentMicroTime() * 1000));
    $this->progressMessage();
    $this->removeListeners();

    $unused_ids = $this->getSource()->getRemainingIdList();
    if ($unused_ids) {
      $this->message->display($this->t("The following specified IDs were not found in the source IDs: @idlist.", [
        '@idlist' => implode(', ', array_map(static fn($ids): string => implode(':', $ids), $unused_ids)),
      ]));
    }
  }

  /**
   * Clean up all our event listeners.
   */
  protected function removeListeners() {
    foreach ($this->listeners as $event => $listener) {
      // Don't remove the listener for the events that are currently being
      // dispatched.
      if ($event !== MigrateEvents::POST_IMPORT && $event !== MigrateEvents::POST_ROLLBACK) {
        $this->getEventDispatcher()->removeListener($event, $listener);
      }
    }
  }

  /**
   * Clean up the event listeners that cannot be removed by removeListeners().
   *
   * @param string $event_name
   *   The name of the event to remove.
   */
  protected function resetListeners(string $event_name) {
    if (in_array($event_name, [
      MigrateEvents::POST_IMPORT,
      MigrateEvents::POST_ROLLBACK,
    ], TRUE)) {
      foreach ($this->getEventDispatcher()->getListeners($event_name) as $registered_listener) {
        if ($registered_listener[0] instanceof self) {
          $this->getEventDispatcher()->removeListener($event_name, $registered_listener);
        }
      }
    }
  }

  /**
   * Emit information on what we've done.
   *
   * Either since the last feedback or the beginning of this migration.
   *
   * @param bool $done
   *   TRUE if this is the last items to process. Otherwise FALSE.
   */
  protected function progressMessage($done = TRUE) {
    $processed = $this->getProcessedCount();
    if ($done) {
      $singular_message = "Processed 1 item (@created created, @updated updated, @failures failed, @ignored ignored) - done with '@name'";
      $plural_message = "Processed @numitems items (@created created, @updated updated, @failures failed, @ignored ignored) - done with '@name'";
    }
    else {
      $singular_message = "Processed 1 item (@created created, @updated updated, @failures failed, @ignored ignored) - continuing with '@name'";
      $plural_message = "Processed @numitems items (@created created, @updated updated, @failures failed, @ignored ignored) - continuing with '@name'";
    }
    $this->message->display(\Drupal::translation()->formatPlural($processed,
      $singular_message, $plural_message,
        [
          '@numitems' => $processed,
          '@created' => $this->getCreatedCount(),
          '@updated' => $this->getUpdatedCount(),
          '@failures' => $this->getFailedCount(),
          '@ignored' => $this->getIgnoredCount(),
          '@name' => $this->migration->id(),
        ]
    ));
  }

  /**
   * React to rollback completion.
   *
   * @param \Drupal\migrate\Event\MigrateRollbackEvent $event
   *   The map event.
   */
  public function onPostRollback(MigrateRollbackEvent $event) {
    $migrate_last_imported_store = \Drupal::keyValue('migrate_last_imported');
    $migrate_last_imported_store->set($event->getMigration()->id(), FALSE);
    $this->rollbackMessage();
    // If this is a sync import, then don't remove listeners or post import will
    // not be executed. Leave it to post import to remove listeners.
    if (empty($event->getMigration()->syncSource)) {
      $this->removeListeners();
    }
  }

  /**
   * Emit information on what we've done.
   *
   * Either since the last feedback or the beginning of this migration.
   *
   * @param bool $done
   *   TRUE if this is the last items to rollback. Otherwise FALSE.
   */
  protected function rollbackMessage($done = TRUE) {
    $translation = \Drupal::translation();
    if (($rolled_back = $this->getRollbackCount()) === 0) {
      $this->message->display($translation->translate(
        "No item has been rolled back - done with '@name'",
        ['@name' => $this->migration->id()])
      );
      return;
    }
    if ($done) {
      $singular_message = "Rolled back 1 item - done with '@name'";
      $plural_message = "Rolled back @numitems items - done with '@name'";
    }
    else {
      $singular_message = "Rolled back 1 item - continuing with '@name'";
      $plural_message = "Rolled back @numitems items - continuing with '@name'";
    }
    $this->message->display($translation->formatPlural($rolled_back,
      $singular_message, $plural_message,
      [
        '@numitems' => $rolled_back,
        '@name' => $this->migration->id(),
      ]
    ));
  }

  /**
   * React to an item about to be imported.
   *
   * @param \Drupal\migrate\Event\MigratePreRowSaveEvent $event
   *   The pre-save event.
   */
  public function onPreRowSave(MigratePreRowSaveEvent $event) {
    $id_map = $event->getRow()->getIdMap();
    if (!empty($id_map['destid1'])) {
      $this->preExistingItem = TRUE;
    }
    else {
      $this->preExistingItem = FALSE;
    }
  }

  /**
   * React to item rollback.
   *
   * @param \Drupal\migrate\Event\MigrateRowDeleteEvent $event
   *   The post-save event.
   */
  public function onPostRowDelete(MigrateRowDeleteEvent $event) {
    if ($this->feedback && ($this->deleteCounter) && $this->deleteCounter % $this->feedback == 0) {
      $this->rollbackMessage(FALSE);
      $this->resetCounters();
    }
  }

  /**
   * React to a new row.
   *
   * @param \Drupal\migrate_plus\Event\MigratePrepareRowEvent $event
   *   The prepare-row event.
   *
   * @throws \Drupal\migrate\MigrateSkipRowException
   */
  public function onPrepareRow(MigratePrepareRowEvent $event) {
    if ($this->feedback && $this->counter && $this->counter % $this->feedback == 0) {
      $this->progressMessage(FALSE);
      $this->resetCounters();
    }
    $this->counter++;
    if ($this->itemLimit && ($this->itemLimitCounter + 1) >= $this->itemLimit) {
      $event->getMigration()->interruptMigration(MigrationInterface::RESULT_COMPLETED);
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getSource() {
    if (!isset($this->source)) {
      // Re-set $this->source which the call to the parent will have set.
      $this->source = new SourceFilter(parent::getSource(), $this->idlist);
    }

    return $this->source;
  }

  /**
   * {@inheritdoc}
   */
  protected function getIdMap(): IdMapFilter {
    return new IdMapFilter(parent::getIdMap(), $this->idlist);
  }

}
