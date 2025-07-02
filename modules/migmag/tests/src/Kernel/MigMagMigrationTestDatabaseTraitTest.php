<?php

namespace Drupal\Tests\migmag\Kernel;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Database;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\migmag\Traits\MigMagMigrationTestDatabaseTrait;

/**
 * Tests MigMagMigrationTestDatabaseTrait.
 *
 * @coversDefaultClass \Drupal\Tests\migmag\Traits\MigMagMigrationTestDatabaseTrait
 *
 * @group migmag
 */
class MigMagMigrationTestDatabaseTraitTest extends KernelTestBase {

  /**
   * Tests source connection creation.
   *
   * @param string|null $migrate_connection_key
   *   The connection key argument to pass to the tested method.
   * @param bool $with_write_settings_method
   *   Whether the test class using MigMagMigrationTestDatabaseTrait should
   *   have a writeSettings method or not. See
   *   \Drupal\Core\Test\FunctionalTestSetupTrait::writeSettings.
   *
   * @covers \Drupal\Tests\migmag\Kernel\MigMagMigrationTestDatabaseTrait::createSourceMigrationConnection
   *
   * @dataProvider providerCreateSourceMigrationConnection
   */
  public function testCreateSourceMigrationConnection($migrate_connection_key, bool $with_write_settings_method): void {
    $test_class = $with_write_settings_method
      ? new TestMigMagMigrationTestDatabaseClassWithWrite()
      : new TestMigMagMigrationTestDatabaseClass();
    $expected_connection_key = $migrate_connection_key ?? 'migrate';

    $this->assertNull(Database::getConnectionInfo($migrate_connection_key));

    if ($migrate_connection_key) {
      $test_class->createSourceMigrationConnection($migrate_connection_key);
    }
    else {
      $test_class->createSourceMigrationConnection();
    }

    $connection_info_after = Database::getConnectionInfo($expected_connection_key);
    $actual_prefix = is_array($connection_info_after['default']['prefix'])
      ? $connection_info_after['default']['prefix']['default']
      : $connection_info_after['default']['prefix'];
    $this->assertSame($this->databasePrefix . '0', $actual_prefix);
    $this->assertInstanceOf(Connection::class, Database::getConnection('default', $expected_connection_key));
    if ($test_class instanceof TestMigMagMigrationTestDatabaseClassWithWrite) {
      $this->assertEquals(
        $expected_connection_key . "']['default",
        array_keys($test_class->getWritten()['databases'])[0]
      );
    }

    if ($expected_connection_key !== 'migrate') {
      $this->assertSame('migmag.test_db', \Drupal::state()->get('migrate.fallback_state_key'));
      $this->assertEquals(
        [
          'key' => $expected_connection_key,
          'target' => 'default',
        ],
        \Drupal::state()->get('migmag.test_db')
      );
    }
  }

  /**
   * Tests source connection cleanup.
   *
   * @param string|null $migrate_connection_key
   *   The connection key argument to pass to the tested method.
   *
   * @covers \Drupal\Tests\migmag\Kernel\MigMagMigrationTestDatabaseTrait::cleanupSourceMigrateConnection
   *
   * @dataProvider providerCleanupSourceMigrateConnection
   */
  public function testCleanupSourceMigrateConnection($migrate_connection_key): void {
    $test_class = new TestMigMagMigrationTestDatabaseClass();
    $expected_connection_key = $migrate_connection_key ?? 'migrate';
    $test_class->createSourceMigrationConnection($expected_connection_key);

    $this->assertIsArray(Database::getConnectionInfo($expected_connection_key)['default']);

    if ($migrate_connection_key) {
      $test_class->cleanupSourceMigrateConnection($migrate_connection_key);
    }
    else {
      $test_class->cleanupSourceMigrateConnection();
    }

    $connection_info_after = Database::getConnectionInfo($expected_connection_key);
    $this->assertNull($connection_info_after);
  }

  /**
   * Data provider for ::testCleanupSourceMigrateConnection.
   *
   * @return array[]
   *   The test cases.
   */
  public static function providerCleanupSourceMigrateConnection(): array {
    return [
      'No arg' => [
        'migrate_connection_key' => NULL,
      ],
      'Key: migrate' => [
        'migrate_connection_key' => 'migrate',
      ],
      'Key: foo_bar_baz' => [
        'migrate_connection_key' => 'foo_bar_baz',
      ],
    ];
  }

