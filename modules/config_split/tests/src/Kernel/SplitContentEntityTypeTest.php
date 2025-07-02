<?php

namespace Drupal\Tests\config_split\Kernel;

use Drupal\Core\Config\StorageCopyTrait;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\NodeType;
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
class SplitContentEntityTypeTest extends KernelTestBase {

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
    'datetime',
    'config',
    'config_split',
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
    $this->installConfig(['system', 'field', 'datetime']);

    NodeType::create([
      'type' => 'test',
      'name' => 'Test',
      'description' => 'testing nodes',
    ])->save();

    // Add field storage and instance for datetime and text field.
    $field_storage_definitions = [
      [
        'field_name' => 'field_test_date',
        'entity_type' => 'node',
        'bundle' => 'test',
        'type' => 'datetime',
      ],
      [
        'field_name' => 'field_test_text',
        'entity_type' => 'node',
        'bundle' => 'test',
        'type' => 'text',
      ],
    ];
    foreach ($field_storage_definitions as $field_storage_definition) {
      $field_storage = FieldStorageConfig::create($field_storage_definition);
      $field_storage->save();

      $field = FieldConfig::create($field_storage_definition);
      $field->save();
    }

    // Save view and form display.
    // @todo add test fields to display, otherwise they are just hidden.
    $display_repository = \Drupal::service('entity_display.repository');
    $display_repository
      ->getViewDisplay('node', 'test')
      ->save();
    $display_repository
      ->getFormDisplay('node', 'test')
      ->save();
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
  public function testEntityDisplayDependency($storage) {
    // Create a test split with a module which will be enabled,
    // as well as a piece of config that will be changed and
    // depend on the module.
    $config = $this->createSplitConfig('test_split', [
      'storage' => $storage,
      'module' => ['datetime' => 0],
      'complete_list' => [
        // Completely split also a config which gts updated by the module being
        // removed by the same split.
        'core.entity_view_display.node.test.*',
      ],
    ]);

    $export = $this->getExportStorage();
    $splitPreview = $this->getSplitPreviewStorage($config, $export);

    // Assert that the export storage can be imported without the split.
    $this->validateImport($export);

    // The date field is removed.
    static::assertNotContains('datetime', array_keys($export->read('core.extension')['module']));
    // The view display is completely split.
    static::assertNotContains('core.entity_view_display.node.test.default', $export->listAll());
    static::assertContains('core.entity_view_display.node.test.default', $splitPreview->listAll());
    static::assertNotContains('config_split.patch.core.entity_view_display.node.test.default', $splitPreview->listAll());
    // The form display is just normally patched due to the date field.
    static::assertContains('core.entity_form_display.node.test.default', $export->listAll());
    static::assertNotContains('core.entity_form_display.node.test.default', $splitPreview->listAll());
    static::assertContains('config_split.patch.core.entity_form_display.node.test.default', $splitPreview->listAll());

    // Write the export to the file system and assert the import to work.
    // This is the most important thing we need to work, it won't if the patch
    // gets somehow created wrong.
    $this->copyConfig($export, $this->getSyncFileStorage());
    $this->copyConfig($splitPreview, $this->getSplitSourceStorage($config));
    static::assertStorageEquals($this->getActiveStorage(), $this->getImportStorage());
  }

