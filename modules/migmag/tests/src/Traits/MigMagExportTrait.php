<?php

namespace Drupal\Tests\migmag\Traits;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\Variable;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Extension\ModuleInstallerInterface;
use Drupal\eme\ExportException;
use Drush\TestTraits\DrushTestTrait;
use PHPUnit\Framework\ExpectationFailedException;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Trait for comparing the resulting content after migration.
 */
trait MigMagExportTrait {

  use DrushTestTrait;

  /**
   * Whether the current test case only creates the base of the comparison.
   *
   * @var bool
   */
  protected $isExportOnly = FALSE;

  /**
   * Whether teardown should be skipped.
   *
   * @var bool
   */
  protected $skipTeardown = FALSE;

  /**
   * Returns the list of entity type IDs to compare.
   *
   * @return string[]
   *   The entity type IDs of the content entity types to be compared.
   */
  protected function getEntityTypesToCompare() {
    if (!empty($this->comparedContentEntityTypes)) {
      return $this->comparedContentEntityTypes;
    }

    return array_reduce(\Drupal::entityTypeManager()->getDefinitions(), function (array $carry, EntityTypeInterface $entity_type) {
      if ($entity_type instanceof ContentEntityTypeInterface) {
        $type_id = $entity_type->id();
        $storage = \Drupal::entityTypeManager()->getStorage($type_id);
        if ($storage->getQuery()->count()->accessCheck(FALSE)->execute() > 0) {
          $carry[] = $entity_type->id();
        }
      }
      return $carry;
    }, []);
  }

  /**
   * Compares the end result of the given actions.
   *
   * This method executes the given callback, and then exports the available
   * content entities' default revision to an EME-generated module.
   * If "$only_create_comparison_base" id TRUE, then the export will be saved to
   * a location which is retained between test case executions.
   * If "$only_create_comparison_base" id FALSE, then this assumes that a
   * previous export was already stored, so creates a fresh export, and compares
   * the result of the two JSON data set.
   */
  protected function compareResultOf(string $method, $only_create_comparison_base) {
    if (!$only_create_comparison_base || !$this->getStaticTestExportModulePath()) {
      $this->$method();
    }

    if ($only_create_comparison_base) {
      // Try to find a previous export – if it isn't present, it means that we
      // have to generate one.
      $this->ensureBaseExportIsPresent();
      $this->assertTrue(TRUE);
      return;
    }

    $this->createActualExport();

    $this->compareEntityContentExportSets();
  }

  /**
   * Compares the base and the actual EME export data set.
   *
   * @param string $error_message
   *   The message of the exception thrown when the comparison fails.
   */
  protected function compareEntityContentExportSets(string $error_message = '') {
    if ($error_message) {
      try {
        $this->doCompareEntityContentExportSets();
      }
      catch (ExpectationFailedException $exception) {
        throw new ExpectationFailedException(
          implode(' ', [
            $error_message,
            $exception->getMessage(),
          ]),
          $exception->getComparisonFailure(),
          $exception
        );
      }
      return;
    }

    $this->doCompareEntityContentExportSets();
  }

  /**
   * Compares the base and the actual EME export data set.
   */
  protected function doCompareEntityContentExportSets() {
    $base_list = $this->getBaseExportAssets();
    $actual_list = $this->getActualExportAssets();
    sort($actual_list);
    sort($base_list);

    $this->assertNotEmpty(
      $base_list,
      'Base set is empty' . $this->getTempBaseExportModuleLocation()
    );
    $this->assertNotEmpty(
      $actual_list,
      'Current set is empty' . $this->getActualExportModuleLocation()
    );
    $this->assertEquals(array_values($base_list), array_values($actual_list));

    foreach ($base_list as $entity_type) {
      $this->compareEntityInstances($entity_type);
    }
  }

