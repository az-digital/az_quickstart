<?php

declare(strict_types = 1);

namespace Drupal\az_enterprise_attributes_import\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannel;
use Drupal\Core\State\StateInterface;
use Drupal\migrate\Event\MigrateImportEvent;
use Drupal\migrate_tools\EventSubscriber\MigrationImportSync;
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
   * @var \Drupal\Core\Logger\LoggerChannel
   */
  protected $logger;

  /**
   * MigrationImportSync constructor.
   *
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $dispatcher
   *   The event dispatcher.
   * @param \Drupal\Core\State\StateInterface $state
   *   The Key/Value Store to use for tracking synced source rows.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager service.
   * @param \Drupal\Core\Logger\LoggerChannel $logger
   *   The logger channel service.
   */
  public function __construct(EventDispatcherInterface $dispatcher, StateInterface $state, EntityTypeManagerInterface $entityTypeManager, LoggerChannel $logger) {
    $this->dispatcher = $dispatcher;
    $this->state = $state;
    $this->state->set('migrate_tools_sync', []);
    $this->entityTypeManager = $entityTypeManager;
    $this->logger = $logger;
  }

  /**
   * {@inheritdoc}
   */
  public function sync(MigrateImportEvent $event): void {
    $migration = $event->getMigration();
    // If this isn't a migration we're concerned with, use the parent.
    if ($migration->id() !== 'az_enterprise_attributes_import') {
      parent::sync($event);
      return;
    }
    if (!empty($migration->syncSource)) {

      // Loop through the source to register existing source ids.
      // @see migrate_tools_migrate_prepare_row().
      // Clone so that any generators aren't initialized prematurely.
      $source = clone $migration->getSourcePlugin();
      $source->rewind();

      while ($source->valid()) {
        $source->next();
      }

      $source_id_values = $this->state->get('migrate_tools_sync', []);

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
