<?php

namespace Drupal\Tests\migmag\Functional;

/**
 * Tests Drupal 7 upgrade using the migrate UI.
 *
 * This test executes the migration of the Drupal 7 DB fixture twice.
 *
 * After the first migration finishes, the content entities' default revision
 * is exported to an EME-generated module (into JSON files) to a location
 * which is retained between test cases are executed.
 *
 * The second case repeats the same migration process with every migmag module
 * being enabled, creates an another export, and compares the end result of
 * the two JSON data set.
 *
 * @group migmag
 */
class MigMagUpgrade7Test extends MigMagCoreMigrationTestBase {

  /**
   * Executes the test of Drupal 7 migration and saves content output.
   */
  public function testCreateBaseDrupal7MigrationDump() {
    $this->executeDrupal7Migration();
    $this->ensureBaseExportIsPresent();
    $this->assertTrue(TRUE);
  }

  /**
   * Checks whether Migration Magician doesn't breaks core content migrations.
   *
   * @depends testCreateBaseDrupal7MigrationDump
   */
  public function testMigMagCoreCompatibility() {
    $this->enableAllMigmagModule();
    // Migmag modules reduce the amount of errors.
    $this->setNumberOfExpectedLoggedErrors(17);
    $this->executeDrupal7Migration();
    $this->createActualExport();
    $this->compareEntityContentExportSets();
    $this->removeTempBaseExportModule();
  }

}
