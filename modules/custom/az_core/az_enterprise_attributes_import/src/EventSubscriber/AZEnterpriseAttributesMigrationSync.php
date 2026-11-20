<?php

declare(strict_types=1);

namespace Drupal\az_enterprise_attributes_import\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\migrate\Event\MigrateImportEvent;
use Drupal\migrate_tools\EventSubscriber\MigrationImportSync;
use Drupal\migrate_tools\MigrateTools;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Import and sync source and destination.
 */
final class AZEnterpriseAttributesMigrationSync extends MigrationImportSync {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * MigrationImportSync constructor.
   *
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $dispatcher
   *   The event dispatcher.
   * @param \Drupal\migrate_tools\MigrateTools $migrateTools
   *   The MigrateTools helper for source id tracking.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   * @param \Drupal\Core\Logger\LoggerChannelInterface $logger
   *   The logger channel service.
   */
  public function __construct(EventDispatcherInterface $dispatcher, MigrateTools $migrateTools, EntityTypeManagerInterface $entityTypeManager, LoggerChannelInterface $logger) {
    $this->dispatcher = $dispatcher;
    $this->migrateTools = $migrateTools;
    $this->entityTypeManager = $entityTypeManager;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public function sync(MigrateImportEvent $event): void {
    $migration = $event->getMigration();
    $migrationId = $migration->getPluginId();
    // If this isn't a migration we're concerned with, use the parent.
    if ($migrationId !== 'az_enterprise_attributes_import') {
      parent::sync($event);
      return;
    }
    if (!empty($migration->syncSource)) {
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
          $tid = $destination_ids['tid'] ?? '';
          if (!empty($tid)) {
            $term = $this->entityTypeManager->getStorage('taxonomy_term')->load($tid);
            if (!empty($term)) {
              if ($term->isPublished()) {
                $this->logger->notice('Unpublishing %title, tid @tid.',
                [
                  '%title' => $term->label(),
                  '@tid' => $term->id(),
                ]);
                $term->setUnpublished();
                $term->save();
              }
            }
          }
        }
        $id_map->next();
      }
    }

  }

}
