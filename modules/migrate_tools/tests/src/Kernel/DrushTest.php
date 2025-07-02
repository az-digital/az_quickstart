<?php

declare(strict_types = 1);

namespace Drupal\Tests\migrate_tools\Kernel {

  use Drupal\migrate\Plugin\MigrationInterface;
  use Drupal\migrate\Plugin\MigrationPluginManagerInterface;
  use Drupal\migrate_tools\Drush\Commands\MigrateToolsCommands;
  use Drupal\migrate_tools\MigrateTools;
  use Drupal\Tests\migrate\Kernel\MigrateTestBase;
  use Drush\Log\DrushLoggerManager;
  use Psr\Log\LoggerInterface;

  /**
   * Tests for the Drush 9 commands.
   *
   * @group migrate_tools
   */
  final class DrushTest extends MigrateTestBase {

    /**
     * {@inheritdoc}
     */
    protected static $modules = [
      'migrate_tools_test',
      'migrate_tools',
      'migrate_plus',
      'taxonomy',
      'text',
      'system',
      'user',
    ];

    /**
     * Base options array for import.
     *
     * @var array
     */
    private array $importBaseOptions = [
      'all' => NULL,
      'group' => NULL,
      'tag' => NULL,
      'limit' => NULL,
      'feedback' => NULL,
      'idlist' => NULL,
      'idlist-delimiter' => MigrateTools::DEFAULT_ID_LIST_DELIMITER,
      'update' => NULL,
      'force' => NULL,
      'execute-dependencies' => NULL,
      'skip-progress-bar' => FALSE,
      'continue-on-failure' => FALSE,
      'sync' => FALSE,
    ];

    private ?MigrateToolsCommands $commands = NULL;
    private MigrationPluginManagerInterface $migrationPluginManager;

    /**
     * {@inheritdoc}
     */
    public function setUp(): void {
      parent::setUp();
      $this->installConfig('migrate_plus');
      $this->installConfig('migrate_tools_test');
      $this->installEntitySchema('taxonomy_term');
      $this->installEntitySchema('user');
      $this->installSchema('user', ['users_data']);
      $this->installSchema('migrate_tools', ['migrate_tools_sync_source_ids']);
      $this->migrationPluginManager = $this->container->get('plugin.manager.migration');
      // Handle Drush 10 vs Drush 11 differences.
      $logger_class = class_exists(DrushLoggerManager::class) ? DrushLoggerManager::class : LoggerInterface::class;
      $this->logger = $this->prophesize($logger_class)->reveal();
      $this->commands = new MigrateToolsCommands(
        $this->migrationPluginManager,
        $this->container->get('date.formatter'),
        $this->container->get('entity_type.manager'),
        $this->container->get('keyvalue'));
      $this->commands->setLogger($this->logger);
    }

    /**
     * Tests drush ms.
     */
    public function testStatus(): void {
      $this->executeMigration('fruit_terms');
      $result = $this->commands->status('fruit_terms', [
        'group' => NULL,
        'tag' => NULL,
        'names-only' => FALSE,
      ]);
      $rows = $result->getArrayCopy();
      $this->assertCount(1, $rows);
      $row = reset($rows);
      $this->assertSame('fruit_terms', $row['id']);
      $this->assertSame(3, $row['total']);
      $this->assertSame(3, $row['imported']);
      $this->assertSame('Idle', $row['status']);

      // Migrate status should not display migrate_drupal migrations if no
      // source database is defined.
      \Drupal::service('module_installer')->uninstall([
        'migrate_tools_test',
      ]);
      $this->enableModules(['migrate_drupal']);
      $this->migrationPluginManager->clearCachedDefinitions();
      $rows = $this->commands->status();
      $this->assertEmpty($rows);
    }

    /**
     * Tests that a failing status throws an exception (i.e. exit code).
     */
    public function testFailingStatusThrowsException(): void {
      $this->expectException(\Exception::class);
      $this->expectExceptionMessage('The "does_not_exist" plugin does not exist.');
      $this->commands->status('invalid_plugin');
    }

    /**
     * Tests drush mim.
     *
     * @throws \Drupal\Component\Plugin\Exception\PluginException
     */
    public function testImport(): void {
      /** @var \Drupal\migrate\Plugin\MigrationInterface $migration */
      $migration = $this->migrationPluginManager->createInstance('fruit_terms');
      $id_map = $migration->getIdMap();
      $this->commands->import('fruit_terms', array_merge($this->importBaseOptions, ['idlist' => 'Apple']));
      $this->assertSame(1, $id_map->importedCount());
      $this->commands->import('fruit_terms', $this->importBaseOptions);
      $this->assertSame(3, $id_map->importedCount());
      $this->commands->import('fruit_terms', array_merge($this->importBaseOptions, [
        'idlist' => 'Apple',
        'update' => TRUE,
      ]));
      $this->assertCount(0, $id_map->getRowsNeedingUpdate(100));
    }

    /**
     * Tests that a failing import throws an exception (i.e. exit code).
     */
    public function testFailingImportThrowsException(): void {
      $this->expectException(\Exception::class);
      $this->expectExceptionMessage('source_exception migration failed.');
      $this->commands->import('source_exception', $this->importBaseOptions);
    }

