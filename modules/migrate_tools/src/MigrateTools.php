<?php

declare(strict_types = 1);

namespace Drupal\migrate_tools;

use Drupal\Core\Database\Connection;
use Drupal\migrate\Plugin\MigrateIdMapInterface;
use Drupal\migrate\Plugin\MigrationInterface;

/**
 * Utility functionality for use in migrate_tools.
 */
class MigrateTools {

  /**
   * Default ID list delimiter.
   */
  public const DEFAULT_ID_LIST_DELIMITER = ':';

  /**
   * Maximum number of source IDs to keep in memory before flushing them
   * to database.
   */
  protected const MAX_BUFFERED_SYNC_SOURCE_IDS_ENTRIES = 1000;

  /**
   * Sync Ids buffered in RAM before flushing them to database.
   */
  protected array $bufferedSyncIdsEntries = [];

  /**
   * Array keeping track of migrations being in the Syncing IDs phase.
   * Structure: List of `string => bool` where the key is the migration ID and
   * the value is a boolean indicating whether it is currently syncing.
   */
  protected array $syncingMigrations = [];

  /**
   * Connection to the database.
   */
  public Connection $connection;

  /**
   * MigrateTools constructor.
   *
   * @param Connection $connection
   *   Connection to the database.
   */
  public function __construct(Connection $connection) {
    $this->connection = $connection;
  }

  /**
   * Build the list of specific source IDs to import.
   *
   * @param array $options
   *   The migration executable options.
   *
   *   The ID list.
   */
  public static function buildIdList(array $options): array {
    $options += [
      'idlist' => NULL,
      'idlist-delimiter' => self::DEFAULT_ID_LIST_DELIMITER,
    ];
    $id_list = [];
    if (is_scalar($options['idlist'])) {
      $id_list = explode(',', (string) $options['idlist']);
      array_walk($id_list, function (&$value) use ($options): void {
        $value = str_getcsv($value, $options['idlist-delimiter']);
      });
    }
    return $id_list;
  }

  /**
   * Clears all SyncSourceIds entries from the database, for given migration.
   *
   * @param string $migrationId
   *   Migration ID.
   */
  public function clearSyncSourceIds(string $migrationId): void
  {
    $query = $this->connection->delete('migrate_tools_sync_source_ids')
      ->condition('migration_id', $migrationId);
    $query->execute();
  }

  /**
   * Adds a SyncSourceIds entry to the database, for given migration.
   *
   * @param string $migrationId
   *   Migration ID.
   * @param array $sourceIds
   *   A set of SyncSourceIds. Gets serialized to retain its structure.
   *
   * @throws \Exception
   */
  public function addToSyncSourceIds(string $migrationId, array $sourceIds): void
  {
    $this->bufferedSyncIdsEntries[] = [
      'migration_id' => $migrationId,
      // Serialize source IDs before saving them to retain their structure.
      'source_ids' => serialize($sourceIds),
    ];
    if (count($this->bufferedSyncIdsEntries) >= static::MAX_BUFFERED_SYNC_SOURCE_IDS_ENTRIES) {
      $this->flushSyncSourceIdsToDatabase();
    }
  }

  /**
   * Flushes any pending SyncSourceIds to the database.
   *
   * @throws \Exception
   */
  protected function flushSyncSourceIdsToDatabase(): void {
    if (empty($this->bufferedSyncIdsEntries)) {
      // Nothing to flush, do nothing.
      return;
    }

    // Batch insert all buffered pending entries.
    $query = $this->connection->insert('migrate_tools_sync_source_ids')
      ->fields(['migration_id', 'source_ids']);
    foreach($this->bufferedSyncIdsEntries as $entry) {
      $query->values($entry);
    }
    $query->execute();

    // Clear buffered pending entries.
    $this->bufferedSyncIdsEntries = [];
  }

  /**
   * Returns all SyncSourceIds from the database, for given migration.
   *
   * @param string $migrationId
   *   Migration ID.
   *
   * @return array
   *   Ids, structured as they were inserted.
   *
   * @throws \Exception
   */
  public function getSyncSourceIds(string $migrationId): array
  {
    // Ensure all data was flushed to database before retrieving all of them.
    $this->flushSyncSourceIdsToDatabase();

    // Retrieve all IDs.
    $serializedSourceIds = $this->connection->query(
        'SELECT source_ids FROM {migrate_tools_sync_source_ids} WHERE migration_id = :mid',
        [':mid' => $migrationId],
      )
      ->fetchCol();

    // Unserialize source IDs to restore their structure.
    array_walk($serializedSourceIds, static function(&$entry) {
      $entry = unserialize($entry);
    });

    return $serializedSourceIds;
  }

  /**
   * Sets the syncing state of a migration.
   *
   * @param string $migrationId
   *   Migration ID.
   * @param bool   $isSyncing
   *   State to set.
   */
  public function setMigrationSyncingState(string $migrationId, bool $isSyncing): void
  {
    $this->syncingMigrations[$migrationId] = $isSyncing;
  }

  /**
   * Returns the syncing state of a migration.
   *
   * @param string $migrationId
   *   Migration ID.
   *
   * @return bool
   *   Whether the migration is currently syncing its IDs or not.
   */
  public function isMigrationSyncing(string $migrationId): bool
  {
    return $this->syncingMigrations[$migrationId] ?? FALSE;
  }

  /**
   * Returns a mapping of log levels to a human-friendly label.
   *
   * @return array
   *   An array of log level labels.
   */
  public static function getLogLevelLabelMapping() {
    return [
      MigrationInterface::MESSAGE_ERROR => t('Error'),
      MigrationInterface::MESSAGE_WARNING => t('Warning'),
      MigrationInterface::MESSAGE_NOTICE => t('Notice'),
      MigrationInterface::MESSAGE_INFORMATIONAL => t('Informational'),
    ];
  }

  /**
   * Returns a mapping of status levels to a human-friendly label.
   *
   * @return array
   *   An array of migration status labels.
   */
  public static function getStatusLevelLabelMapping() {
    return [
      MigrateIdMapInterface::STATUS_IMPORTED => t('Imported'),
      MigrateIdMapInterface::STATUS_NEEDS_UPDATE => t('Pending'),
      MigrateIdMapInterface::STATUS_IGNORED => t('Ignored'),
      MigrateIdMapInterface::STATUS_FAILED => t('Failed'),
    ];
  }

}