  /**
   * Data provider for ::testCreateSourceMigrationConnection.
   *
   * @return array[]
   *   The test cases.
   */
  public static function providerCreateSourceMigrationConnection(): array {
    $cleanup_cases = static::providerCleanupSourceMigrateConnection();
    $create_cases = [];
    foreach ($cleanup_cases as $label => $test_case) {
      $create_cases[$label] = $test_case + [
        'with_write_settings_method' => FALSE,
      ];
      $create_cases[$label . " with write"] = $test_case + [
        'with_write_settings_method' => TRUE,
      ];
    }
    return $create_cases;
  }

  /**
   * Tests how data gets imported into the source database.
   *
   * @param array[] $db_to_import
   *   The source data to import, keyed by table name. Each table is an array
   *   containing the rows in that table.
   * @param array|null $expected_data
   *   The expected data if it differs from the original. Defaults to NULL.
   *
   * @covers \Drupal\Tests\migmag\Kernel\MigMagMigrationTestDatabaseTrait::importSourceDatabase
   *
   * @dataProvider providerImportSourceDatabase
   */
  public function testImportSourceDatabase(array $db_to_import, ?array $expected_data = NULL): void {
    $test_class = new TestMigMagMigrationTestDatabaseClass();
    $test_class->createSourceMigrationConnection('migmag_test');
    $test_class->sourceDatabase = Database::getConnection('default', 'migmag_test');

    $test_class->importSourceDatabase($db_to_import);

    $expected_tables = array_keys($expected_data ?? $db_to_import);
    $actual_tables = $test_class->sourceDatabase->schema()->findTables('%');
    sort($expected_tables);
    sort($actual_tables);
    $this->assertEquals(
      array_values($expected_tables),
      array_values($actual_tables)
    );

    foreach ($expected_tables as $table) {
      $actual_data[$table] = $test_class->sourceDatabase
        ->select($table, $table)
        ->fields($table)
        ->execute()
        ->fetchAll(\PDO::FETCH_ASSOC);
    }

    $this->assertEquals($actual_data, $expected_data ?? $db_to_import);
  }

  /**
   * Data provider for ::testImportSourceDatabase.
   *
   * @return array[]
   *   The test cases.
   */
  public function providerImportSourceDatabase(): array {
    return [
      'Two tables with some rows' => [
        'db_to_import' => [
          'foobar' => [
            ['string_col' => 'foo', 'int_col' => 10],
            ['string_col' => 'bar', 'int_col' => 2],
          ],
          'bar' => [
            ['id' => 'baz'],
          ],
        ],
      ],
      'Key order does not matter' => [
        'db_to_import' => [
          'foobar' => [
            ['string_col' => 'foo', 'int_col' => 10],
            ['int_col' => 2, 'string_col' => 'bar'],
          ],
        ],
      ],
      'A row contains more columns than the others' => [
        'db_to_import' => [
          'foobar' => [
            ['foo_col' => 'foo', 'int_col' => 10],
            ['foo_col' => 'bar', 'int_col' => 2, 'o1' => 10, 'o2' => '5'],
            ['foo_col' => 'baz', 'int_col' => 800, 'o2' => 60],
          ],
        ],
        'expected_data' => [
          'foobar' => [
            ['foo_col' => 'foo', 'int_col' => 10, 'o1' => NULL, 'o2' => NULL],
            ['foo_col' => 'bar', 'int_col' => 2, 'o1' => 10, 'o2' => '5'],
            ['foo_col' => 'baz', 'int_col' => 800, 'o1' => NULL, 'o2' => 60],
          ],
        ],
      ],
    ];
  }

}

/**
 * Test class using MigMagMigrationTestDatabaseTrait without ::writeSettings.
 */
class TestMigMagMigrationTestDatabaseClass {

  use MigMagMigrationTestDatabaseTrait {
    createSourceMigrationConnection as public;
    cleanupSourceMigrateConnection as public;
    importSourceDatabase as public;
  }

  /**
   * A source DB connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  public $sourceDatabase;

}

/**
 * Test class using MigMagMigrationTestDatabaseTrait with ::writeSettings.
 */
class TestMigMagMigrationTestDatabaseClassWithWrite extends TestMigMagMigrationTestDatabaseClass {

  /**
   * Whether writeSettings method was called.
   *
   * @var mixed|null
   */
  protected static $written;

  /**
   * A test writeSettings method.
   *
   * @param mixed $thing
   *   A thing to write.
   */
  protected function writeSettings($thing): void {
    self::$written = $thing;
  }

  /**
   * A test writeSettings method.
   */
  public function getWritten() {
    return self::$written;
  }

}
