<?php

namespace Drupal\Tests\config_sync\Kernel;

use Drupal\config_distro\Event\ConfigDistroEvents;
use Drupal\config_distro\Event\DistroStorageImportEvent;
use Drupal\config_snapshot\ConfigSnapshotStorageTrait;
use Drupal\config_snapshot\Entity\ConfigSnapshot;
use Drupal\config_sync\ConfigSyncListerInterface;
use Drupal\config_sync\ConfigSyncSnapshotterInterface;
use Drupal\Core\Config\ConfigImporter;
use Drupal\Core\Config\StorageComparer;
use Drupal\Core\Config\StorageInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\NodeType;

/**
 * Tests importing configuration entities using various import modes.
 *
 * @group config_sync
 */
class UpdateModeTest extends KernelTestBase {

  use ConfigSnapshotStorageTrait;

  /**
   * {@inheritDoc}
   */
  protected $preserveGlobalState = TRUE;

  /**
   * Storage for the test module's snapshot.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $testSnapshotStorage;

  /**
   * Config Importer object used for testing.
   *
   * @var \Drupal\Core\Config\ConfigImporter
   */
  protected $configImporter;

  /**
   * Names of test module node types.
   *
   * @var array
   */
  protected $nodeTypeNames = [
    1 => 'config_sync_test_1',
    2 => 'config_sync_test_2',
    3 => 'config_sync_test_3',
    4 => 'config_sync_test_4',
  ];

