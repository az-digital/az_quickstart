<?php

namespace Drupal\Tests\migmag\Traits;

use Drupal\Core\Database\Database;

/**
 * Trait for importing custom data into the migrate source database.
 */
trait MigMagMigrationTestDatabaseTrait {

  /**
   * Changes the database connection to the prefixed one.
   *
   * @see \Drupal\Tests\migrate\Kernel\MigrateTestBase::createMigrationConnection()
   *
   * @todo Refactor when core don't use global.
   *   https://www.drupal.org/node/2552791
   */
  protected function createSourceMigrationConnection(string $source_db_key = 'migrate') {
    // If the backup already exists, something went terribly wrong.
    // This case is possible, because database connection info is a static
    // global state construct on the Database class, which at least persists
    // for all test methods executed in one PHP process.
    if (Database::getConnectionInfo("simpletest_original_$source_db_key")) {
      throw new \RuntimeException("Bad Database connection state: 'simpletest_original_$source_db_key' connection key already exists. Broken test?");
    }

    // Clone the current connection and replace the current prefix.
    $connection_info = Database::getConnectionInfo($source_db_key);
    if ($connection_info) {
      Database::renameConnection($source_db_key, "simpletest_original_$source_db_key");
    }
    $connection_info = Database::getConnectionInfo('default');
    foreach ($connection_info as $target => $value) {
      $prefix = is_array($value['prefix']) ? $value['prefix']['default'] : $value['prefix'];
      // Simpletest uses 7 character prefixes at most so this can't cause
      // collisions.
      if (is_string($connection_info[$target]['prefix'])) {
        $connection_info[$target]['prefix'] = $prefix . '0';
        continue;
      }

      $connection_info[$target]['prefix']['default'] = $prefix . '0';
      // Add the original simpletest prefix so SQLite can attach its database.
      // @see \Drupal\Core\Database\Driver\sqlite\Connection::init()
      $connection_info[$target]['prefix'][$value['prefix']['default']] = $prefix;
      break;
    }
    Database::addConnectionInfo($source_db_key, 'default', $connection_info['default']);

    // If we are in a functional test, we might use Drush for testing things.
    // So we write the connection info into settings.php.
    if (is_callable([$this, 'writeSettings'])) {
      $this->writeSettings([
        'databases' => [
          "{$source_db_key}']['default" => (object) [
            'value' => $connection_info['default'],
            'required' => TRUE,
          ],
        ],
      ]);
    }

    if ($source_db_key !== 'migrate') {
      \Drupal::state()->set('migmag.test_db', [
        'key' => $source_db_key,
        'target' => 'default',
      ]);
      \Drupal::state()->set('migrate.fallback_state_key', 'migmag.test_db');
    }
  }

  /**
   * Loads a database fixture into the source database connection.
   *
   * @param array $database
   *   The source data, keyed by table name. Each table is an array containing
   *   the rows in that table.
   *
   * @see \Drupal\Tests\migrate\Kernel\MigrateSqlSourceTestBase::getDatabase
   */
  protected function importSourceDatabase(array $database): void {
    // Create the tables and fill them with data.
    foreach ($database as $table => $rows) {
      // Use the biggest row to build the table schema.
      $counts = array_map('count', $rows);
      asort($counts);
      end($counts);
      $pilot = $rows[key($counts)];

      $schema = array_map(
        function ($value) {
          $type = [];

          // If the value is unserializable, use longblob.
          $res = @unserialize($value, ['allowed_classes' => FALSE]);
          if ($value === 'b:0;' || $res !== FALSE) {
            $type = [
              'type' => 'blob',
              'size' => 'big',
            ];
          }
          else {
            $type['type'] = is_numeric($value) && !is_float($value + 0)
              ? 'int'
              : 'text';
          }
          $type['not null'] = FALSE;
          return $type;
        },
        $pilot
      );

      $this->sourceDatabase->schema()
        ->createTable($table, [
          'fields' => $schema,
        ]);

      $fields = array_keys($pilot);
      $default_nulls = array_combine(array_keys($schema), array_fill(0, count($schema), NULL));
      $insert = $this->sourceDatabase->insert($table)->fields($fields);
      foreach ($rows as $row) {
        $insert->values($row + $default_nulls);
      }
      $insert->execute();
    }
  }

  /**
   * Cleans up the test migrate connection.
   *
   * @see \Drupal\Tests\migrate\Kernel\MigrateTestBase::cleanupMigrateConnection()
   *
   * @todo Refactor when core don't use global.
   *   https://www.drupal.org/node/2552791
   */
  private function cleanupSourceMigrateConnection(string $source_db_key = 'migrate') {
    Database::removeConnection($source_db_key);
    $original_connection_info = Database::getConnectionInfo("simpletest_original_$source_db_key");
    if ($original_connection_info) {
      Database::renameConnection("simpletest_original_$source_db_key", $source_db_key);
    }
  }

}