    /**
     * Tests drush mmsg.
     *
     * @throws \Drupal\Component\Plugin\Exception\PluginException
     */
    public function testMessages(): void {
      $this->executeMigration('fruit_terms');
      $message = $this->getRandomGenerator()->string(16);
      /** @var \Drupal\migrate\Plugin\MigrationInterface $migration */
      $migration = $this->migrationPluginManager->createInstance('fruit_terms');
      $id_map = $migration->getIdMap();
      $id_map->saveMessage(['name' => 'Apple'], $message);
      /** @var \Consolidation\OutputFormatters\StructuredData\RowsOfFields $result */
      $result = $this->commands->messages('fruit_terms', [
        'csv' => FALSE,
        'idlist' => NULL,
        'idlist-delimiter' => MigrateTools::DEFAULT_ID_LIST_DELIMITER,
      ]);
      $rows = $result->getArrayCopy();
      $this->assertSame($message, $rows[0]['message']);
    }

    /**
     * Tests that a failing messages throws an exception (i.e. exit code).
     */
    public function testFailingMessagesThrowsException(): void {
      $this->expectException(\Exception::class);
      $this->expectExceptionMessage('Migration does_not_exist does not exist');
      $this->commands->messages('does_not_exist', [
        'csv' => FALSE,
        'idlist' => NULL,
        'idlist-delimiter' => MigrateTools::DEFAULT_ID_LIST_DELIMITER,
      ]);
    }

    /**
     * Tests drush mr.
     */
    public function testRollback(): void {
      $this->executeMigration('fruit_terms');
      /** @var \Drupal\migrate\Plugin\MigrationInterface $migration */
      $migration = $this->migrationPluginManager->createInstance('fruit_terms');
      $id_map = $migration->getIdMap();
      $this->assertSame(3, $id_map->importedCount());
      $this->commands->rollback('fruit_terms', $this->importBaseOptions);
      $this->assertSame(0, $id_map->importedCount());
    }

    /**
     * Tests that a failing rollback throws an exception (i.e. exit code).
     */
    public function testFailingRollbackThrowsException(): void {
      $this->expectException(\Exception::class);
      $this->expectExceptionMessage('source_exception migration failed');
      /** @var \Drupal\migrate\Plugin\MigrationInterface $migration */
      $migration = $this->migrationPluginManager->createInstance('source_exception');
      $migration->setStatus(MigrationInterface::STATUS_IMPORTING);
      $this->commands->rollback('source_exception', $this->importBaseOptions);
    }

    /**
     * Tests drush mrs.
     *
     * @throws \Drupal\Component\Plugin\Exception\PluginException
     */
    public function testReset(): void {
      /** @var \Drupal\migrate\Plugin\MigrationInterface $migration */
      $migration = $this->migrationPluginManager->createInstance('fruit_terms');
      $migration->setStatus(MigrationInterface::STATUS_IMPORTING);
      $status = $this->commands->status('fruit_terms', [
        'group' => NULL,
        'tag' => NULL,
        'names-only' => FALSE,
      ])->getArrayCopy()[0]['status'];
      $this->assertSame('Importing', $status);
      $this->commands->resetStatus('fruit_terms');
      $this->assertSame(MigrationInterface::STATUS_IDLE, $migration->getStatus());

    }

    /**
     * Tests that a failing reset status throws an exception (i.e. exit code).
     */
    public function testFailingResetStatusThrowsException(): void {
      $this->expectException(\Exception::class);
      $this->expectExceptionMessage('Migration does_not_exist does not exist');
      $this->commands->resetStatus('does_not_exist');
    }

    /**
     * Tests drush mst.
     *
     * @throws \Drupal\Component\Plugin\Exception\PluginException
     */
    public function testStop(): void {
      /** @var \Drupal\migrate\Plugin\MigrationInterface $migration */
      $migration = $this->migrationPluginManager->createInstance('fruit_terms');
      $migration->setStatus(MigrationInterface::STATUS_IMPORTING);
      $this->commands->stop('fruit_terms');
      $this->assertSame(MigrationInterface::STATUS_STOPPING, $migration->getStatus());
    }

    /**
     * Tests that a failing stop throws an exception (i.e. exit code).
     */
    public function testFailingStopThrowsException(): void {
      $this->expectException(\Exception::class);
      $this->expectExceptionMessage('Migration does_not_exist does not exist');
      $this->commands->stop('does_not_exist');
    }

    /**
     * Tests drush mfs.
     */
    public function testFieldsSource(): void {
      /** @var \Consolidation\OutputFormatters\StructuredData\RowsOfFields $result */
      $result = $this->commands->fieldsSource('fruit_terms');
      $rows = $result->getArrayCopy();
      $this->assertCount(1, $rows);
      $this->assertSame('name', $rows[0]['machine_name']);
      $this->assertSame('name', $rows[0]['description']);
    }

    /**
     * Tests that a failing fields source throws an exception (i.e. exit code).
     */
    public function testFailingFieldsSourceThrowsException(): void {
      $this->expectException(\Exception::class);
      $this->expectExceptionMessage('Migration does_not_exist does not exist');
      $this->commands->fieldsSource('does_not_exist');
    }

  }
}

namespace {
  if (!function_exists('dt')) {

    /**
     * Stub for dt().
     *
     * @param string $message
     *   The text.
     * @param array $replace
     *   The replacement values.
     *
     *   The text.
     */
    function dt($message, array $replace = []): string {
      return strtr($message, $replace);
    }

  }

  if (!function_exists('drush_op')) {

    /**
     * Stub for drush_op.
     *
     * @param callable $callable
     *   The function to call.
     */
    function drush_op(callable $callable) {
      $args = func_get_args();
      array_shift($args);
      return call_user_func_array($callable, $args);
    }

  }

}