  /**
   * Names of test module config items.
   *
   * @var array
   */
  protected $configNames = [];

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'system',
    'field',
    'filter',
    'text',
    'user',
    'node',
    'config_distro',
    'config_distro_filter',
    'config_filter',
    'config_merge',
    'config_normalizer',
    'config_provider',
    'config_snapshot',
    'config_update',
    'config_sync',
    'config_sync_test',
  ];

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('node');
    $this->installEntitySchema('config_snapshot');
    $this->installConfig([
      'system',
      'user',
      'field',
      'filter',
      'text',
      'node',
      'config_sync_test',
    ]);

    // Refresh the extension snapshot, since this won't have been done on
    // module install.
    $this->container->get('config_sync.snapshotter')->refreshExtensionSnapshot('module', ['config_sync_test'], ConfigSyncSnapshotterInterface::SNAPSHOT_MODE_INSTALL);

    // Load and customize the node type provided by config_sync_test_1 module.
    $content_type_1 = NodeType::load($this->nodeTypeNames[1]);
    $content_type_1
      ->set('name', 'Custom name')
      ->set('description', 'Prior description')
      ->set('help', 'Custom help')
      ->save();

    // Load the configuration snapshot for the test module.
    $test_module_snapshot = ConfigSnapshot::load(ConfigSyncSnapshotterInterface::CONFIG_SNAPSHOT_SET . '.module.config_sync_test');

    // Load and customize the node type snapshot, simulating a prior install
    // state.
    $this->configNames[1] = $content_type_1->getEntityType()->getConfigPrefix() . '.' . $content_type_1->id();
    $content_type_1_snapshot = $test_module_snapshot->getItem(StorageInterface::DEFAULT_COLLECTION, $this->configNames[1]);
    $content_type_1_snapshot['name'] = 'Prior name';
    $content_type_1_snapshot['description'] = 'Prior description';
    $test_module_snapshot
      ->setItem(StorageInterface::DEFAULT_COLLECTION, $this->configNames[1], $content_type_1_snapshot)
      ->save();

    // Remove the second node type from both the snapshot and the active
    // configuration. This simulates an item that is newly provided.
    $content_type_2 = NodeType::load($this->nodeTypeNames[2]);
    $this->configNames[2] = $content_type_2->getEntityType()->getConfigPrefix() . '.' . $content_type_2->id();
    $test_module_snapshot
      ->clearItem(StorageInterface::DEFAULT_COLLECTION, $this->configNames[2])
      ->save();
    $content_type_2->delete();

    // Delete the third node type. This covers an item installed and
    // later deleted.
    $content_type_3 = NodeType::load($this->nodeTypeNames[3]);
    $this->configNames[3] = $content_type_3->getEntityType()->getConfigPrefix() . '.' . $content_type_3->id();
    $content_type_3->delete();

    // Modify the fourth node. This covers an item that has been customized and
    // for which no update is available.
    $content_type_4 = NodeType::load($this->nodeTypeNames[4]);
    $this->configNames[4] = $content_type_4->getEntityType()->getConfigPrefix() . '.' . $content_type_4->id();
    $content_type_4
      ->set('name', 'Custom name')
      ->save();

    $this->testSnapshotStorage = $this->getConfigSnapshotStorage(ConfigSyncSnapshotterInterface::CONFIG_SNAPSHOT_SET, 'module', 'config_sync_test');
    // We deleted the snapshot for config_sync_test_2.
    $expected_snapshot_items = $this->configNames;
    unset($expected_snapshot_items[2]);
    $expected_snapshot_items = array_values($expected_snapshot_items);
    $snapshot_items = $this->testSnapshotStorage->listAll();
    $this->assertSame($snapshot_items, $expected_snapshot_items, 'Snapshot items match those expected.');
  }

  /**
   * Helper method for changing config_sync.update_mode.
   */
  protected function setUpdateMode($update_mode) {
    $this->container->get('state')->set('config_sync.update_mode', $update_mode);

    // Rebuild the container to update the config distro storage.
    $this->container->get('kernel')->rebuildContainer();

    // Set up the ConfigImporter object for testing.
    $storage_comparer = new StorageComparer(
      $this->container->get('config_distro.storage.distro'),
      $this->container->get('config.storage')
    );
    $this->configImporter = new ConfigImporter(
      $storage_comparer->createChangelist(),
      $this->container->get('event_dispatcher'),
      $this->container->get('config.manager'),
      $this->container->get('lock'),
      $this->container->get('config.typed'),
      $this->container->get('module_handler'),
      $this->container->get('module_installer'),
      $this->container->get('theme_handler'),
      $this->container->get('string_translation'),
      $this->container->get('extension.list.module'),
      $this->container->get('extension.list.theme')
    );
  }

  /**
   * Tests merge update mode.
   */
  public function testUpdateModeMerge() {
    // Set update mode to merge.
    $this->setUpdateMode(ConfigSyncListerInterface::UPDATE_MODE_MERGE);

    $creates = $this->configImporter->getUnprocessedConfiguration('create');
    $updates = $this->configImporter->getUnprocessedConfiguration('update');
    $this->assertEquals(0, count($this->configImporter->getUnprocessedConfiguration('delete')), 'There are no configuration items to delete.');
    // node.type.config_sync_test_2 was deleted from both the snapshot and
    // the active configuration and so should be created.
    // node.type.config_sync_test_3 was deleted from active but should not be
    // restored since it is snapshotted.
    $expected_creates = [
      $this->configNames[2],
    ];
    $this->assertSame($creates, $expected_creates, 'Create operations match those expected.');
    // For node.type.config_sync_test_1, the snapshot differs from the current
    // provided value and not all of the differences are customized in the
    // active configuration.
    $expected_updates = [
      $this->configNames[1],
    ];
    $this->assertSame($updates, $expected_updates, 'Update operations match those expected.');

    $this->configImporter->import();
    $this->container->get('event_dispatcher')->dispatch(new DistroStorageImportEvent(), ConfigDistroEvents::IMPORT);

    // Verify that the expected config changes were made.
    $node_type_1 = NodeType::load($this->nodeTypeNames[1]);
    $this->assertEquals('Custom name', $node_type_1->label());
    $this->assertEquals('Provided description', $node_type_1->get('description'));
    $this->assertEquals('Custom help', $node_type_1->get('help'));

    $this->verifySnapshot();
  }

  /**
   * Tests partial reset update mode.
   */
  public function testUpdateModePartialReset() {
    // Set update mode to partial reset.
    $this->setUpdateMode(ConfigSyncListerInterface::UPDATE_MODE_PARTIAL_RESET);

    $creates = $this->configImporter->getUnprocessedConfiguration('create');
    $updates = $this->configImporter->getUnprocessedConfiguration('update');
    $this->assertEquals(0, count($this->configImporter->getUnprocessedConfiguration('delete')), 'There are no configuration items to delete.');
    // node.type.config_sync_test_2 was deleted from both the snapshot and
    // the active configuration and so should be created.
    // node.type.config_sync_test_3 was deleted from active but should not be
    // restored since it is snapshotted.
    $expected_creates = [
      $this->configNames[2],
    ];
    $this->assertSame($creates, $expected_creates, 'Create operations match those expected.');
    // For node.type.config_sync_test_1, the snapshot differs from the current
    // provided value.
    $expected_updates = [
      $this->configNames[1],
    ];
    $this->assertSame($updates, $expected_updates, 'Update operations match those expected.');

    $this->configImporter->import();
    $this->container->get('event_dispatcher')->dispatch(new DistroStorageImportEvent(), ConfigDistroEvents::IMPORT);

    // Verify that the expected config changes were made.
    $node_type_1 = NodeType::load($this->nodeTypeNames[1]);
    $this->assertEquals('Provided name', $node_type_1->label());
    $this->assertEquals('Provided description', $node_type_1->get('description'));
    $this->assertEquals('Provided help', $node_type_1->get('help'));

    $this->verifySnapshot();
  }

  /**
   * Tests full reset update mode.
   */
  public function testUpdateModeFullReset() {
    // Set update mode to partial reset.
    $this->setUpdateMode(ConfigSyncListerInterface::UPDATE_MODE_FULL_RESET);

    $creates = $this->configImporter->getUnprocessedConfiguration('create');
    $updates = $this->configImporter->getUnprocessedConfiguration('update');
    $this->assertEquals(0, count($this->configImporter->getUnprocessedConfiguration('delete')), 'There are no configuration items to delete.');
    // node.type.config_sync_test_2 was deleted from both the snapshot and
    // the active configuration and so should be created.
    // node.type.config_sync_test_3 was deleted from active and should be
    // restored even though it is snapshotted.
    $expected_creates = [
      $this->configNames[2],
      $this->configNames[3],
    ];
    $this->assertSame($creates, $expected_creates, 'Create operations match those expected.');
    // For node.type.config_sync_test_1, the snapshot differs from the current
    // provided value and from the active value.
    // For node.type.config_sync_test_4, the snapshot differs from the active
    // value.
    $expected_updates = [
      $this->configNames[1],
      $this->configNames[4],
    ];
    $this->assertSame($updates, $expected_updates, 'Update operations match those expected.');

    $this->configImporter->import();
    $this->container->get('event_dispatcher')->dispatch(new DistroStorageImportEvent(), ConfigDistroEvents::IMPORT);

    // Verify that all provided items are at their provided state.
    foreach (array_keys($this->configNames) as $index) {
      $node_type = NodeType::load($this->nodeTypeNames[$index]);
      $this->assertEquals('Provided name', $node_type->label());
      $this->assertEquals('Provided description', $node_type->get('description'));
      $this->assertEquals('Provided help', $node_type->get('help'));
    }

    $this->verifySnapshot();
  }

  /**
   * Helper method for verifying configuration snapshot after importing.
   */
  protected function verifySnapshot() {
    // Verify that all provided items are now snapshotted.
    $expected_snapshot_items = array_values($this->configNames);
    $test_snapshot_storage = $this->getConfigSnapshotStorage(ConfigSyncSnapshotterInterface::CONFIG_SNAPSHOT_SET, 'module', 'config_sync_test');
    $snapshot_items = $test_snapshot_storage->listAll();
    $this->assertSame($snapshot_items, $expected_snapshot_items, 'Snapshot items match those expected.');

    // Verify that the snapshot is now fully updated.
    $extension_storage = $this->container->get('config_update.extension_storage');
    foreach (array_keys($this->configNames) as $index) {
      $test_snapshot_value = $test_snapshot_storage->read($this->configNames[$index]);
      // Omit '_core.default_config_hash' values in snapshot for verification.
      // @todo Remove this workaround once a fix for
      //   https://drupal.org/i/3056249 is included in a config_update module
      //   release.
      if (is_array($test_snapshot_value) && isset($test_snapshot_value['_core'])) {
        unset($test_snapshot_value['_core']);
      }
      $this->assertEquals($extension_storage->read($this->configNames[$index]), $test_snapshot_value);
    }
  }

}