  /**
   * Test a simple export split.
   *
   * @dataProvider storageAlternativesProvider
   */
  public function testEntityDisplayWithMultipleDependencies($storage) {
    // Create a test split with a module which will be enabled,
    // as well as a piece of config that will be changed and
    // depend on the module.
    $config = $this->createSplitConfig('test_split', [
      'storage' => $storage,
      'module' => ['datetime' => 0],
      'complete_list' => [
        'field.storage.node.field_test_text',
      ],
    ]);

    $export = $this->getExportStorage();
    $splitPreview = $this->getSplitPreviewStorage($config, $export);

    // Assert that the export storage can be imported without the split.
    $this->validateImport($export);

    // The module is split.
    static::assertNotContains('datetime', array_keys($export->read('core.extension')['module']));
    // Make sure both fields are correctly removed from the form display.
    static::assertNotContains('field.field.node.test.field_test_date', $export->read('core.entity_form_display.node.test.default')['dependencies']['config']);
    static::assertNotContains('field.field.node.test.field_test_text', $export->read('core.entity_form_display.node.test.default')['dependencies']['config']);
    // The both fields are completely split.
    foreach ([
      'field.field.node.test.field_test_date',
      'field.field.node.test.field_test_text',
      'field.storage.node.field_test_date',
      'field.storage.node.field_test_text',
    ] as $item) {
      static::assertContains($item, $splitPreview->listAll());
      static::assertNotContains('config_split.patch.' . $item, $splitPreview->listAll());
    }
    // There is a patch for the display.
    static::assertContains('config_split.patch.core.entity_form_display.node.test.default', $splitPreview->listAll());
    static::assertContains('config_split.patch.core.entity_view_display.node.test.default', $splitPreview->listAll());
    static::assertNotContains('core.entity_form_display.node.test.default', $splitPreview->listAll());
    static::assertNotContains('core.entity_view_display.node.test.default', $splitPreview->listAll());

    // Write the export to the file system and assert the import to work.
    // This is the most important thing we need to work, it won't if the patch
    // gets somehow created wrong.
    $this->copyConfig($export, $this->getSyncFileStorage());
    $this->copyConfig($splitPreview, $this->getSplitSourceStorage($config));
    static::assertStorageEquals($this->getActiveStorage(), $this->getImportStorage());
  }

  /**
   * Test a simple export split.
   *
   * @dataProvider storageAlternativesProvider
   */
  public function testPatchCreationAfterCompleteSplit($storage) {
    // Make the display depend on the datetime module so that it is completely
    // split when modules are processed.
    $display = $this->config('core.entity_form_display.node.test.default');
    $dependencies = $display->get('dependencies');
    $dependencies['enforced']['module'][] = 'datetime';
    $display->set('dependencies', $dependencies)->save();

    $config = $this->createSplitConfig('test_split', [
      'storage' => $storage,
      'module' => ['datetime' => 0],
      'complete_list' => [
        'field.storage.node.field_test_text',
      ],
    ]);

    $export = $this->getExportStorage();
    $splitPreview = $this->getSplitPreviewStorage($config, $export);

    // Assert that the export storage can be imported without the split.
    $this->validateImport($export);

    // The form display is completely split.
    static::assertNotContains('core.entity_form_display.node.test.default', $export->listAll());
    static::assertContains('core.entity_form_display.node.test.default', $splitPreview->listAll());
    static::assertNotContains('config_split.patch.core.entity_form_display.node.test.default', $splitPreview->listAll());
    // The view display is patched.
    static::assertContains('core.entity_view_display.node.test.default', $export->listAll());
    static::assertNotContains('core.entity_view_display.node.test.default', $splitPreview->listAll());
    static::assertContains('config_split.patch.core.entity_view_display.node.test.default', $splitPreview->listAll());

    // Write the export to the file system and assert the import to work.
    // This is the most important thing we need to work, it won't if the patch
    // gets somehow created wrong.
    $this->copyConfig($export, $this->getSyncFileStorage());
    $this->copyConfig($splitPreview, $this->getSplitSourceStorage($config));
    static::assertStorageEquals($this->getActiveStorage(), $this->getImportStorage());
  }

  /**
   * Test a simple export split.
   *
   * @dataProvider storageAlternativesProvider
   */
  public function testPatchCreationInPartialSplitAfterCompleteSplit($storage) {
    // Partially and completely split the same item.
    $config = $this->createSplitConfig('test_split', [
      'storage' => $storage,
      'complete_list' => [
        'system.menu.main',
      ],
      'partial_list' => [
        'system.menu.main',
      ],
    ]);

    // Write the thing we partially split to the sync storage.
    $menu = $this->getActiveStorage()->read('system.menu.main');
    $menu['label'] = 'not in sync';
    $this->getSyncFileStorage()->write('system.menu.main', $menu);

    $export = $this->getExportStorage();
    $splitPreview = $this->getSplitPreviewStorage($config, $export);

    // Assert that the export storage can be imported without the split.
    $this->validateImport($export);

    static::assertNotContains('system.menu.main', $export->listAll());
    static::assertContains('system.menu.main', $splitPreview->listAll());
    static::assertNotContains('config_split.patch.system.menu.main', $splitPreview->listAll());

    // Write the export to the file system and assert the import to work.
    // This is the most important thing we need to work, it won't if the patch
    // gets somehow created wrong.
    $this->copyConfig($export, $this->getSyncFileStorage());
    $this->copyConfig($splitPreview, $this->getSplitSourceStorage($config));
    static::assertStorageEquals($this->getActiveStorage(), $this->getImportStorage());
  }

