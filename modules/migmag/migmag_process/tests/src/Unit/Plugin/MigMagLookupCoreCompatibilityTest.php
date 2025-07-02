<?php

namespace Drupal\Tests\migmag_process\Unit\Plugin;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Component\Utility\DeprecationHelper;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Schema;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Tests\migrate\Unit\process\MigrationLookupTest;
use Drupal\migmag_process\MigMagMigrateStub;
use Drupal\migmag_process\Plugin\migrate\process\MigMagLookup;
use Drupal\migrate\MigrateSkipProcessException;
use Drupal\migrate\Plugin\MigrateIdMapInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Plugin\MigrationPluginManagerInterface;
use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Prophecy\Argument;

/**
 * Tests the MigMagLookup migrate process plugin's basic functionality.
 *
 * @coversDefaultClass \Drupal\migmag_process\Plugin\migrate\process\MigMagLookup
 *
 * @group migmag_process
 */
class MigMagLookupCoreCompatibilityTest extends MigrationLookupTest {

  /**
   * The prophecy of the migration plugin manager service.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy
   */
  protected $migrationManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->migrateStub = $this->prophesize(MigMagMigrateStub::class);
    $this->migrationManager = $this->prophesize(MigrationPluginManagerInterface::class);
  }

  /**
   * {@inheritdoc}
   */
  protected function prepareContainer() {
    $container = new ContainerBuilder();
    $container->set('migmag_process.lookup.stub', $this->migrateStub->reveal());
    $container->set('migrate.lookup', $this->migrateLookup->reveal());
    $container->set('plugin.manager.migration', $this->migrationManager->reveal());
    \Drupal::setContainer($container);
    return $container;
  }

  /**
   * @covers ::transform
   */
  public function testTransformWithStubSkipping(): void {
    $migration_plugin = $this->prophesize(MigrationInterface::class);

    $destination_id_map = $this->prophesize(MigrateIdMapInterface::class);
    $destination_migration = $this->prophesize(MigrationInterface::class);
    $destination_migration->getIdMap()->willReturn($destination_id_map->reveal());
    $destination_id_map->lookupDestinationIds([1])->willReturn(NULL);

    // Ensure the migration plugin manager returns our migration.
    $this->migrationManager->createInstances(Argument::exact(['destination_migration']))
      ->willReturn(['destination_migration' => $destination_migration->reveal()]);

    $configuration = [
      'no_stub' => TRUE,
      'migration' => 'destination_migration',
    ];

    $migration_plugin->id()->willReturn('actual_migration');
    $destination_migration->getDestinationPlugin(TRUE)->shouldNotBeCalled();

    $migration = MigMagLookup::create($this->prepareContainer(), $configuration, '', [], $migration_plugin->reveal());
    $result = $migration->transform(1, $this->migrateExecutable, $this->row, '');
    $this->assertNull($result);
  }

  /**
   * @covers ::transform
   *
   * @dataProvider transformWithStubbingProvider
   */
  public function testTransformWithStubbing($exception_class = NULL, $exception_message = NULL, $expected_message = NULL): void {
    $stub_migration_id = 'destination_migration';
    $stub_migration = $this->prophesize(MigrationInterface::class);
    $stub_migration->id()->willReturn($stub_migration_id);
    $this->migrateLookup->lookup($stub_migration_id, [1])->willReturn(NULL);
    $this->migrateStub->createStub($stub_migration_id, [1], [], FALSE, TRUE)->willReturn([2]);
    $this->migrationManager->createInstances([$stub_migration_id])->willReturn([$stub_migration->reveal()]);

    $configuration = [
      'no_stub' => FALSE,
      'migration' => $stub_migration_id,
    ];

    $lookup_plugins_migration = $this->prophesize(MigrationInterface::class);
    $migmag_lookup_plugin = MigMagLookup::create($this->prepareContainer(), $configuration, '', [], $lookup_plugins_migration->reveal());
    $result = $migmag_lookup_plugin->transform(1, $this->migrateExecutable, $this->row, '');
    $this->assertEquals(2, $result);
  }

  /**
   * Test data provider wrapper for ::testTransformWithStubbing.
   *
   * @return array[]
   *   Test cases.
   */
  protected static function transformWithStubbingProvider(): array {
    if (method_exists(__CLASS__, 'providerTestTransformWithStubbing')) {
      return parent::providerTestTransformWithStubbing();
    }
    return [[]];
  }

  /**
   * Tests that processing is skipped when the input value is invalid.
   *
   * @param mixed $value
   *   An invalid value.
   *
   * @dataProvider skipInvalidDataProvider
   */
  public function testSkipInvalid($value): void {
    $migration_plugin = $this->prophesize(MigrationInterface::class);

    $configuration = [
      'migration' => 'foo_baz',
    ];
    $migration_plugin->id()->willReturn(uniqid());
    $this->migrationManager
      ->createInstances(Argument::type('array'))
      ->will(
        function ($arguments) use ($migration_plugin) {
          if (in_array('foo_baz', $arguments[0])) {
            return [
              'foo_baz' => $migration_plugin->reveal(),
            ];
          }
          throw new PluginNotFoundException(current($arguments[0]));
        }
      );
    $plugin = MigMagLookup::create($this->prepareContainer(), $configuration, '', [], $migration_plugin->reveal());

    $throws_skip_exception = DeprecationHelper::backwardsCompatibleCall(
      \Drupal::VERSION,
      '10.3',
      fn () => FALSE,
      fn () => TRUE,
    );

    if ($throws_skip_exception) {
      // @phpstan-ignore-next-line
      $this->expectException(MigrateSkipProcessException::class);
    }

    $result = $plugin->transform($value, $this->migrateExecutable, $this->row, 'foo');
    if ($throws_skip_exception) {
      return;
    }
    $this->assertTrue($plugin->isPipelineStopped());
    $this->assertNull($result);
  }

  /**
   * Tests that valid, but technically empty values are not skipped.
   *
   * @param mixed $value
   *   A valid value.
   *
   * @dataProvider noSkipValidDataProvider
   */
  public function testNoSkipValid($value): void {
    $migration_plugin = $this->prophesize(MigrationInterface::class);
    $id_map = $this->prophesize(MigrateIdMapInterface::class);
    $id_map->lookupDestinationIds([$value])->willReturn([]);
    $migration_plugin->getIdMap()->willReturn($id_map->reveal());

    $configuration = [
      'migration' => 'foo_baz',
      'no_stub' => TRUE,
    ];
    $migration_plugin->id()->willReturn(uniqid());
    $this->migrationManager->createInstances(['foo_baz'])
      ->willReturn(['foo_baz' => $migration_plugin->reveal()]);
    $plugin = MigMagLookup::create($this->prepareContainer(), $configuration, '', [], $migration_plugin->reveal());
    $lookup = $plugin->transform($value, $this->migrateExecutable, $this->row, 'foo');

    /* We provided no values and asked for no stub, so we should get NULL. */
    $this->assertNull($lookup);
  }

  /**
   * Tests a successful lookup.
   *
   * @param array $source_id_values
   *   The source id(s) of the migration map.
   * @param array $destination_id_values
   *   The destination id(s) of the migration map.
   * @param string|array $source_value
   *   The source value(s) for the migration process plugin.
   * @param string|array $expected_value
   *   The expected value(s) of the migration process plugin.
   *
   * @dataProvider successfulLookupDataProvider
   *
   * @throws \Drupal\migrate\MigrateSkipProcessException
   */
  public function testSuccessfulLookup(array $source_id_values, array $destination_id_values, $source_value, $expected_value): void {
    $migration_plugin = $this->prophesize(MigrationInterface::class);
    $this->migrateLookup->lookup('foo_baz', $source_id_values)->willReturn([$destination_id_values]);

    $configuration = [
      'migration' => 'foo_baz',
    ];

    $plugin = MigMagLookup::create($this->prepareContainer(), $configuration, '', [], $migration_plugin->reveal());
    $this->assertSame($expected_value, $plugin->transform($source_value, $this->migrateExecutable, $this->row, 'foo'));
  }

  /**
   * Tests processing multiple source IDs.
   */
  public function testMultipleSourceIds(): void {
    $migration_plugin = $this->prophesize(MigrationInterface::class);
    $this->migrateLookup->lookup('foo_baz', ['id', 6])->willReturn([[2]]);
    $configuration = [
      'migration' => 'foo_baz',
    ];
    $plugin = MigMagLookup::create($this->prepareContainer(), $configuration, '', [], $migration_plugin->reveal());
    $result = $plugin->transform(
      ['id', 6],
      $this->migrateExecutable,
      $this->row,
      ''
    );
    $this->assertEquals(2, $result);
  }

  /**
   * Tests processing multiple migrations and source IDs.
   */
  public function testMultipleMigrations(): void {
    $migration_plugin = $this->prophesize(MigrationInterface::class);
    $this->migrateLookup->lookup('foo_baz', [1])->willReturn([[2]]);
    $this->migrateLookup->lookup('foo_baz', [2])->willReturn([]);
    $this->migrateLookup->lookup('foobar', [1, 2])->willReturn([]);
    $this->migrateLookup->lookup('foobar', [3, 4])->willReturn([[5]]);
    $configuration = [
      'migration' => [
        'foobar',
        'foo_baz',
      ],
      'source_ids' => [
        'foobar' => ['foo', 'bar'],
      ],
    ];
    $variable_migration = $this->prophesize(MigrationInterface::class);
    $variable_migration_source = $this->prophesize(SqlBase::class);
    $connection_schema = $this->prophesize(Schema::class);
    $connection_schema->tableExists(Argument::any())->willReturn(FALSE);
    $source_db_connection = $this->prophesize(Connection::class);
    $source_db_connection->schema()->willReturn($connection_schema->reveal());
    $variable_migration_source->getDatabase()->willReturn($source_db_connection->reveal());
    $variable_migration->getSourcePlugin()->willReturn($variable_migration_source->reveal());
    $this->migrationManager->createStubMigration(Argument::type('array'))->willReturn($variable_migration->reveal());

    $plugin = MigMagLookup::create($this->prepareContainer(), $configuration, '', [], $migration_plugin->reveal());

    $row1 = $this->row;
    $row2 = clone $this->row;

    $row1->expects($this->any())
      ->method('getMultiple')
      ->willReturn([1, 2]);
    $result = $plugin->transform([1], $this->migrateExecutable, $row1, '');
    $this->assertEquals(2, $result);

    $row2->expects($this->any())
      ->method('getMultiple')
      ->willReturn([3, 4]);
    $result = $plugin->transform([2], $this->migrateExecutable, $row2, '');
    $this->assertEquals(5, $result);
  }

}
