<?php

namespace Drupal\Tests\config_split\Kernel;

use Drupal\config_split\Config\ConfigPatch;
use Drupal\config_split\Config\SplitCollectionStorage;
use Drupal\Core\Config\MemoryStorage;
use Drupal\Core\Config\StorageCopyTrait;
use Drupal\Core\Config\StorageInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Tests\config_filter\Kernel\ConfigStorageTestTrait;

/**
 * Test the splitting and merging.
 *
 * These are the integration tests to assert that the module has the behavior
 * on import and export that we expect. This is supposed to not go into internal
 * details of how config split achieves this.
 *
 * @group config_split
 */
class SplitMergeTest extends KernelTestBase {

  use ConfigStorageTestTrait;
  use SplitTestTrait;
  use StorageCopyTrait;

  /**
   * Modules to enable.
   *
   * @var string[]
   */
  protected static $modules = [
    'system',
    'language',
    'user',
    'node',
    'field',
    'text',
    'config',
    'config_test',
    'config_exclude_test',
    'config_split',
    'config_filter',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Make sure there is a good amount of config to play with.
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    // The module config_test has translations and config_exclude_test has
    // config with dependencies.
    $this->installConfig(['system', 'field', 'config_test', 'config_exclude_test']);

    // Set up multilingual.
    ConfigurableLanguage::createFromLangcode('fr')->save();
    ConfigurableLanguage::createFromLangcode('de')->save();
  }

  /**
   * Data provider to test with all storages.
   *
   * @return string[][]
   *   The different storage types.
   */
  public static function storageAlternativesProvider(): array {
    return [['folder'], ['collection'], ['database']];
  }

  /**
   * Test a simple export split.
   *
   * @dataProvider storageAlternativesProvider
   */
  public function testSimpleSplitExport($storage) {
    // Simple split with default configuration.
    $config = $this->createSplitConfig('test_split', [
      'storage' => $storage,
      'module' => ['config_test' => 0],
    ]);

    $active = $this->getActiveStorage();
    $expectedExport = new MemoryStorage();
    $expectedSplit = new MemoryStorage();

    // Set up expectations.
    foreach (array_merge($active->getAllCollectionNames(), [StorageInterface::DEFAULT_COLLECTION]) as $collection) {
      $active = $active->createCollection($collection);
      $expectedExport = $expectedExport->createCollection($collection);
      $expectedSplit = $expectedSplit->createCollection($collection);
      foreach ($active->listAll() as $name) {
        $data = $active->read($name);
        if ($name === 'core.extension') {
          // We split off the module.
          unset($data['module']['config_test']);
        }

        if (strpos($name, 'config_test') !== FALSE || in_array($name, ['system.menu.exclude-test', 'system.menu.indirect-exclude-test'])) {
          // Expect config that depends on config_test directly and indirectly
          // to be split off.
          $expectedSplit->write($name, $data);
        }
        else {
          $expectedExport->write($name, $data);
        }
      }
    }
    if ($storage === 'collection') {
      $temp = new SplitCollectionStorage($expectedExport, $config->get('id'));
      self::replaceStorageContents($expectedSplit, $temp);
    }

    $export = $this->getExportStorage();
    static::assertStorageEquals($expectedExport, $export);
    static::assertStorageEquals($expectedSplit, $this->getSplitPreviewStorage($config, $export));

    // Write the export to the file system and assert the import to work.
    $this->copyConfig($expectedExport, $this->getSyncFileStorage());
    $this->copyConfig($expectedSplit, $this->getSplitSourceStorage($config));
    static::assertStorageEquals($active, $this->getImportStorage());
  }

