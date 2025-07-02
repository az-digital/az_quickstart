<?php

namespace Drupal\Tests\migmag\Functional;

/**
 * Tests MigMagExportTrait with Drupal 7 upgrade end result using migrate UI.
 *
 * @group migmag
 */
class MigMagExportTraitWithProviderTest extends MigMagCoreMigrationTestBase {

  /**
   * Compares the end result of the same Drupal 7 migration process.
   *
   * This test executes the migration of the Drupal 7 DB fixture twice.
   *
   * After the first migration finishes, the content entities' default revision
   * is exported to an EME-generated module (into JSON files) to a location
   * which is retained between test cases are executed.
   *
   * The second case repeats the same migration process, does an another export,
   * and compares the end result of the two JSON data set.
   *
   * @dataProvider comparisonTestDataProvider
   */
  public function testDrupal7Migration($is_export_only = FALSE) {
    $this->compareResultOf(
      'executeDrupal7Migration',
      $is_export_only
    );
  }

}
