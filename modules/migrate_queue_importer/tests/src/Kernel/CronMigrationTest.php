<?php

namespace Drupal\Tests\migrate_queue_importer\Kernel;

use Drupal\Core\Database\Database;
use Drupal\KernelTests\KernelTestBase;

/**
 * Kernel tests to ensure modules functionality.
 *
 * @group migrate_queue_importer
 */
class CronMigrationTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'system',
    'dblog',
    'user',
    'field',
    'node',
    'text',
    'taxonomy',
    'migrate',
    'migrate_plus',
    'migrate_tools',
    'migrate_queue_importer',
    'migrate_queue_importer_test',
  ];

  /**
   * Cron.
   *
   * @var \Drupal\Core\Cron
   */
  protected $cron;

  /**
   * The migration manager service.
   *
   * @var \Drupal\migrate\Plugin\MigrationPluginManagerInterface
   */
  protected $migrationManager;

  /**
   * The cron_migration storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storage;

  /**
   * The migrate_queue_importer queue.
   *
   * @var \Drupal\Core\Queue\QueueInterface
   */
  protected $queue;

  /**
   * DB connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installSchema('dblog', ['watchdog']);
    $this->installConfig(['system']);
    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installEntitySchema('cron_migration');
    $this->installConfig(['migrate_queue_importer']);
    $this->installConfig(['migrate_queue_importer_test']);
    $this->installSchema('migrate_tools', ['migrate_tools_sync_source_ids']);

    $this->cron = $this->container->get('cron');
    $this->migrationManager = $this->container->get('plugin.manager.migration');
    $this->storage = $this->container->get('entity_type.manager')->getStorage('cron_migration');
    $this->queue = $this->container->get('queue')->get('migrate_queue_importer');
    $this->connection = Database::getConnection();
  }

  /**
   * Test cron migration config import.
   */
  public function testCronMigrationConfigImport() {
    /** @var \Drupal\migrate_queue_importer\Entity\CronMigration $cron_migration */
    $cron_migration = $this->storage->load('nodes_migrate_queue_importer_nodes');

    // Check that the stored menu link meeting the expectations.
    $this->assertNotNull($cron_migration);
    $this->assertEquals('nodes_migrate_queue_importer_nodes', $cron_migration->id());
    $this->assertEquals('Nodes (migrate_queue_importer_nodes)', $cron_migration->label());
    $this->assertEquals('migrate_queue_importer_nodes', $cron_migration->migration);
    $this->assertEquals(60, $cron_migration->time);
    $this->assertEquals(TRUE, $cron_migration->update);
    $this->assertEquals(FALSE, $cron_migration->sync);
    $this->assertEquals(FALSE, $cron_migration->ignore_dependencies);
    $this->assertEquals(TRUE, $cron_migration->status());
  }

  /**
   * Test actual migration on cron run.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function testCronMigrationRunOnCron() {
    $migration = $this->migrationManager->createInstance('migrate_queue_importer_nodes');

    $this->assertEquals('Idle', $migration->getStatusLabel(), 'Migration is not idle.');
    $this->assertEquals(FALSE, $migration->allRowsProcessed());

    $this->cron->run();
    $this->assertEquals('Idle', $migration->getStatusLabel(), 'Migration is not idle.');
    $this->assertEquals(TRUE, $migration->allRowsProcessed());

    // Test the db_log.
    $query = $this->connection->select('watchdog');
    $query->fields('watchdog', ['type', 'message', 'variables'])
      ->condition('type', 'migrate_queue_importer');
    $result = $query->execute()->fetchAll();

    $this->assertEquals(1, count($result));
    $this->assertEquals('%label has been scheduled for import.', $result[0]->message);
    $this->assertEquals('a:1:{s:6:"%label";s:5:"Nodes";}', $result[0]->variables);

    // Test that the nodes are actually created.
    $nodes = $this->container->get('entity_type.manager')->getStorage('node')->loadByProperties(['type' => 'migrate_queue_importer_node_type']);
    $this->assertEquals(3, count($nodes));
  }

  /**
   * Tests _migrate_queue_importer_check_dependencies().
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function testDependencyDetection() {
    $migration = $this->migrationManager->createInstance('migrate_queue_importer_terms');
    $this->assertEquals(0, $this->queue->numberOfItems());

    _migrate_queue_importer_check_dependencies($migration, TRUE, TRUE, [
      'migrationManager' => $this->migrationManager,
      'queue' => $this->queue,
      'logger' => \Drupal::logger('migrate_queue_importer'),
    ]);

    $this->assertEquals(1, $this->queue->numberOfItems());

    // Test the db_log.
    $query = $this->connection->select('watchdog');
    $query->fields('watchdog', ['type', 'message', 'variables'])
      ->condition('type', 'migrate_queue_importer');
    $result = $query->execute()->fetchAll();

    $this->assertEquals(1, count($result));
    $this->assertEquals('%label has been scheduled for import.', $result[0]->message);
    $this->assertEquals('a:1:{s:6:"%label";s:5:"Nodes";}', $result[0]->variables);
  }

}
