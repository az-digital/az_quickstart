<?php

namespace Drupal\Tests\config_ignore\Kernel;

use Drupal\Component\Utility\NestedArray;
use Drupal\config_ignore\ConfigIgnoreConfig;
use Drupal\Core\Config\MemoryStorage;
use Drupal\Core\Config\StorageInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Tests\config_filter\Kernel\ConfigStorageTestTrait;

/**
 * Test the transformations.
 *
 * This test is a bit more condensed and doesn't actually import the config.
 *
 * @group config_ignore
 */
class IgnoreKernelTest extends KernelTestBase {

  use ConfigStorageTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'system',
    'language',
    'config',
    'config_test',
    'config_ignore',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // We install the system and config_test config so that there is something
    // to modify and ignore for the test.
    $this->installConfig(['system', 'config_test', 'config_ignore']);

    // Set up multilingual. The config_test module comes with translations.
    ConfigurableLanguage::createFromLangcode('de')->save();
    ConfigurableLanguage::createFromLangcode('fr')->save();
  }

  /**
   * Test the import transformations.
   *
   * @param string $mode
   *   The import modes.
   * @param array $patterns
   *   An array of ignore patterns, we may refactor this to be the whole config.
   * @param array $active
   *   Modifications to the active config.
   * @param array $sync
   *   Modifications to the sync storage.
   * @param array $expected
   *   Modifications to the expected storage.
   *
   * @dataProvider simpleAndLenientProvider
   */
  public function testImport(string $mode, array $patterns, array $active, array $sync, array $expected) {
    $this->saveConfigWithLenient($mode, $patterns);
    $expectedStorage = $this->setUpStorages($active, $sync, $expected);

    static::assertStorageEquals($expectedStorage, $this->getImportStorage());
  }

  /**
   * Test the export transformations.
   *
   * @param string $mode
   *   The export mode.
   * @param array $patterns
   *   An array of ignore patterns, we may refactor this to be the whole config.
   * @param array $active
   *   Modifications to the active config.
   * @param array $sync
   *   Modifications to the sync storage.
   * @param array $expected
   *   Modifications to the expected storage.
   *
   * @dataProvider simpleAndLenientProvider
   */
  public function testExport(string $mode, array $patterns, array $active, array $sync, array $expected) {
    $this->saveConfigWithLenient($mode, $patterns);
    // Reverse the active and sync to set up the expectations for export.
    $expectedStorage = $this->setUpStorages($sync, $active, $expected);

    static::assertStorageEquals($expectedStorage, $this->getExportStorage());
  }

  /**
   * Save the config in an advanced mode for old tests.
   *
   * When the test was written and the bug was fixed, we had to make a decision
   * on what the behaviour should be that config ignore exhibits. We had a
   * choice, and we implemented the more strict definition of "ignore".
   * A commented out scenario for the more "lenient" option remained in the list
   * So that we could assert it when implementing a feature that allows one to
   * configure the behaviour. It is configured differently than originally
   * anticipated. So this method sets up the config in the new way to cater to
   * the test scenario.
   *
   * @param string $mode
   *   The modes the test used.
   * @param array $patterns
   *   The patterns form the simple mode.
   */
  protected function saveConfigWithLenient(string $mode, array $patterns) {
    // We implemented modes differently now.
    // But we can make the previously commented out tests pass by adapting what
    // lenient means in the new system.
    if ($mode === 'lenient') {
      // The lenient pattern given looks like the simple one.
      $config = new ConfigIgnoreConfig('simple', $patterns);
      $delete = array_map(fn($p) => strpos($p, ':') === FALSE ? $p : substr($p, 0, strpos($p, ':')), $patterns);
      $config->setList('import', 'create', []);
      $config->setList('export', 'create', []);
      $config->setList('import', 'delete', $delete);
      $config->setList('export', 'delete', $delete);
      $this->config('config_ignore.settings')->set('mode', 'advanced')->set('ignored_config_entities', $config->getFormated('advanced'))->save();
    }
    else {
      $this->config('config_ignore.settings')->set('mode', $mode)->set('ignored_config_entities', $patterns)->save();
    }
  }

  /**
   * Provides the test cases for the import.
   *
   * @return \Generator
   *   The test case.
   */
  public function simpleAndLenientProvider() {
    yield 'empty test' => [
        // Mode, can be either one of "simple", "intermediate" or "advanced"
        // For testing legacy tests we also allow "lenient".
      'simple',
      // The ignore config.
      [],
      // Modifications to the active config keyed by language.
      [],
      // Modifications to the sync config keyed by language.
      [],
      // Modifications to the expected config keyed by language.
      [],
    ];
    yield 'keep config deleted in sync' => [
      'simple',
      ['config_test.system'],
      [],
      [
        // Delete the config_test.system from all languages in sync storage.
        '' => ['config_test.system' => FALSE],
        'de' => ['config_test.system' => FALSE],
        'fr' => ['config_test.system' => FALSE],
      ],
      [],
    ];
    yield 'remove translation when not ignored' => [
      'simple',
      ['config_test.system'],
      ['de' => ['config_test.no_status.default' => ['label' => 'DE default']]],
      [],
      [],
    ];
    yield 'do not remove translation when ignored' => [
      'simple',
      ['config_test.system'],
      ['de' => ['config_test.system' => ['foo' => 'Neues Foo']]],
      [],
      ['de' => ['config_test.system' => ['foo' => 'Neues Foo']]],
    ];
    yield 'do not remove config and translation when ignored' => [
      'simple',
      ['config_test.system'],
      [
        '' => ['config_test.system' => ['foo' => 'New Foo']],
        'de' => ['config_test.system' => ['foo' => 'Neues Foo']],
      ],
      [],
      [
        '' => ['config_test.system' => ['foo' => 'New Foo']],
        'de' => ['config_test.system' => ['foo' => 'Neues Foo']],
      ],
    ];
    yield 'do not remove only DE translation when ignored' => [
      'simple',
      ['language.de|config_test.system'],
      [
        '' => ['config_test.system' => ['foo' => 'New Foo']],
        'de' => ['config_test.system' => ['foo' => 'Neues Foo']],
        'fr' => ['config_test.system' => ['foo' => 'Nouveau Foo']],
      ],
      [],
      ['de' => ['config_test.system' => ['foo' => 'Neues Foo']]],
    ];
    yield 'do not remove translation when key is ignored' => [
      'simple',
      ['config_test.system:foo'],
      ['de' => ['config_test.system' => ['foo' => 'Neues Foo']]],
      [],
      ['de' => ['config_test.system' => ['foo' => 'Neues Foo']]],
    ];
    yield 'remove translation when other key is ignored' => [
      'simple',
      ['config_test.system:404'],
      ['de' => ['config_test.system' => ['foo' => 'Neues Foo']]],
      [],
      [],
    ];
    yield 'new translation is ignored' => [
      'simple',
      ['config_test.*'],
      [],
      ['se' => ['config_test.system' => ['foo' => 'Ny foo']]],
      [],
    ];
    yield 'ignore only fr lang collection' => [
      'simple',
      ['language.fr|*'],
      [],
      [
        '' => ['config_test.system' => ['foo' => 'FR Bar', '404' => 'herp']],
        'fr' => ['config_test.system' => ['foo' => 'FR Bar']],
      ],
      [
        '' => ['config_test.system' => ['foo' => 'FR Bar', '404' => 'herp']],
      ],
    ];
    yield 'new config is ignored' => [
      'simple',
      ['config_test.*'],
      [
        '' => [
          'config_test.dynamic.exist' => ['id' => 'exist', 'label' => 'E'],
        ],
      ],
      [
        '' => [
          'config_test.dynamic.exist' => ['id' => 'exist', 'label' => 'N'],
          'config_test.dynamic.new' => ['id' => 'new', 'label' => 'N'],
          'config_test.system' => ['foo' => 'ignored'],
        ],
      ],
      [
        '' => [
          'config_test.dynamic.exist' => ['id' => 'exist', 'label' => 'E'],
        ],
      ],
    ];
    yield 'new collection is ignored' => [
      'simple',
      ['language.*|config_test.*'],
      ['' => ['config_test.dynamic.exist' => ['id' => 'exist', 'label' => 'E']]],
      [
        '' => ['config_test.dynamic.exist' => ['id' => 'exist', 'label' => 'N']],
        'de' => ['config_test.dynamic.exist' => ['label' => 'DE']],
      ],
      ['' => ['config_test.dynamic.exist' => ['id' => 'exist', 'label' => 'N']]],
    ];
    yield 'new collection is ignored except FR' => [
      'simple',
      ['language.*|config_test.*', '~language.fr|config_test.*'],
      ['' => ['config_test.dynamic.exist' => ['id' => 'exist', 'label' => 'E']]],
      [
        '' => ['config_test.dynamic.exist' => ['id' => 'exist', 'label' => 'N']],
        'de' => ['config_test.dynamic.exist' => ['label' => 'DE']],
        'fr' => ['config_test.dynamic.exist' => ['label' => 'FR']],
      ],
      [
        '' => ['config_test.dynamic.exist' => ['id' => 'exist', 'label' => 'N']],
        'fr' => ['config_test.dynamic.exist' => ['label' => 'FR']],
      ],
    ];
    yield 'Ignore property in translations' => [
      'simple',
      ['language.*|config_test.system:foo'],
      [
        '' => ['config_test.system' => ['foo' => 'Replaced Foo', 'baz' => 'replaced baz']],
        'de' => ['config_test.system' => ['foo' => 'Neues Foo', 'baz' => 'ersetztes baz']],
      ],
      [
        '' => ['config_test.system' => ['foo' => 'New Foo', 'baz' => 'new baz']],
        'de' => ['config_test.system' => ['foo' => 'Ignoriertes Foo', 'baz' => 'neues baz']],
      ],
      [
        '' => ['config_test.system' => ['foo' => 'New Foo', 'baz' => 'new baz']],
        'de' => ['config_test.system' => ['foo' => 'Neues Foo', 'baz' => 'neues baz']],
      ],
    ];
    yield 'new config is not ignored in lenient mode' => [
      'lenient',
      ['config_test.*'],
      [
        '' => [
          'config_test.dynamic.exist' => ['id' => 'exist', 'label' => 'E'],
        ],
      ],
      [
        '' => [
          'config_test.dynamic.exist' => ['id' => 'exist', 'label' => 'N'],
          'config_test.dynamic.new' => ['id' => 'new', 'label' => 'N'],
          'config_test.system' => ['foo' => 'ignored'],
        ],
      ],
      [
        '' => [
          'config_test.dynamic.exist' => ['id' => 'exist', 'label' => 'E'],
          'config_test.dynamic.new' => ['id' => 'new', 'label' => 'N'],
        ],
      ],
    ];
    yield 'new config with only key ignored (issue 3137437)' => [
      'simple',
      ['config_test.*:label'],
      ['' => ['config_test.dynamic.exist' => ['id' => 'exist', 'label' => 'E']]],
      [],
      [],
    ];
    yield 'new config with  only key ignored lenient (issue 3137437)' => [
      'lenient',
      ['config_test.*:label'],
      ['' => ['config_test.dynamic.exist' => ['id' => 'exist', 'label' => 'E']]],
      [],
      ['' => ['config_test.dynamic.exist' => ['id' => 'exist', 'label' => 'E']]],
    ];

    yield 'creating new config with ignored keys' => [
      'simple',
      ['config_test.query.*:array.first'],
      [],
      [
        '' => [
          'config_test.query.new' => [
            'id' => 'new',
            'label' => 'N',
            'array' => [
              'hello' => ['world' => 1],
              'first' => ['last' => 1],
            ],
            'number' => 1,
          ],
        ],
      ],
      [
        '' => [
          'config_test.query.new' => [
            'id' => 'new',
            'label' => 'N',
            'array' => [
              'hello' => ['world' => 1],
            ],
            'number' => 1,
          ],
        ],
      ],
    ];

    $active = [
      '' => [
        'config_test.query.existing' => [
          'id' => 'existing',
          'label' => 'E',
          'array' => [
            'hello' => ['who?' => 1],
            'last' => ['stays' => 1],
          ],
          'number' => 0,
        ],
      ],
    ];
    $sync = [
      '' => [
        'config_test.query.existing' => [
          'id' => 'existing',
          'label' => 'N',
          'array' => [
            'hello' => ['world' => 1],
            'first' => ['no' => 1],
          ],
          'number' => 1,
        ],
      ],
    ];
    $expected = [
      '' => [
        'config_test.query.existing' => [
          'id' => 'existing',
          'label' => 'N',
          'array' => [
            'hello' => ['world' => 1],
            'last' => ['stays' => 1],
          ],
          'number' => 1,
        ],
      ],
    ];

    yield 'updating config with new ignored keys' => [
      'simple',
      ['config_test.query.*:array.first', 'config_test.query.*:array.last'],
      $active,
      $sync,
      $expected,
    ];

    // An advanced example.
    $config = new ConfigIgnoreConfig('simple', []);
    foreach (['import', 'export'] as $dir) {
      $config->setList($dir, 'create', ['config_test.query.*:array.*']);
      $config->setList($dir, 'delete', ['config_test.query.*:array.*']);
    }

    yield 'advanced config example' => [
      'advanced',
      $config->getFormated('advanced'),
      $active,
      $sync,
      $expected,
    ];

    foreach (['import', 'export'] as $dir) {
      $config->setList($dir, 'create', ['config_test.query.*:array.*', '~config_test.query.*:array.last']);
      $config->setList($dir, 'delete', ['config_test.query.*:array.*', 'config_test.query.*:~array.first']);
    }

    yield 'more advanced config example' => [
      'advanced',
      $config->getFormated('advanced'),
      $active,
      $sync,
      $expected,
    ];
  }

  /**
   * Set up the active, sync and expected storages.
   *
   * @param array $active
   *   Modifications to the active config.
   * @param array $sync
   *   Modifications to the sync storage.
   * @param array $expected
   *   Modifications to the expected storage.
   *
   * @return \Drupal\Core\Config\StorageInterface
   *   The expected storage.
   */
  protected function setUpStorages(array $active, array $sync, array $expected): StorageInterface {
    // Copy the active config to the sync storage and the expected storage.
    $syncStorage = $this->getSyncFileStorage();
    $expectedStorage = new MemoryStorage();
    $this->copyConfig($this->getActiveStorage(), $syncStorage);
    $this->copyConfig($this->getActiveStorage(), $expectedStorage);

    // Then modify the active storage by saving the config which was given.
    foreach ($active as $lang => $configs) {
      foreach ($configs as $name => $data) {
        if ($lang === '') {
          $config = $this->config($name);
        }
        else {
          // Load the config override.
          $config = \Drupal::languageManager()->getLanguageConfigOverride($lang, $name);
        }

        if ($data !== FALSE) {
          $config->merge($data)->save();
        }
        else {
          // If the data is not an array we want to delete it.
          $config->delete();
        }
      }
    }

    // Apply modifications to the storages.
    static::modifyStorage($syncStorage, $sync);
    static::modifyStorage($expectedStorage, $expected);

    return $expectedStorage;
  }

  /**
   * Helper method to modify a config storage.
   *
   * @param \Drupal\Core\Config\StorageInterface $storage
   *   The storage to modify.
   * @param array $modifications
   *   The modifications keyed by language.
   */
  protected static function modifyStorage(StorageInterface $storage, array $modifications) {
    foreach ($modifications as $lang => $configs) {
      $lang = $lang === '' ? StorageInterface::DEFAULT_COLLECTION : 'language.' . $lang;
      $storage = $storage->createCollection($lang);
      if ($configs === NULL) {
        // If it is set to null explicitly remove everything.
        $storage->deleteAll();
        return;
      }
      foreach ($configs as $name => $data) {
        if ($data !== FALSE) {
          if (is_array($storage->read($name))) {
            // Merge nested arrays if the storage already has data.
            $data = NestedArray::mergeDeepArray([$storage->read($name), $data], TRUE);
          }
          $storage->write($name, $data);
        }
        else {
          // A config name set to false means deleting it.
          $storage->delete($name);
        }
      }
    }
  }

}