  /**
   * Test complete and conditional split export.
   *
   * @dataProvider storageAlternativesProvider
   */
  public function testCompleteAndConditionalSplitExport($storage) {

    $config = $this->createSplitConfig('test_split', [
      'storage' => $storage,
      'complete_list' => ['config_test.types'],
      'partial_list' => ['config_test.system'],
    ]);

    $active = $this->getActiveStorage();
    // Export the configuration to sync without filtering.
    $this->copyConfig($active, $this->getSyncFileStorage());

    // Change the gray listed config to see if it is exported the same.
    $originalSystem = $this->config('config_test.system')->getRawData();
    $this->config('config_test.system')->set('foo', 'baz')->save();
    $systemPatch = ConfigPatch::fromArray([
      'added' => ['foo' => 'bar'],
      'removed' => ['foo' => 'baz'],
    ])->toArray();

    $expectedExport = new MemoryStorage();
    $expectedSplit = new MemoryStorage();

    // Set up the expected data.
    foreach (array_merge($active->getAllCollectionNames(), [StorageInterface::DEFAULT_COLLECTION]) as $collection) {
      $active = $active->createCollection($collection);
      $expectedExport = $expectedExport->createCollection($collection);
      $expectedSplit = $expectedSplit->createCollection($collection);
      foreach ($active->listAll() as $name) {
        $data = $active->read($name);
        if ($name === 'config_test.types') {
          $expectedSplit->write($name, $data);
        }
        elseif ($name === 'config_test.system') {
          // We only changed the config in the default collection.
          if ($collection === StorageInterface::DEFAULT_COLLECTION) {
            $expectedSplit->write('config_split.patch.' . $name, $systemPatch);
            $expectedExport->write($name, $originalSystem);
          }
          else {
            $expectedExport->write($name, $data);
          }
        }
        else {
          $expectedExport->write($name, $data);
        }
      }
    }

    if ($storage === 'collection') {
      $temp = new SplitCollectionStorage($expectedExport, $config->get('id'));
      self::replaceStorageContents($expectedSplit, $temp);
    }

    $export = $this->getExportStorage();
    static::assertStorageEquals($expectedExport, $export);
    static::assertStorageEquals($expectedSplit, $this->getSplitPreviewStorage($config, $export));

    // Change the config.
    $config->set('complete_list', ['config_test.system'])->set('partial_list', [])->save();
    $active = $this->getActiveStorage();

    // Update expectations.
    $expectedExport->write($config->getName(), $config->getRawData());
    $expectedExport->write('config_test.types', $active->read('config_test.types'));
    $expectedSplit->delete('config_test.types');
    $expectedSplit->delete('config_split.patch.config_test.system');
    $expectedExport->delete('config_test.system');
    $expectedSplit->write('config_test.system', $active->read('config_test.system'));
    // Update multilingual expectations.
    foreach (array_merge($active->getAllCollectionNames(), [StorageInterface::DEFAULT_COLLECTION]) as $collection) {
      $active = $active->createCollection($collection);
      $expectedExport = $expectedExport->createCollection($collection);
      $expectedSplit = $expectedSplit->createCollection($collection);

      $expectedExport->delete('config_test.system');
      $expectedSplit->write('config_test.system', $active->read('config_test.system'));
    }
    if ($storage === 'collection') {
      $temp = new SplitCollectionStorage($expectedExport, $config->get('id'));
      self::replaceStorageContents($expectedSplit, $temp);
    }

    $export = $this->getExportStorage();
    static::assertStorageEquals($expectedExport, $export);
    static::assertStorageEquals($expectedSplit, $this->getSplitPreviewStorage($config, $export));

    // Write the export to the file system and assert the import to work.
    $this->copyConfig($expectedExport, $this->getSyncFileStorage());
    $this->copyConfig($expectedSplit, $this->getSplitSourceStorage($config));
    static::assertStorageEquals($active, $this->getImportStorage());
  }

