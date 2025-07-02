<?php

/**
 * @file
 * Contains post_update hooks for migrate_queue_importer.
 */

/**
 * Add a sync flag to existing CronMigration entities.
 */
function migrate_queue_importer_post_update_0001_add_sync_flag(&$sandbox) {
  $storage = Drupal::entityTypeManager()->getStorage('cron_migration');
  $cron_migrations = $storage->loadMultiple();
  /** @var \Drupal\migrate_queue_importer\Entity\CronMigration $cronMigration */
  foreach ($cron_migrations as $cronMigration) {
    $cronMigration->sync = 0;
    $storage->save($cronMigration);
  }
}