  /**
   * Ensures that a base export is present.
   *
   * This method will copy the already-available data fixture to its expected
   * location (this is sites/simpletest/module_location_name), or if the fixture
   * is missing, creates a data export from the currently available content
   * entity type instances.
   */
  protected function ensureBaseExportIsPresent() {
    $this->isExportOnly = TRUE;
    $base_export_path = $this->getTempBaseExportModuleLocation();
    if ($static_export_path = $this->getStaticTestExportModulePath()) {
      $source = implode(DIRECTORY_SEPARATOR, [
        $static_export_path,
        $this->getStaticExportModuleName(),
      ]);
      $destination = implode(DIRECTORY_SEPARATOR, [
        $base_export_path,
        $this->getExportModuleName(),
      ]);
      $fileSystem = new Filesystem();
      $fileSystem->remove($destination);
      $fileSystem->mirror($source, $destination, NULL, [
        'override' => TRUE,
      ]);
      $this->assertTrue(TRUE, sprintf(
        "MigMagExportTrait has found a predefined dataset at '%s'",
        $source
      ));
      return;
    }

    $this->assertTrue(TRUE, sprintf(
      "MigMagExportTrait was not able to find a predefined dataset with name '%s'",
      $this->getStaticExportModuleName()
    ));

    $this->doExport($base_export_path);
  }

  /**
   * Creates a data export from the currently available content.
   */
  protected function createActualExport() {
    $this->doExport($this->getActualExportModuleLocation());
  }

  /**
   * Performs content entity instance export to JSON files with EME.
   *
   * @param string $destination
   *   The destination of the export. Relative to the current Drupal instance's
   *   root directory.
   *
   * @requires module eme
   *
   * @throws \Drupal\Core\Extension\MissingDependencyException
   *   Thrown if the EME module is not available.
   * @throws \PHPUnit\Framework\ExpectationFailedException
   *   Thrown when the export throws an exception.
   */
  protected function doExport($destination) {
    if (!\Drupal::moduleHandler()->moduleExists('eme')) {
      $module_installer = \Drupal::service('module_installer');
      assert($module_installer instanceof ModuleInstallerInterface);
      $module_installer->install(['eme']);
      try {
        $this->resetAll();
      }
      catch (\Throwable $t) {
        // After executing core fixture's migration with Drupal core 8.9.x,
        // ListItemBase throws undefined index because of wrongly migrated data
        // caused by the broken migrate process plugin 'd7_field_option'.
        // The bug won't be fixed in Drupal core 8.9.x.
        // @see https://drupal.org/i/3187463
        $this->assertEquals('8.9', $this->getCleanedDrupalCoreVersion());
      }
    }

    $content_entity_types = $this->getEntityTypesToCompare();

    $export_name = $this->getExportModuleName();
    try {
      $this->drush('eme:export', [], [
        'types' => implode(',', $content_entity_types),
        'destination' => $destination,
        'module' => $export_name,
        'name' => $export_name,
        'id-prefix' => 'migmag',
      ]);
    }
    catch (ExportException $e) {
      throw new ExpectationFailedException(
        sprintf(
          "Content entity instances with the specified types cannot be exported. Types are: '%s'",
          implode("', '", $content_entity_types)
        )
      );
    }

    $this->assertTrue(
      file_exists($destination . DIRECTORY_SEPARATOR . $export_name),
      sprintf(
        "Export wasn't created:\n%s'",
        Variable::export($this->getErrorOutputAsList())
      )
    );
  }

  /**
   * Returns a test class specific temporary export ID.
   *
   * This ID id used as the root dir of the exported data, and this is used also
   * as the export module's name.
   *
   * @return string
   *   The temporary export's ID.
   */
  protected function getTempExportId() {
    $test = array_reverse(explode('\\', get_class($this)), FALSE)[0];
    $base = implode('_', [
      $test,
      $this->getCleanedDrupalCoreVersion(),
    ]);
    $new_value = preg_replace('/[^a-z0-9_]+/', '_', strtolower($base));
    return preg_replace('/_+/', '_', $new_value);
  }

