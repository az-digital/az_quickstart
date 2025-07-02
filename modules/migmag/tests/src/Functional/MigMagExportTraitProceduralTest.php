<?php

namespace Drupal\Tests\migmag\Functional;

/**
 * Tests MigMagExportTrait with Drupal 7 upgrade end result using migrate UI.
 *
 * @group migmag
 */
class MigMagExportTraitProceduralTest extends MigMagCoreMigrationTestBase {

  /**
   * Executes the test of Drupal 7 migration and saves content output.
   */
  public function testDrupal7MigrationInitial() {
    $this->executeDrupal7Migration();
    $this->ensureBaseExportIsPresent();
    $this->assertTrue(TRUE);
  }

  /**
   * Executes the test of Drupal 7 migration and compares output with the prev.
   *
   * @depends testDrupal7MigrationInitial
   */
  public function testDrupal7MigrationAgainAndCompare() {
    $this->executeDrupal7Migration();
    $this->createActualExport();
    $this->compareEntityContentExportSets();
    $this->removeTempBaseExportModule();
  }

}
