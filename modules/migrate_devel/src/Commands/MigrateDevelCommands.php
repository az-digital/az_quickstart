<?php

namespace Drupal\migrate_devel\Commands;

use Consolidation\AnnotatedCommand\CommandData;
use Drush\Commands\DrushCommands;

/**
 * Extends migrate commands with debugging options.
 */
class MigrateDevelCommands extends DrushCommands {

  /**
   * Register two new options for the migrate:import command.
   *
   * @hook command migrate:import
   * @option migrate-debug Enable Debug Mode
   * @option migrate-debug-pre Enable Debug Mode (Before Row Save)
   */
  public function additionalOptionsMigrateImport(CommandData $commandData) {
    // No action required here. The new options will be examined in the
    // migrate event subscriber methods.
  }

}