  /**
   * Returns a cleaned Drupal version number.
   *
   * This trait tends to use only major and minor, but leaves this to be
   * overridable.
   *
   * @return string
   *   A cleaned Drupal version.
   */
  protected function getCleanedDrupalCoreVersion() {
    $drupal_version_exploded = explode('.', \Drupal::VERSION);
    return implode('.', [
      $drupal_version_exploded[0],
      preg_replace('/\D/', '', $drupal_version_exploded[1]) ?: 0,
    ]);
  }

  /**
   * Returns the test class specific name of the generated temporary EME module.
   *
   * @return string
   *   The temporary export's ID.
   */
  protected function getExportModuleName() {
    return $this->getTempExportId();
  }

  /**
   * Returns the name of the static EME module.
   *
   * @return string
   *   The static export's ID.
   */
  protected function getStaticExportModuleName() {
    return $this->getExportModuleName();
  }

  /**
   * The Drupal relative location of the temp export (base of the comparison).
   *
   * @return string
   *   The Drupal root relative location of the temporary export.
   */
  protected function getTempBaseExportModuleLocation() {
    return implode(DIRECTORY_SEPARATOR, [
      $this->siteDirectory,
      '..',
      $this->getTempExportId(),
    ]);
  }

  /**
   * The Drupal relative location of the actual export (compared to base).
   *
   * @return string
   *   The Drupal root relative location of the actual export.
   */
  protected function getActualExportModuleLocation() {
    return $this->publicFilesDirectory;
  }

  /**
   * Returns info about, or actual entity instance data from the dir specified.
   *
   * @param string $base_dir
   *   The location where an EME export can be found.
   * @param string|null $entity_type
   *   The entity type whose bundles (or whose entity instance data) we want to
   *   get.
   * @param string|null $bundle
   *   The bundle whose entity instance data we want to get.
   *
   * @return string[]|array[]
   *   Depends on the given arguments.
   *   - Returns the list of the discovered entity type IDs if "$entity_type" is
   *     NULL.
   *   - Returns the list of the discovered bundles of the specified
   *     "$entity_type" IF there is exported data with the entity type, and the
   *     entity type has bundles.
   *   - Returns entity instance data keyed with the file name which holds the
   *     data IF:
   *     - The given entity type has data, the entity is bundleless and
   *       "$bundle" is NULL.
   *     - There is data with the given entity type ID and bundle.
   *   - Returns an empty array if no entity values can be found with the given
   *     entity type ID (and bundle).
   */
  protected function getExportAssets(string $base_dir, $entity_type = NULL, $bundle = NULL) {
    $temp_export_data_root = implode(DIRECTORY_SEPARATOR, array_filter([
      $base_dir,
      $this->getExportModuleName(),
      'data',
      $entity_type,
      $bundle,
    ]));
    if (!file_exists($temp_export_data_root) || !is_dir($temp_export_data_root)) {
      return [];
    }

    $asset_list = array_filter(scandir($temp_export_data_root), function ($value) {
      return !in_array($value, ['.', '..']);
    });

    $file_list = array_filter($asset_list, function ($file_name) use ($temp_export_data_root) {
      return is_file($temp_export_data_root . DIRECTORY_SEPARATOR . $file_name);
    });

    if (empty($file_list)) {
      return $asset_list;
    }

    $data = [];
    foreach ($file_list as $file_name) {
      $file_path = implode(DIRECTORY_SEPARATOR, [
        $temp_export_data_root,
        $file_name,
      ]);
      $file_content = Json::decode(file_get_contents($file_path));
      $data[$file_name] = $file_content;
    }

    return $data;
  }

  /**
   * Returns data about the base export.
   *
   * @see getExportAssets
   *
   * @return string[]|array[]
   *   Data about the base export
   */
  protected function getBaseExportAssets($entity_type = NULL, $bundle = NULL) {
    return $this->getExportAssets($this->getTempBaseExportModuleLocation(), $entity_type, $bundle);
  }

  /**
   * Returns data about the active export.
   *
   * @see getExportAssets
   *
   * @return string[]|array[]
   *   Data about the active export
   */
  protected function getActualExportAssets($entity_type = NULL, $bundle = NULL) {
    return $this->getExportAssets($this->getActualExportModuleLocation(), $entity_type, $bundle);
  }

