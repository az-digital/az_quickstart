<?php

declare(strict_types = 1);

namespace Drupal\Tests\migrate_plus\Kernel;

use Drupal\Core\Database\Connection;
use Drupal\migrate\MigrateExecutable;
use Drupal\Tests\migrate\Kernel\MigrateTestBase;

/**
 * Tests migration destination table with auto-increment keys.
 *
 * @group migrate
 */
class MigrateTableIncrementTest extends MigrateTestBase {

  public const TABLE_NAME = 'migrate_test_destination_table';

  /**
   * The database connection.
   */
  protected ?Connection $connection = NULL;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['migrate_plus'];

  /**
   * The batch size to configure.
   *
   * @var int
   */
  protected static int $batchSize = 1;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->connection = $this->container->get('database');
    $this->connection->schema()->createTable(static::TABLE_NAME, [
      'description' => 'Test table',
      'fields' => [
        'id' => [
          'type' => 'serial',
          'not null' => TRUE,
        ],
        'data1' => [
          'type' => 'varchar',
          'length' => '32',
          'not null' => TRUE,
        ],
        'data2' => [
          'type' => 'varchar',
          'length' => '32',
          'not null' => TRUE,
        ],
      ],
      'primary key' => ['id'],
    ]);
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    $this->connection->schema()->dropTable(static::TABLE_NAME);
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
                'data1' => 'dummy1 value1',
                'data2' => 'dummy2 value1',
              ],
              [
                'data1' => 'dummy1 value2',
                'data2' => 'dummy2 value2',
              ],
              [
                'data1' => 'dummy1 value3',
                'data2' => 'dummy2 value3',
              ],
            ],
            'ids' => [
              'data1' => ['type' => 'string'],
            ],
          ],
          'destination' => [
            'plugin' => 'table',
            'table_name' => static::TABLE_NAME,
            'batch_size' => static::$batchSize,
            'id_fields' => [
              'id' => [
                'type' => 'integer',
                'use_auto_increment' => TRUE,
              ],
            ],
          ],
          'process' => [
            'data1' => 'data1',
            'data2' => 'data2',
          ],
        ],
      ],
    ];
  }

  /**
   * Tests table destination.
   *
   * @param array $definition
   *   The migration definition.
   *
   * @dataProvider tableDestinationMigration
   *
   * @throws \Drupal\migrate\MigrateException
   */
  public function testTableDestination(array $definition) {
    $migration = \Drupal::service('plugin.manager.migration')->createStubMigration($definition);

    $executable = new MigrateExecutable($migration, $this);
    $executable->import();

    $values = $this->connection->select(static::TABLE_NAME)
      ->fields(static::TABLE_NAME)
      ->execute()
      ->fetchAllAssoc('data1');

    $this->assertEquals(1, $values['dummy1 value1']->id);
    $this->assertEquals(2, $values['dummy1 value2']->id);
    $this->assertEquals(3, $values['dummy1 value3']->id);
    $this->assertEquals('dummy2 value3', $values['dummy1 value3']->data2);
    $this->assertCount(3, $values);
  }

  /**
   * Tests table destination with data already in the table.
   *
   * @param array $definition
   *   The migration definition.
   *
   * @dataProvider tableDestinationMigration
   *
   * @throws \Drupal\migrate\MigrateException
   */
  public function testTableDestinationWithExistingData(array $definition) {
    $this->connection->insert(static::TABLE_NAME)
      ->fields([
        'id' => 5,
        'data1' => 'Dummy initial value',
        'data2' => 'Dummy initial value2',
      ])
      ->execute();
    $migration = \Drupal::service('plugin.manager.migration')->createStubMigration($definition);

    $executable = new MigrateExecutable($migration, $this);
    $executable->import();

    $values = $this->connection->select(static::TABLE_NAME)
      ->fields(static::TABLE_NAME)
      ->execute()
      ->fetchAllAssoc('data1');

    $this->assertEquals(5, $values['Dummy initial value']->id);
    $this->assertEquals(6, $values['dummy1 value1']->id);
    $this->assertEquals(7, $values['dummy1 value2']->id);
    $this->assertEquals(8, $values['dummy1 value3']->id);
    $this->assertEquals('dummy2 value3', $values['dummy1 value3']->data2);
    $this->assertCount(4, $values);
  }

}
