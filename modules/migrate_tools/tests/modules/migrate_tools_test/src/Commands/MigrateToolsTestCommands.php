<?php

declare(strict_types = 1);

namespace Drupal\migrate_tools_test\Commands;

use Drupal\migrate\MigrateMessage;
use Drupal\migrate\Plugin\MigrationPluginManager;
use Drupal\migrate_tools\MigrateBatchExecutable;
use Drush\Commands\DrushCommands;

/**
 * Migrate Tools Test drush commands.
 */
final class MigrateToolsTestCommands extends DrushCommands {

  protected MigrationPluginManager $migrationPluginManager;

  /**
   * MigrateToolsTestCommands constructor.
   *
   * @param \Drupal\migrate\Plugin\MigrationPluginManager $migrationPluginManager
   *   The Migration Plugin Manager.
   */
  public function __construct(MigrationPluginManager $migrationPluginManager) {
    parent::__construct();
    $this->migrationPluginManager = $migrationPluginManager;
  }

  /**
   * Run a batch import of fruit terms as a test.
   *
   * @command migrate:batch-import-fruit
   */
  public function batchImportFruit(): void {
    $fruit_migration = $this->migrationPluginManager->createInstance('fruit_terms');
    $executable = new MigrateBatchExecutable($fruit_migration, new MigrateMessage());
    $executable->batchImport();
    drush_backend_batch_process();
  }

}