  /**
   * Returns the location of a static export (if one exists in the codebase).
   *
   * If an export can be found in the codebase, this method returns its path.
   *
   * @return string|null
   *   The location of the preexisting (static) export, or NULL if no such an
   *   export exists.
   */
  protected function getStaticTestExportModulePath() {
    $current_tested_module_path = $this->getTestedModulesPath();
    $preexisting_exports_root = $current_tested_module_path
      ? implode(DIRECTORY_SEPARATOR, [
        $current_tested_module_path,
        'tests',
        'fixtures',
        'exports',
      ])
      : NULL;

    if (!$preexisting_exports_root || !file_exists($preexisting_exports_root) || !is_dir($preexisting_exports_root)) {
      return NULL;
    }

    $preexisting_export_location = implode(DIRECTORY_SEPARATOR, [
      $preexisting_exports_root,
      $this->getStaticExportModuleName(),
    ]);

    if (!file_exists($preexisting_export_location) || !is_dir($preexisting_export_location)) {
      return NULL;
    }

    return $preexisting_exports_root;
  }

  /**
   * Returns the path of the module which is being tested right now.
   */
  protected function getTestedModulesPath() {
    $namespace_parts = explode('\\', get_class($this), 4);
    $this->assertEquals($namespace_parts[0], 'Drupal');
    $this->assertEquals($namespace_parts[1], 'Tests');
    $extension_name = $namespace_parts[2];
    $current_test_patch = (new \ReflectionClass($this))->getFilename();
    $dir_parts = explode(DIRECTORY_SEPARATOR, $current_test_patch);

    for ($i = count($dir_parts); $i > 1; $i--) {
      $current_dir_parts = array_slice($dir_parts, 0, $i);
      $current_dir = implode(DIRECTORY_SEPARATOR, $current_dir_parts);
      $provisioned_extension_info = implode(DIRECTORY_SEPARATOR, [
        $current_dir,
        "{$extension_name}.info.yml",
      ]);

      if (file_exists($provisioned_extension_info)) {
        $tested_modules_path = $current_dir;
        break;
      }
    }

    return $tested_modules_path ?? NULL;
  }

  /**
   * Deletes the temporary data base module.
   */
  protected function removeTempBaseExportModule() {
    (new Filesystem())->remove([$this->getTempBaseExportModuleLocation()]);
  }

  /**
   * Compares the entity instances of the given entity type.
   *
   * @param string $entity_type
   *   The entity type ID of the content entities whose base and active state
   *   should be compared.
   */
  protected function compareEntityInstances(string $entity_type) {
    $actual_bundles = $this->getActualExportAssets($entity_type);
    $base_bundles = $this->getBaseExportAssets($entity_type);
    // If "$base_bundle_or_entity_revisions" is a multidimensional array,
    // then it is an array of an entity revisions.
    $entity_type_has_bundles = !is_array($base_bundles[key($base_bundles)]);
    if ($entity_type_has_bundles) {
      $this->assertEquals($base_bundles, $actual_bundles);
    }
    else {
      $this->assertEquals(array_keys($base_bundles), array_keys($actual_bundles));
    }

    foreach ($base_bundles as $filename_or_bundle => $base_bundle_or_entity_revisions) {
      if ($entity_type_has_bundles) {
        $this->compareEntityInstancesWithBundle($entity_type, $base_bundle_or_entity_revisions);
      }
      else {
        // Files, users, path aliases.
        $this->compareEntityContent($base_bundle_or_entity_revisions, $actual_bundles[$filename_or_bundle], $filename_or_bundle);
      }
    }
  }

