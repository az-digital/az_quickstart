<?php

namespace Drupal\Tests\inline_entity_form\Kernel\Migrate;

use Drupal\Tests\migrate_drupal\Kernel\MigrateDrupalTestBase;

/**
 * Base class for migration tests.
 */
abstract class MigrateTestBase extends MigrateDrupalTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->loadFixture($this->getFixtureFilePath());
  }

  /**
   * Gets the path to the fixture file.
   */
  protected function getFixtureFilePath() {
    return __DIR__ . '/../../../fixtures/drupal7-small.php';
  }

}
