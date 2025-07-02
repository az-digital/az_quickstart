<?php

declare(strict_types=1);

namespace Drupal\Tests\config_split\Kernel;

use Drupal\Core\Config\MemoryStorage;
use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\config_filter\Kernel\ConfigStorageTestTrait;

/**
 * Test that splits which exist only in the sync storage get imported anyway.
 *
 * @group config_split
 */
class NewSplitImportTest extends KernelTestBase {

  use ConfigStorageTestTrait;
  use SplitTestTrait;

  /**
   * Modules to enable.
   *
   * @var string[]
   */
  protected static $modules = [
    'system',
    'config_split',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installConfig(['system']);
  }

  /**
   * Test that new splits are imported the first time.
   */
  public function testImportingNewSplits() {
    // Populate the sync storage.
    $sync = $this->getSyncFileStorage();
    $this->copyConfig($this->getActiveStorage(), $sync);

    $sync->write('config_split.config_split.new_active',
      [
        'uuid' => 'a544eb0a-f7ec-4b97-8394-23b53abee88e',
        'langcode' => 'en',
        'status' => TRUE,
        'dependencies' => [],
        'id' => 'new_active',
        'label' => 'New split',
        'description' => 'This one is created directly in the sync storage',
        'weight' => 0,
        'storage' => 'collection',
        'folder' => '',
        'module' => [],
        'theme' => [],
        'complete_list' => ['system.menu.account'],
        'partial_list' => [],
      ]
    );

    $sync->write('config_split.config_split.new_overwritten',
      [
        'uuid' => '527302d5-c79e-4c34-a8fd-133d9a716e45',
        'langcode' => 'en',
        'status' => TRUE,
        'dependencies' => [],
        'id' => 'new_overwritten',
        'label' => 'New but deactivated',
        'description' => 'This one is created directly in the sync storage',
        'weight' => 0,
        'storage' => 'collection',
        'folder' => '',
        'module' => [],
        'theme' => [],
        'complete_list' => ['system.menu.footer'],
        'partial_list' => [],
      ]
    );

    $expected = new MemoryStorage();
    $this->copyConfig($this->getActiveStorage(), $expected);

    // Move the config to the split collections.
    $sync->createCollection('split.new_active')->write('system.menu.account', $sync->read('system.menu.account'));
    $sync->delete('system.menu.account');
    // The inactive split collection will remain in the storage while the active
    // one will be removed during the transformation.
    $expected->createCollection('split.new_overwritten')->write('system.menu.footer', $sync->read('system.menu.footer'));
    $sync->createCollection('split.new_overwritten')->write('system.menu.footer', $sync->read('system.menu.footer'));
    $sync->delete('system.menu.footer');

    foreach (['new_active', 'new_overwritten'] as $split) {
      $name = "config_split.config_split.$split";
      $expected->write($name, $sync->read($name));
    }
    // Override the config.
    $GLOBALS['config']['config_split.config_split.new_overwritten']['status'] = FALSE;
    $expected->delete('system.menu.footer');

    self::assertStorageEquals($expected, $this->getImportStorage());
  }

  /**
   * Test that a split can split itself.
   */
  public function testSplittingSplit() {
    $config = $this->createSplitConfig('test_split', [
      'complete_list' => [
        'config_split.*',
      ],
    ]);

    $export = $this->getExportStorage();
    $splitPreview = $this->getSplitPreviewStorage($config, $export);

    // Assert that the export storage can be imported without the split.
    $this->validateImport($export);

    static::assertNotContains($config->getName(), $export->listAll());
    static::assertContains($config->getName(), $splitPreview->listAll());

    // Write the export to the file system and assert the import to work.
    // This is the most important thing we need to work, it won't if the split
    // is not merged correctly.
    // Of course on a site where the split doesn't already exist there is no way
    // to import the split via a config import other than "individual import".
    $this->copyConfig($export, $this->getSyncFileStorage());
    $this->copyConfig($splitPreview, $this->getSplitSourceStorage($config));
    static::assertStorageEquals($this->getActiveStorage(), $this->getImportStorage());
  }

}
