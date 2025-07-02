<?php

namespace Drupal\Tests\migmag\Functional;

use Drupal\Core\Database\Database;
use Drupal\Tests\migmag\Traits\CoreCompatibilityTrait;

/**
 * Tests whether the committed content export fixtures are up to date.
 *
 * @group migmag
 */
class MigMagFixtureIsUpToDateTest extends MigMagCoreMigrationTestBase {

  use CoreCompatibilityTrait;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    if (!strpos(\Drupal::VERSION, '-dev', -0)) {
      $this->markTestSkipped(
        sprintf("Fixture test only makes sense if it is executed with development core versions (e.g. 11.0.0-dev, 9.3.6-dev). Found %s",
          \Drupal::VERSION
        )
      );
    }

    parent::setUp();
  }

  /**
   * List of entity types to compare.
   *
   * @var string[]
   */
  protected $comparedContentEntityTypes = [
    'aggregator_feed',
    'aggregator_item',
    'block_content',
    'comment',
    'file',
    'menu_link_content',
    'node',
    'path_alias',
    'shortcut',
    'taxonomy_term',
    'user',
  ];

  /**
   * {@inheritdoc}
   */
  protected function getEntityTypesToCompare() {
    if (static::coreAggregatorIsMissing()) {
      return array_values(
          array_diff(
            $this->comparedContentEntityTypes,
            ['aggregator_feed', 'aggregator_item']
          )
        );
    }
    return $this->comparedContentEntityTypes;
  }

  /**
   * {@inheritdoc}
   */
  protected function getStaticExportModuleName() {
    $base = implode('_', [
      'core',
      $this->getCleanedDrupalCoreVersion(),
    ]);
    $new_value = preg_replace('/[^a-z0-9_]+/', '_', strtolower($base));
    // Use the 10.2.x content export fixture for testing 10.3.x as long as
    // the end result of the Drupal migration is the same with these core
    // versions.
    $unchanged_versions = [
      'core_10_3',
      'core_10_4',
      'core_11_0',
      'core_11_1',
      'core_11_2',
      'core_11_3',
    ];
    if (in_array($new_value, $unchanged_versions, TRUE)) {
      return 'core_10_2';
    }
    return $new_value;
  }

  /**
   * Executes the test of Drupal 7 migration and saves content output.
   */
  public function createMigrateUpgradeDataset() {
    $this->executeDrupal7Migration();
    $this->createActualExport();
    $this->isExportOnly = TRUE;
    $this->skipTeardown = TRUE;
  }

  /**
   * Tests whether the committed content exports fixtures are up to date.
   */
  public function testContentExportFixtureIsUpToDate() {
    if (Database::getConnection()->driver() === 'sqlite') {
      $this->markTestSkipped(
        "SQLITE stores floats like '1.0' as '1'. Since this test uses a static dataset committed into the codebase (making us able to track what happens in core), we skip this test instead of fighting with it."
      );
    }
    if (Database::getConnection()->driver() === 'pgsql') {
      $this->markTestSkipped(
        sprintf(
          "Migrate Drupal's abstract source plugin class FieldableEntity returns field values in the wrong order from PostgreSQL sources. We skip this test instead of fighting with it. See '%s'",
          'https:/drupal.org/i/3164520'
        )
      );
    }
    $this->ensureBaseExportIsPresent();
    $this->executeDrupal7Migration();
    $this->createActualExport();
    $this->compareEntityContentExportSets(sprintf(
      "The content export fixture of Drupal core '%s' isn't up to date.",
      \Drupal::VERSION
    ));
    $this->removeTempBaseExportModule();
  }

}