  /**
   * Compares the entity instances of the given entity type and bundle.
   *
   * @param string $entity_type
   *   The entity type ID of the content entities whose base and active state
   *   should be compared.
   * @param string $bundle
   *   The bundle of the content entities whose base and current state should be
   *   compared.
   */
  protected function compareEntityInstancesWithBundle(string $entity_type, string $bundle) {
    $actual_entities = $this->getActualExportAssets($entity_type, $bundle);
    $base_entities = $this->getBaseExportAssets($entity_type, $bundle);

    $this->assertNotEmpty($actual_entities);
    $this->assertNotEmpty($base_entities);
    $this->assertEquals(array_keys($base_entities), array_keys($actual_entities));

    foreach ($base_entities as $filename => $base_entity_revisions) {
      $this->compareEntityContent($base_entity_revisions, $actual_entities[$filename], $filename);
    }
  }

  /**
   * Compares the given entity data to each other.
   *
   * @param array[][] $expected
   *   List of the base entity revision values.
   * @param array[][] $actual
   *   List of the actual entity revision values to compare tho the given base.
   * @param string $filename
   *   The name of the file which contains the entity properties. The entity
   *   type ID and the entity ID are extracted from this argument.
   */
  protected function compareEntityContent(array $expected, array $actual, string $filename) {
    $entity_type_and_id = explode('.json', $filename)[0];
    [$entity_type, $entity_id] = explode('-', $entity_type_and_id, 2);
    $datasets = ['actual' => $actual, 'expected' => $expected];
    foreach ($datasets as $dataset_type => $dataset) {
      foreach ($dataset as $key => $entity_revision_values) {
        $$dataset_type[$key] = $this->removeDynamicEntityValues($entity_revision_values, $entity_type);
      }
    }

    $this->assertEquals($expected, $actual, sprintf(
      "The field values of the active revision of %s %s aren't matching.",
      $entity_type,
      $entity_id
    ));
  }

  /**
   * Removes certain dynamic entity instance values depending on the type.
   *
   * @param array $values
   *   Entity values, keyed by property.
   * @param string $entity_type
   *   The entity type ID of the values array.
   *
   * @return array
   *   Entity values without the dynamic properties.
   */
  protected function removeDynamicEntityValues(array $values, $entity_type) {
    // UUIDs aren't migrated from Drupal 7 core.
    $ignore = [
      'uuid',
    ];

    switch ($entity_type) {
      case 'aggregator_item':
        $ignore[] = 'timestamp';
        break;

      case 'block_content':
        $ignore[] = 'revision_id';
        $ignore[] = 'revision_created';
        $ignore[] = 'changed';
        break;

      case 'menu_link_content':
        $ignore[] = 'revision_id';
        $ignore[] = 'revision_created';
        $ignore[] = 'changed';
        $ignore[] = 'content_translation_created';
        // We cannot check menu link parents.
        $ignore[] = 'parent';
        // 'node_translation_menu_links' does not migrate langcode.
        $ignore[] = 'langcode';
        break;

      case 'node':
        // Node's changed property is destroyed by the followup migrations +
        // https://drupal.org/i/2329253.
        $ignore[] = 'changed';
        break;

      case 'taxonomy_term':
        $ignore[] = 'changed';
        $ignore[] = 'revision_created';
        $ignore[] = 'content_translation_created';
        break;

      case 'user':
        // Ignore the comparison of specific properties of user with UID < 3.
        if ($values['uid'] < 3) {
          $ignore[] = 'pass';
          $ignore[] = 'access';
          $ignore[] = 'login';
        }
        $ignore[] = 'changed';
        // In core, content translation creation timestamps are the timestamp
        // when the translation was migrated.
        $ignore[] = 'content_translation_created';
        break;
    }

    return array_diff_key($values, array_combine($ignore, $ignore));
  }

  /**
   * Data provider of tests comparing before-after content entity exports.
   *
   * @return bool[][]
   *   Test cases.
   */
  public function comparisonTestDataProvider(): array {
    return [
      'Generate test data' => [
        'Export only' => TRUE,
      ],
      'Compare test data with base data' => [
        'Export only' => FALSE,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    if (!$this->isExportOnly) {
      $this->removeTempBaseExportModule();
    }

    if ($this->skipTeardown) {
      return;
    }
    parent::tearDown();
  }

}
