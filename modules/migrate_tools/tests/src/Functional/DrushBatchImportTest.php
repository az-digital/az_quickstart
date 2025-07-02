<?php

declare(strict_types = 1);

namespace Drupal\Tests\migrate_tools\Functional;

use Drupal\Tests\BrowserTestBase;
use Drush\TestTraits\DrushTestTrait;

/**
 * Test that batch import runs correctly in drush command.
 *
 * @group migrate_tools
 */
final class DrushBatchImportTest extends BrowserTestBase {
  use DrushTestTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'migrate_tools_test',
    'migrate_tools',
    'migrate_plus',
    'taxonomy',
    'text',
    'system',
    'user',
  ];

  /**
   * Tests that a batch import run from a custom drush command succeeds.
   */
  public function testBatchImportInDrushComand(): void {
    $this->drush('migrate:batch-import-fruit');
    $migration = \Drupal::service('plugin.manager.migration')->createInstance('fruit_terms');
    $id_map = $migration->getIdMap();
    $this->assertSame(3, $id_map->importedCount());
  }

}