  /**
   * Test stackable split splits.
   *
   * @dataProvider storageAlternativesProvider
   */
  public function testSingleStackableSplit($storage) {
    $config = $this->createSplitConfig('stackable', [
      'storage' => $storage,
      'stackable' => TRUE,
      'module' => ['datetime' => 0],
      'complete_list' => [
        'node.type.test',
      ],
    ]);

    $export = $this->getExportStorage();
    $splitPreview = $this->getSplitPreviewStorage($config, $export);

    $this->validateImport($export);
    $this->copyConfig($export, $this->getSyncFileStorage());
    $this->copyConfig($splitPreview, $this->getSplitSourceStorage($config));
    static::assertStorageEquals($this->getActiveStorage(), $this->getImportStorage());
  }

  /**
   * Test overlapping splits.
   *
   * @dataProvider storageAlternativesProvider
   */
  public function testMultipleStackableSplits($storage) {

    $feature_split = $this->createSplitConfig('feature', [
      'storage' => $storage,
      'weight' => 20,
      'stackable' => TRUE,
      'complete_list' => [
        'node.type.test',
        'field.storage.node.field_test_text',
      ],
    ]);

    // Trigger the config export event to save the feature split to the storage.
    $export = $this->getExportStorage();
    // Export the feature split.
    $feature_preview_storage = $this->getSplitPreviewStorage($feature_split, $export);
    $this->copyConfig($feature_preview_storage, $this->getSplitSourceStorage($feature_split));

    // Create a new split to stack on top.
    $site_split = $this->createSplitConfig('site', [
      'storage' => $storage,
      'weight' => 10,
      'stackable' => TRUE,
      'module' => ['datetime' => 0],
      'complete_list' => [
        'core.entity_view_display.node.test.default',
      ],
      'partial_list' => [
        'node.type.test',
      ],
    ]);

    // Change the config which we partially split.
    $this->config('node.type.test')->set('description', 'The sites test node type')->save();

    // Export with both splits.
    $export = $this->getExportStorage();
    $feature_preview_storage = $this->getSplitPreviewStorage($feature_split, $export);
    $site_preview_storage = $this->getSplitPreviewStorage($site_split, $export);

    // Assert that the export storage can be imported without the splits.
    $this->validateImport($export);

    // Validate that the site split could be deactivated.
    $feature_only = $this->mergeSplit($feature_split, $export, $feature_preview_storage);
    $this->validateImport($feature_only);

    // Assert that datetime is not included in core.extension modules.
    static::assertNotContains('datetime', array_keys($export->read('core.extension')['module']));
    // Assert that date field config is not contained in the feature split.
    static::assertNotContains('field.field.node.test.field_test_date', $feature_only->listAll());
    static::assertNotContains('field.field.node.test.field_test_date', $feature_preview_storage->listAll());

    static::assertNotContains('node.type.test', $export->listAll());
    static::assertEquals('testing nodes', $feature_only->read('node.type.test')['description']);
    static::assertEquals('testing nodes', $feature_preview_storage->read('node.type.test')['description']);
    static::assertNotContains('config_split.patch.core.entity_view_display.node.test.default', $site_preview_storage->listAll());
    static::assertContains('config_split.patch.node.type.test', $site_preview_storage->listAll());
    static::assertNotContains('node.type.test', $site_preview_storage->listAll());

    // Write the export to the file system and assert the import to work.
    // This is the most important thing we need to work, it won't if the patch
    // gets somehow created wrong.
    $this->copyConfig($export, $this->getSyncFileStorage());
    $this->copyConfig($feature_preview_storage, $this->getSplitSourceStorage($feature_split));
    $this->copyConfig($site_preview_storage, $this->getSplitSourceStorage($site_split));
    static::assertStorageEquals($this->getActiveStorage(), $this->getImportStorage());
  }

