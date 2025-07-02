<?php

declare(strict_types = 1);

namespace Drupal\Tests\migrate_plus\Kernel;

use Drupal\Core\Database\Connection;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate\Plugin\MigrateIdMapInterface;
use Drupal\migrate\Row;
use Drupal\Tests\migrate\Kernel\MigrateTestBase;

/**
 * Tests migration destination table.
 *
 * @group migrate
 */
class MigrateTableTest extends MigrateTestBase {

  public const SOURCE_TABLE_NAME = 'migrate_test_source_table';
  public const DEST_TABLE_NAME = 'migrate_test_destination_table';

  /**
   * The database connection.
   */
  protected ?Connection $connection = NULL;

  /**
   * The batch size to configure (a size of 1 disables batching).
   *
   * @var int
   */
  protected $batchSize = 1;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['migrate_plus'];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->connection = $this->container->get('database');
    $connections = [
      static::SOURCE_TABLE_NAME => $this->sourceDatabase,
      static::DEST_TABLE_NAME => $this->connection,
    ];
    foreach ($connections as $table => $connection) {
      $connection->schema()->createTable($table, [
        'description' => 'Test table',
        'fields' => [
          'data' => [
            'type' => 'varchar',
            'length' => '32',
            'not null' => TRUE,
          ],
          'data2' => [
            'type' => 'varchar',
            'length' => '32',
            'not null' => TRUE,
          ],
          'data3' => [
            'type' => 'varchar',
            'length' => '32',
            'not null' => TRUE,
          ],
        ],
        'primary key' => ['data'],
      ]);
    }
    $query = $this->sourceDatabase->insert(static::SOURCE_TABLE_NAME)
      ->fields(['data', 'data2', 'data3']);
    $values = [
      [
        'data' => 'dummy value',
        'data2' => 'dummy2 value',
        'data3' => 'dummy3 value',
      ],
      [
        'data' => 'dummy value2',
        'data2' => 'dummy2 value2',
        'data3' => 'dummy3 value2',
      ],
      [
        'data' => 'dummy value3',
        'data2' => 'dummy2 value3',
        'data3' => 'dummy3 value3',
      ],
    ];
    foreach ($values as $record) {
      $query->values($record);
    }
    $query->execute();
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    $this->sourceDatabase->schema()->dropTable(static::SOURCE_TABLE_NAME);
    $this->connection->schema()->dropTable(static::DEST_TABLE_NAME);
    parent::tearDown();
  }

  /**
   * Create a minimally valid migration with some source data.
   *
   * @return array
   *   The migration definition.
   */
  public static function tableDestinationMigration(): array {
    return [
      'dummy table' => [
        [
          'id' => 'migration_table_test',
          'migration_tags' => ['Testing'],
          'source' => [
            'plugin' => 'embedded_data',
            'data_rows' => [
              [
                'data' => 'dummy1 value1',
                'data2' => 'dummy2 value1',
              ],
              [
                'data' => 'dummy1 value2',
                'data2' => 'dummy2 value2',
              ],
              [
                'data' => 'dummy1 value3',
                'data2' => 'dummy2 value3',
              ],
            ],
            'ids' => [
              'data' => ['type' => 'string'],
            ],
          ],
          'destination' => [
            'plugin' => 'table',
            'table_name' => static::DEST_TABLE_NAME,
            'id_fields' => [
              'data' => [
                'type' => 'string',
              ],
            ],
          ],
          'process' => [
            'data' => 'data',
            'data2' => 'data2',
          ],
        ],
      ],
    ];
  }

  /**
   * Tests table migration.
   */
  public function testTableMigration(): void {
    $definition = [
      'id' => 'migration_table_test',
      'migration_tags' => ['Testing'],
      'source' => [
        'plugin' => 'table',
        'table_name' => static::SOURCE_TABLE_NAME,
        'id_fields' => [
          'data' => ['type' => 'string'],
        ],
        'ignore_map' => TRUE,
      ],
      'destination' => [
        'plugin' => 'table',
        'table_name' => static::DEST_TABLE_NAME,
        'id_fields' => [
          'data' => ['type' => 'string'],
        ],
        'batch_size' => $this->batchSize,
      ],
      'process' => [
        'data' => 'data',
        'data2' => 'data2',
        'data3' => 'data3',
      ],
    ];
    $migration = \Drupal::service('plugin.manager.migration')->createStubMigration($definition);

    $executable = new MigrateExecutable($migration, $this);
    $executable->import();

    $values = $this->connection->select(static::DEST_TABLE_NAME)
      ->fields(static::DEST_TABLE_NAME)
      ->execute()
      ->fetchAllAssoc('data');

    $this->assertEquals('dummy value', $values['dummy value']->data);
    $this->assertEquals('dummy2 value', $values['dummy value']->data2);
    $this->assertEquals('dummy2 value2', $values['dummy value2']->data2);
    $this->assertEquals('dummy3 value3', $values['dummy value3']->data3);
    $this->assertEquals(3, count($values));

    // Now rollback.
    $executable->rollback();
    $values = $this->connection->select(static::DEST_TABLE_NAME)
      ->fields(static::DEST_TABLE_NAME)
      ->execute()
      ->fetchAllAssoc('data');

    $this->assertEquals(0, count($values));
  }

  /**
   * Tests table update.
   *
   * @dataProvider tableDestinationMigration
   */
  public function testTableUpdate(array $definition): void {
    // Make sure migration overwrites the original data for the first row.
    $original_values = [
      'data' => 'dummy value',
      'data2' => 'original value 2',
      'data3' => 'original value 3',
    ];
    $this->connection->insert(static::DEST_TABLE_NAME)
      ->fields($original_values)
      ->execute();

    /** @var \Drupal\migrate\Plugin\MigrationInterface $migration */
    $migration = \Drupal::service('plugin.manager.migration')
      ->createStubMigration($definition);
    $migration->getIdMap()->saveIdMapping(new Row($original_values,
      ['data' => 'dummy value']), ['data' => 'dummy value'], MigrateIdMapInterface::STATUS_NEEDS_UPDATE);
    $this->testTableMigration();
  }

}
