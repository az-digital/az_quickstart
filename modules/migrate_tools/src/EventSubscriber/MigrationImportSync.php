<?php

declare(strict_types = 1);

namespace Drupal\migrate_tools\EventSubscriber;

use Drupal\Core\State\StateInterface;
use Drupal\migrate\Event\MigrateEvents;
use Drupal\migrate\Event\MigrateImportEvent;
use Drupal\migrate\Event\MigrateRollbackEvent;
use Drupal\migrate\Event\MigrateRowDeleteEvent;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate_plus\Event\MigrateEvents as MigratePlusEvents;
use Drupal\migrate_tools\MigrateTools;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Import and sync source and destination.
 */
class MigrationImportSync implements EventSubscriberInterface {

  protected EventDispatcherInterface $dispatcher;
  protected MigrateTools $migrateTools;

  /**
   * MigrationImportSync constructor.
   *
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $dispatcher
   *   The event dispatcher.
   */
  public function __construct(EventDispatcherInterface $dispatcher, MigrateTools $migrateTools) {
    $this->dispatcher = $dispatcher;
    $this->migrateTools = $migrateTools;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events = [];
    $events[MigrateEvents::PRE_IMPORT][] = ['sync'];
    $events[MigrateEvents::POST_IMPORT][] = ['cleanSyncData'];
    return $events;
  }

  /**
   * Event callback to sync source and destination.
   *
   * @param \Drupal\migrate\Event\MigrateImportEvent $event
   *   The migration import event.
   *
   * @throws \Exception
   */
  public function sync(MigrateImportEvent $event): void {
    $migration = $event->getMigration();
    if (!empty($migration->syncSource)) {
      $migrationId = $migration->getPluginId();
      // Clear Sync IDs for this migration before starting preparing rows.
      $this->migrateTools->clearSyncSourceIds($migrationId);
      // Activate the syncing state for this migration, so
      // migrate_tools_migrate_prepare_row() can record all IDs.
      $this->migrateTools->setMigrationSyncingState($migrationId, TRUE);

      // Loop through the source to register existing source ids.
      // @see migrate_tools_migrate_prepare_row().
      // Clone so that any generators aren't initialized prematurely.
      $source = clone $migration->getSourcePlugin();
      $source->rewind();

      while ($source->valid()) {
        $source->next();
      }

      // Deactivate the syncing state for this migration, so
      // migrate_tools_migrate_prepare_row() does not record any further IDs
      // during the actual migration process.
      $this->migrateTools->setMigrationSyncingState($migrationId, FALSE);

      $source_id_values = $this->migrateTools->getSyncSourceIds($migrationId);

      $id_map = $migration->getIdMap();
      $id_map->rewind();
      $destination = $migration->getDestinationPlugin();

      while ($id_map->valid()) {
        $map_source_id = $id_map->currentSource();

        foreach ($source->getIds() as $id_key => $id_config) {
          if ($id_config['type'] === 'string') {
            $map_source_id[$id_key] = (string) $map_source_id[$id_key];
          }
          elseif ($id_config['type'] === 'integer') {
            $map_source_id[$id_key] = (int) $map_source_id[$id_key];
          }
        }

        if (!in_array($map_source_id, $source_id_values, TRUE)) {
          $destination_ids = $id_map->currentDestination();
          if ($destination_ids !== NULL) {
            $this->dispatchRowDeleteEvent(MigrateEvents::PRE_ROW_DELETE, $migration, $destination_ids);
            if (class_exists(MigratePlusEvents::class)) {
              $this->dispatchRowDeleteEvent(MigratePlusEvents::MISSING_SOURCE_ITEM, $migration, $destination_ids);
            }
            $destination->rollback($destination_ids);
            $this->dispatchRowDeleteEvent(MigrateEvents::POST_ROW_DELETE, $migration, $destination_ids);
          }
          $id_map->delete($map_source_id);
        }
        $id_map->next();
      }
      $this->dispatcher->dispatch(new MigrateRollbackEvent($migration), MigrateEvents::POST_ROLLBACK);
    }
  }

  /**
   * Cleans Sync data after a migration is complete.
   *
   * @param \Drupal\migrate\Event\MigrateImportEvent $event
   *   The migration import event.
   */
  public function cleanSyncData(MigrateImportEvent $event): void {
    $migration = $event->getMigration();
    $migrationId = $migration->getPluginId();
    $this->migrateTools->clearSyncSourceIds($migrationId);
  }

  /**
   * Dispatches MigrateRowDeleteEvent event.
   *
   * @param string $event_name
   *   The event name to dispatch.
   * @param \Drupal\migrate\Plugin\MigrationInterface $migration
   *   The active migration.
   * @param array $destination_ids
   *   The destination identifier values of the record.
   */
  protected function dispatchRowDeleteEvent(string $event_name, MigrationInterface $migration, array $destination_ids): void {
    // Symfony changing dispatcher so implementation could change.
    $this->dispatcher->dispatch(new MigrateRowDeleteEvent($migration, $destination_ids), $event_name);
  }

}