  /**
   * Test overlapping splits.
   *
   * @dataProvider storageAlternativesProvider
   */
  public function testMultipleIndependentSplits($storage) {
    $split_a = $this->createSplitConfig('split_a', [
      'storage' => $storage,
      'weight' => 10,
      'module' => ['datetime' => 0],
    ]);

    // @todo make the splits different and still overlap.
    $split_b = $this->createSplitConfig('split_b', [
      'storage' => $storage,
      'weight' => 20,
      'module' => ['datetime' => 0],
    ]);

    $export = $this->getExportStorage();
    $preview_storage_a = $this->getSplitPreviewStorage($split_a, $export);
    $preview_storage_b = $this->getSplitPreviewStorage($split_b, $export);

    // Assert that the export storage can be imported without the split.
    $this->validateImport($export);

    // Validate that the splits can be imported independently.
    $only_a = $this->mergeSplit($split_a, $export, $preview_storage_a);
    $this->validateImport($only_a);
    $only_b = $this->mergeSplit($split_b, $export, $preview_storage_b);
    $this->validateImport($only_b);

    // Assert that datetime is not included in core.extension modules.
    static::assertNotContains('datetime', array_keys($export->read('core.extension')['module']));
    // Assert that date field config is not contained in the feature split.
    // The feature split does contain the date field, but it will not be merged.
    static::assertContains('field.field.node.test.field_test_date', $only_a->listAll());
    static::assertContains('field.field.node.test.field_test_date', $only_b->listAll());
    static::assertContains('field.field.node.test.field_test_date', $preview_storage_a->listAll());
    static::assertContains('field.field.node.test.field_test_date', $preview_storage_b->listAll());

    // Write the export to the file system and assert the import to work.
    // This is the most important thing we need to work, it won't if the patch
    // gets somehow created wrong.
    $this->copyConfig($export, $this->getSyncFileStorage());
    $this->copyConfig($preview_storage_a, $this->getSplitSourceStorage($split_a));
    $this->copyConfig($preview_storage_b, $this->getSplitSourceStorage($split_b));
    static::assertStorageEquals($this->getActiveStorage(), $this->getImportStorage());
  }

  /**
   * Test non patching splits and dependents.
   *
   * @dataProvider storageAlternativesProvider
   */
  public function testNoPatchingSplit($storage) {
    // Create a split, completely splitting the type.
    $config = $this->createSplitConfig('test_split', [
      'storage' => $storage,
      'no_patching' => TRUE,
      'complete_list' => [
        'node.type.test',
      ],
    ]);

    $export = $this->getExportStorage();
    $splitPreview = $this->getSplitPreviewStorage($config, $export);

    // Assert that the export storage can be imported without the split.
    $this->validateImport($export);

    // Confirm node type.
    static::assertNotContains('node.type.test', $export->listAll());
    static::assertContains('node.type.test', $splitPreview->listAll());

    // Confirm dependents.
    static::assertNotContains('field.field.node.test.field_test_text', $export->listAll());
    static::assertContains('field.field.node.test.field_test_text', $splitPreview->listAll());
    static::assertNotContains('core.entity_view_display.node.test.default', $export->listAll());
    static::assertContains('core.entity_view_display.node.test.default', $splitPreview->listAll());

    // Confirm non-dependent (field storage).
    static::assertContains('field.storage.node.field_test_text', $export->listAll());
    static::assertNotContains('field.storage.node.field_test_text', $splitPreview->listAll());

    // Write the export to the file system and assert the import to work.
    $this->copyConfig($export, $this->getSyncFileStorage());
    $this->copyConfig($splitPreview, $this->getSplitSourceStorage($config));
    static::assertStorageEquals($this->getActiveStorage(), $this->getImportStorage());
  }

}
