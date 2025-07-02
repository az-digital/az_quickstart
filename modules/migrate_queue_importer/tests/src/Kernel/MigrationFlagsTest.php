<?php

namespace Drupal\Tests\migrate_queue_importer\Kernel;

use Drupal\Core\Database\Database;
use Drupal\Core\StreamWrapper\PublicStream;
use Drupal\Core\StreamWrapper\StreamWrapperInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\migrate_queue_importer\Entity\CronMigration;

/**
 * Kernel tests to ensure modules functionality.
 *
 * @group migrate_queue_importer
 */
class MigrationFlagsTest extends KernelTestBase {

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
   * The source XML data.
   *
   * @var string
   */
  protected $sourceData;

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
   * @var \Drupal\Core\Entity\EntityTypeManager
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
    $this->installSchema('node', ['node_access']);
    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installEntitySchema('cron_migration');
    $this->installConfig(['migrate_queue_importer']);
    $this->installConfig(['migrate_queue_importer_test']);
    $this->installSchema('migrate_tools', ['migrate_tools_sync_source_ids']);

    // Setup the file system so we create the source XML.
    $this->container->get('stream_wrapper_manager')->registerWrapper('public', PublicStream::class, StreamWrapperInterface::NORMAL);
    $fs = \Drupal::service('file_system');
    $fs->mkdir('public://sites/default/files', NULL, TRUE);

    // The source data for this test.
    $this->sourceData = <<<'EOD'
<?xml version="1.0" encoding="UTF-8" ?>
<root>
  <row>
    <title>Title 1</title>
    <id>1</id>
  </row>
  <row>
    <title>Title 2</title>
    <id>2</id>
  </row>
  <row>
    <title>Title 3</title>
    <id>3</id>
  </row>
</root>
EOD;

    // Write the data to the filepath given in the test migration.
    file_put_contents('public://import.xml', $this->sourceData);

    // Create a cron migration.
    $cron_migration = CronMigration::create([
      'label' => 'Nodes Flag Test',
      'status' => TRUE,
      'id' => 'nodes_flag_test',
      'migration' => 'migrate_queue_importer_nodes_xml_local_data',
      'time' => 0,
      'update' => TRUE,
      'sync' => TRUE,
      'ignore_dependencies' => FALSE,
    ]);
    $cron_migration->save();

    // Deactivate the other test cron migration.
    $nodes_cron_migration = CronMigration::load('nodes_migrate_queue_importer_nodes');
    $nodes_cron_migration->setStatus(FALSE);
    $nodes_cron_migration->save();

    $this->cron = $this->container->get('cron');
    $this->migrationManager = $this->container->get('plugin.manager.migration');
    $this->storage = $this->container->get('entity_type.manager');
    $this->queue = $this->container->get('queue')->get('migrate_queue_importer');
    $this->connection = Database::getConnection();

    $this->cron->run();
  }

  /**
   * Test the update flag.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function testUpdateFlag() {
    $nodes = $this->storage->getStorage('node')->loadByProperties(['type' => 'node_type_local']);
    $node = reset($nodes);
    $this->assertCount(3, $nodes);
    $this->assertEquals('Title 1', $node->label());

    // Change title 1 in data.
    $this->sourceData = str_replace('Title 1', 'Title 11', $this->sourceData);
    file_put_contents('public://import.xml', $this->sourceData);

    // Wait before next cron run.
    sleep(1);

    // Update.
    $this->cron->run();

    $query = $this->connection->select('watchdog');
    $query->fields('watchdog', ['type', 'message', 'variables'])
      ->condition('type', 'migrate');
    $result = $query->execute()->fetchAll();

    $this->assertEquals('Processed 3 items (0 created, 3 updated, 0 failed, 0 ignored) - done with \'migrate_queue_importer_nodes_xml_local_data\'', end($result)->message);

    $nodes = $this->storage->getStorage('node')->loadByProperties(['type' => 'node_type_local']);
    $node_1 = $this->storage->getStorage('node')->loadByProperties([
      'type' => 'node_type_local',
      'title' => 'Title 1',
    ]);
    $node_11 = $this->storage->getStorage('node')->loadByProperties([
      'type' => 'node_type_local',
      'title' => 'Title 11',
    ]);

    $this->assertCount(3, $nodes);
    $this->assertCount(0, $node_1);
    $this->assertCount(1, $node_11);
  }

  /**
   * Test the sync flag.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function testSyncFlag() {
    $nodes = $this->storage->getStorage('node')->loadByProperties(['type' => 'node_type_local']);
    $node = $this->storage->getStorage('node')->loadByProperties([
      'type' => 'node_type_local',
      'title' => 'Title 3',
    ]);

    $this->assertCount(3, $nodes);
    $this->assertCount(1, $node);

    // Remove one item from source data.
    $this->sourceData = <<<'EOD'
<?xml version="1.0" encoding="UTF-8" ?>
<root>
  <row>
    <title>Title 1</title>
    <id>1</id>
  </row>
  <row>
    <title>Title 2</title>
    <id>2</id>
  </row>
</root>
EOD;
    file_put_contents('public://import.xml', $this->sourceData);

    // Wait before next cron run.
    sleep(1);

    drupal_flush_all_caches();

    // Update.
    $this->cron->run();

    $nodes = $this->storage->getStorage('node')->loadByProperties(['type' => 'node_type_local']);
    $node = $this->storage->getStorage('node')->loadByProperties([
      'type' => 'node_type_local',
      'title' => 'Title 3',
    ]);
    $this->assertCount(2, $nodes);
    $this->assertCount(0, $node);
  }

}