  /**
   * Test complete and conditional split export with modules.
   *
   * @dataProvider storageAlternativesProvider
   */
  public function testConditionalSplitWithModuleConfig($storage) {

    $config = $this->createSplitConfig('test_split', [
      'storage' => $storage,
      'module' => ['config_test' => 0],
      'partial_list' => ['config_test.system'],
    ]);

    $active = $this->getActiveStorage();
    // Export the configuration to sync without filtering.
    $this->copyConfig($active, $this->getSyncFileStorage());

    // Change the config which is partially split to see how it is exported.
    $this->config('config_test.system')->set('foo', 'baz')->save();

    $expectedExport = new MemoryStorage();
    $expectedSplit = new MemoryStorage();

    // Set up the expected data.
    foreach (array_merge([StorageInterface::DEFAULT_COLLECTION], $active->getAllCollectionNames()) as $collection) {
      $active = $active->createCollection($collection);
      $expectedExport = $expectedExport->createCollection($collection);
      $expectedSplit = $expectedSplit->createCollection($collection);
      foreach ($active->listAll() as $name) {
        $data = $active->read($name);
        if ($name === 'core.extension') {
          unset($data['module']['config_test']);
        }

        // The partial config does not take effect because it still depends
        // explicitly on the module which is split off.
        if (strpos($name, 'config_test') !== FALSE || in_array($name, ['system.menu.exclude-test', 'system.menu.indirect-exclude-test'])) {
          // Expect config that depends on config_test directly and indirectly
          // to be split off.
          $expectedSplit->write($name, $data);
        }
        else {
          $expectedExport->write($name, $data);
        }
      }
    }
    if ($storage === 'collection') {
      $temp = new SplitCollectionStorage($expectedExport, $config->get('id'));
      self::replaceStorageContents($expectedSplit, $temp);
    }

    $export = $this->getExportStorage();
    static::assertStorageEquals($expectedExport, $export);
    static::assertStorageEquals($expectedSplit, $this->getSplitPreviewStorage($config, $export));

    // Write the export to the file system and assert the import to work.
    $this->copyConfig($expectedExport, $this->getSyncFileStorage());
    $this->copyConfig($expectedSplit, $this->getSplitSourceStorage($config));
    static::assertStorageEquals($active, $this->getImportStorage());
  }

  /**
   * Test that dependencies are split too.
   *
   * @dataProvider storageAlternativesProvider
   */
  public function testIncludeDependency($storage) {
    $config = $this->createSplitConfig('test_split', [
      'storage' => $storage,
      'complete_list' => ['system.menu.exclude-test'],
    ]);

    // Export the configuration to sync without filtering.
    $this->copyConfig($this->getActiveStorage(), $this->getSyncFileStorage());

    // Change some config.
    $this->config('system.menu.indirect-exclude-test')->set('label', 'Split Test')->save();

    $active = $this->getActiveStorage();
    $expectedExport = new MemoryStorage();
    $expectedSplit = new MemoryStorage();

    // Set up the expected data.
    foreach (array_merge([StorageInterface::DEFAULT_COLLECTION], $active->getAllCollectionNames()) as $collection) {
      $active = $active->createCollection($collection);
      $expectedExport = $expectedExport->createCollection($collection);
      $expectedSplit = $expectedSplit->createCollection($collection);
      foreach ($active->listAll() as $name) {
        $data = $active->read($name);

        if (in_array($name, ['system.menu.exclude-test', 'system.menu.indirect-exclude-test'])) {
          $expectedSplit->write($name, $data);
        }
        else {
          $expectedExport->write($name, $data);
        }
      }
    }
    if ($storage === 'collection') {
      $temp = new SplitCollectionStorage($expectedExport, $config->get('id'));
      self::replaceStorageContents($expectedSplit, $temp);
    }

    $export = $this->getExportStorage();
    static::assertStorageEquals($expectedExport, $export);
    static::assertStorageEquals($expectedSplit, $this->getSplitPreviewStorage($config, $export));

    // Write the export to the file system and assert the import to work.
    $this->copyConfig($expectedExport, $this->getSyncFileStorage());
    $this->copyConfig($expectedSplit, $this->getSplitSourceStorage($config));
    static::assertStorageEquals($active, $this->getImportStorage());
  }

}
