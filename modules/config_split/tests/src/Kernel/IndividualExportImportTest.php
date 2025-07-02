<?php

namespace Drupal\Tests\config_split\Kernel;

use Drupal\Core\Config\MemoryStorage;
use Drupal\Core\Config\StorageInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Tests\config_filter\Kernel\ConfigStorageTestTrait;

/**
 * Test the export and import of individual splits.
 *
 * @group config_split
 */
class IndividualExportImportTest extends KernelTestBase {

  use ConfigStorageTestTrait;
  use SplitTestTrait;

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
    'config_split',
  ];

  /**
   * The split config.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $split;

  /**
   * The fake IO object cli tools provide us normally.
   *
   * @var object
   */
  protected $io;

  /**
   * The t function.
   *
   * @var callable
   */
  protected $t;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Make sure there is a good amount of config to play with.
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installConfig(['system', 'field', 'config_test']);

    // Set up multilingual.
    ConfigurableLanguage::createFromLangcode('fr')->save();
    ConfigurableLanguage::createFromLangcode('de')->save();

    // Sort the extensions, kernel tests don't concern themselves with that.
    // But we care because the config importer would see a difference when we
    // sort the extensions in the split.
    $extension = $this->container->get('config.storage')->read('core.extension');
    $extension['module'] = module_config_sort($extension['module']);
    $this->container->get('config.storage')->write('core.extension', $extension);

    // The split we test with.
    $this->split = $this->createSplitConfig($this->randomMachineName(), [
      'module' => ['config_test' => 0],
    ]);

    // The stand-in for \Symfony\Component\Console\Style\StyleInterface.
    $this->io = new class() {
      /**
       * The log of the method calls.
       *
       * @var array
       */
      public $calls = [];

      /**
       * {@inheritdoc}
       */
      public function __call($name, $arguments) {
        $this->calls[] = [$name => $arguments];
        return TRUE;
      }

    };

    // By default no translations.
    $this->t = static function (string $s, $args = []): string {
      return strtr($s, $args);
    };
  }

  /**
   * Test exporting when there is no files in the system.
   */
  public function testExportEmptyFiles() {
    // At the beginning all folders are empty.
    static::assertEmpty($this->getSyncFileStorage()->listAll());
    static::assertEmpty($this->getSyncFileStorage()->getAllCollectionNames());

    static::assertEmpty($this->getSplitSourceStorage($this->split)->listAll());
    static::assertEmpty($this->getSplitSourceStorage($this->split)->getAllCollectionNames());
    static::assertEmpty($this->getSplitPreviewStorage($this->split)->listAll());
    static::assertEmpty($this->getSplitPreviewStorage($this->split)->getAllCollectionNames());

    // Do the export.
    $this->container->get('config_split.cli')->ioExport($this->split->getName(), $this->io, $this->t);

    // The sync storage is still empty.
    static::assertEmpty($this->getSyncFileStorage()->listAll());
    static::assertEmpty($this->getSyncFileStorage()->getAllCollectionNames());

    // The split is successfully exported.
    static::assertStorageEquals($this->getSplitExpectationStorage(), $this->getSplitSourceStorage($this->split));

    // Check the IO, but we skip checking the confirmation message.
    static::assertCount(2, $this->io->calls);
    static::assertEquals(['confirm'], array_keys($this->io->calls[0]));
    static::assertEquals(['success' => ['Configuration successfully exported.']], $this->io->calls[1]);
  }

  /**
   * Test exporting when there is already data to be overwritten.
   */
  public function testExportNonEmptyFiles() {
    // Start with random data in the storages.
    $random = $this->getRandomDataStorage();
    $this->copyConfig($random, $this->getSyncFileStorage());
    $this->copyConfig($this->getRandomDataStorage(), $this->getSplitSourceStorage($this->split));

    // Do the export.
    $this->container->get('config_split.cli')->ioExport($this->split->getName(), $this->io, $this->t);

    // The sync storage is still random.
    static::assertStorageEquals($random, $this->getSyncFileStorage());

    // The split is successfully exported.
    static::assertStorageEquals($this->getSplitExpectationStorage(), $this->getSplitSourceStorage($this->split));

    // Check the IO, but we skip checking the confirmation message.
    static::assertCount(2, $this->io->calls);
    static::assertEquals(['confirm'], array_keys($this->io->calls[0]));
    static::assertEquals(['success' => ['Configuration successfully exported.']], $this->io->calls[1]);
  }

  /**
   * Test importing of a split.
   */
  public function testImport() {
    // Set up the split storage to contain the data it should.
    $storage = $this->getSplitSourceStorage($this->split);
    $this->copyConfig($this->getSplitExpectationStorage(), $storage);

    static::assertEmpty($this->getSyncFileStorage()->listAll());
    static::assertEmpty($this->getSyncFileStorage()->getAllCollectionNames());

    // Do the import.
    $this->container->get('config_split.cli')->ioImport($this->split->getName(), $this->io, $this->t);

    static::assertCount(2, $this->io->calls);
    static::assertEquals(['confirm'], array_keys($this->io->calls[0]));
    if (isset($this->io->calls[1]['error'])) {
      // This is not a real assertion but it will display nicer.
      static::assertEquals([''], $this->io->calls[1]['error']);
    }
    static::assertEquals(['text' => ['There are no changes to import.']], $this->io->calls[1]);

    // Change some data so that there is something to import.
    $data = $storage->read('config_test.system');
    $data['foo'] = 'test split';
    $storage->write('config_test.system', $data);
    $this->io->calls = [];

    // Do the import.
    $this->container->get('config_split.cli')->ioImport($this->split->getName(), $this->io, $this->t);

    // The data was imported.
    static::assertEquals($data, $this->getActiveStorage()->read('config_test.system'));

    // @phpstan-ignore-next-line
    static::assertCount(2, $this->io->calls);
    // @phpstan-ignore-next-line
    static::assertEquals(['confirm'], array_keys($this->io->calls[0]));
    // @phpstan-ignore-next-line
    static::assertEquals(['success' => ['Configuration successfully imported.']], $this->io->calls[1]);
  }

  /**
   * Get the config which is expected to be split off.
   *
   * @return \Drupal\Core\Config\StorageInterface
   *   The config storage with the data the split should have.
   */
  protected function getSplitExpectationStorage(): StorageInterface {
    $expected = new MemoryStorage();
    foreach (array_merge($this->getActiveStorage()->getAllCollectionNames(), [StorageInterface::DEFAULT_COLLECTION]) as $collection) {
      $active = $this->getActiveStorage()->createCollection($collection);
      $expected = $expected->createCollection($collection);
      foreach ($active->listAll() as $name) {
        if (strpos($name, 'config_test') !== FALSE) {
          // Split all config starting with config_test, the module we split.
          $expected->write($name, $active->read($name));
        }
      }
    }

    return $expected;
  }

  /**
   * Get random data in a config storage.
   *
   * @return \Drupal\Core\Config\StorageInterface
   *   The storage with random data.
   */
  protected function getRandomDataStorage(): StorageInterface {
    $random = new MemoryStorage();
    $collections = array_merge($this->getActiveStorage()->getAllCollectionNames(), [StorageInterface::DEFAULT_COLLECTION, $this->randomMachineName()]);
    foreach ($collections as $collection) {
      $random = $random->createCollection($collection);
      $size = random_int(4, 10);
      for ($i = 0; $i < $size; $i++) {
        $random->write($this->randomMachineName(), (array) $this->randomObject());
      }
    }

    return $random;
  }

}
