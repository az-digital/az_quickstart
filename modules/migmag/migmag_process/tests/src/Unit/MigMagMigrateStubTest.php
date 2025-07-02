<?php

namespace Drupal\Tests\migmag_process\Unit;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\migmag_process\MigMagMigrateStub;
use Drupal\migrate\Plugin\MigrateDestinationInterface;
use Drupal\migrate\Plugin\MigrateIdMapInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Plugin\MigrationPluginManagerInterface;
use Drupal\migrate\Plugin\migrate\source\EmbeddedDataSource;
use Drupal\migrate\Row;
use Prophecy\Argument;

/**
 * Tests the migrate stub service.
 *
 * @group migmag_process
 *
 * @coversDefaultClass \Drupal\migmag_process\MigMagMigrateStub
 */
class MigMagMigrateStubTest extends UnitTestCase {

  /**
   * The plugin manager prophecy.
   *
   * @var \Prophecy\Prophecy\ObjectProphecy
   */
  protected $migrationPluginManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->migrationPluginManager = $this->prophesize(MigrationPluginManagerInterface::class);
  }

  /**
   * Tests stubbing.
   *
   * @covers ::createStub
   */
  public function testCreateStub() {
    $ids = ['id' => ['type' => 'integer']];
    $row_source_1_missing = new Row(['id' => 1], $ids, TRUE);
    $row_source_2 = new Row(['id' => 2], $ids, TRUE);

    $id_map = $this->prophesize(MigrateIdMapInterface::class);
    $id_map->getRowBySource(Argument::any())->willReturn([]);
    $id_map->setMessage(Argument::any())->willReturn(NULL);
    $id_map->saveIdMapping(Argument::cetera())->willReturn(NULL);
    $id_map->delete(Argument::cetera())->willReturn(NULL);

    $migration = $this->prophesize(MigrationInterface::class);
    $destination_plugin = $this->prophesize(MigrateDestinationInterface::class);
    $migration->id()->willReturn('test_migration');
    $migration->getIdMap()->willReturn($id_map->reveal());
    $source_plugin = new EmbeddedDataSource([
      'data_rows' => [
        $row_source_2->getSource(),
      ],
      'ids' => $ids,
    ], 'embedded_data', [], $migration->reveal());
    $migration->getSourcePlugin()->willReturn($source_plugin);
    $migration->getProcessPlugins([])->willReturn([]);
    $migration->getProcess()->willReturn([]);
    $migration->getSourceConfiguration()->willReturn([]);

    // The source plugin's prepareRow method uses a module handler fetched from
    // the service container.
    // @see \Drupal\migrate\Plugin\migrate\source\SourcePluginBase::prepareRow()
    // @see \Drupal\migrate\Plugin\migrate\source\SourcePluginBase::getModuleHandler()
    $module_handler = $this->createMock(ModuleHandlerInterface::class);
    $module_handler
      ->expects($this->any())
      ->method('invokeAll')
      ->willReturn([]);
    $container = new ContainerBuilder();
    $container->set('module_handler', $module_handler);
    \Drupal::setContainer($container);
    $destination_plugin->import(Argument::any())->will(function () {
      $row = func_get_args()[0][0];
      return ['id' => $row->getSource()['id'] + 1];
    });
    $migration->getDestinationPlugin(TRUE)
      ->willReturn($destination_plugin->reveal());

    $this->migrationPluginManager->createInstances(['test_migration'])
      ->willReturn([$migration->reveal()]);

    $stub = new MigMagMigrateStub($this->migrationPluginManager->reveal());

    $this->assertSame(['id' => 2], $stub->createStub('test_migration', ['id' => 1], []));

    // If MigMagMigrateStub is asked to create only valid stubs, then with the
    // incoming "['id' => 1]" source IDs array shouldn't create a stub.
    $this->assertFalse($stub->createStub('test_migration', $row_source_1_missing->getSource(), [], NULL, TRUE));
    $this->assertSame(['id' => 3], $stub->createStub('test_migration', $row_source_2->getSource(), [], NULL, TRUE));
  }

  /**
   * Tests that an error is logged if the plugin manager throws an exception.
   */
  public function testExceptionOnPluginNotFound() {
    $this->migrationPluginManager->createInstances(['test_migration'])
      ->willReturn([]);
    $this->expectException(PluginNotFoundException::class);
    $this->expectExceptionMessage("Plugin ID 'test_migration' was not found.");
    $stub = new MigMagMigrateStub($this->migrationPluginManager->reveal());
    $stub->createStub('test_migration', [1]);
  }

  /**
   * Tests that an error is logged on derived migrations.
   */
  public function testExceptionOnDerivedMigration() {
    $this->migrationPluginManager->createInstances(['test_migration'])
      ->willReturn([
        'test_migration:d1' => $this->prophesize(MigrationInterface::class)
          ->reveal(),
        'test_migration:d2' => $this->prophesize(MigrationInterface::class)
          ->reveal(),
      ]);
    $this->expectException(\LogicException::class);
    $this->expectExceptionMessage('Cannot stub derivable migration "test_migration".  You must specify the id of a specific derivative to stub.');
    $stub = new MigMagMigrateStub($this->migrationPluginManager->reveal());
    $stub->createStub('test_migration', [1]);
  }

}
