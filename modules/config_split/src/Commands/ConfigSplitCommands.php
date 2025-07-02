<?php

declare(strict_types=1);

namespace Drupal\config_split\Commands;

use Consolidation\AnnotatedCommand\CommandData;
use Drupal\config_split\ConfigSplitCliService;
use Drush\Commands\DrushCommands;

/**
 * The Drush 10 commands.
 */
class ConfigSplitCommands extends DrushCommands {

  /**
   * The interoperability cli service.
   *
   * @var \Drupal\config_split\ConfigSplitCliService
   */
  protected $cliService;

  /**
   * ConfigSplitCommands constructor.
   *
   * @param \Drupal\config_split\ConfigSplitCliService $cliService
   *   The CLI service which allows interoperability.
   */
  public function __construct(ConfigSplitCliService $cliService) {
    parent::__construct();
    $this->cliService = $cliService;
  }

  /**
   * Export only split configuration to a directory.
   *
   * @param string $split
   *   The split configuration to export.
   *
   * @command config-split:export
   *
   * @usage drush config-split:export development
   *   Export configuration of the "development" split
   *
   * Propose an alias at:
   *   https://www.drupal.org/project/config_split/issues/3181368
   */
  public function splitExport($split) {
    return $this->cliService->ioExport($split, $this->io(), 'dt') ? DrushCommands::EXIT_SUCCESS : DrushCommands::EXIT_FAILURE;
  }

  /**
   * Import only config from a split.
   *
   * @param string $split
   *   The split configuration to import.
   *
   * @command config-split:import
   *
   * @usage drush config-split:import development
   *   Import configuration of the "development" split
   *
   * Propose an alias at:
   *   https://www.drupal.org/project/config_split/issues/3181368
   */
  public function splitImport($split) {
    return $this->cliService->ioImport($split, $this->io(), 'dt') ? DrushCommands::EXIT_SUCCESS : DrushCommands::EXIT_FAILURE;
  }

  /**
   * Activate a config split.
   *
   * @param string $split
   *   The split configuration to activate.
   *
   * @command config-split:activate
   *
   * @usage drush config-split:activate development
   *   Activate configuration of the "development" split
   *
   * Propose an alias at:
   *   https://www.drupal.org/project/config_split/issues/3181368
   */
  public function splitActivate($split) {
    return $this->cliService->ioActivate($split, $this->io(), 'dt') ? DrushCommands::EXIT_SUCCESS : DrushCommands::EXIT_FAILURE;
  }

  /**
   * Deactivate a config split.
   *
   * @param string $split
   *   The split configuration to deactivate.
   * @param array $options
   *   The options.
   *
   * @command config-split:deactivate
   *
   * @option override
   *   Allows the deactivation via override.
   *
   * @usage drush config-split:deactivate development
   *   Deactivate configuration of the "development" split
   *
   * Propose an alias at:
   *   https://www.drupal.org/project/config_split/issues/3181368
   */
  public function splitDeactivate($split, array $options = ['override' => FALSE]) {
    return $this->cliService->ioDeactivate($split, $this->io(), 'dt', FALSE, $options['override']) ? DrushCommands::EXIT_SUCCESS : DrushCommands::EXIT_FAILURE;
  }

  /**
   * Override the status of a split via state.
   *
   * @param string $name
   *   The split name.
   * @param string|int|bool $status
   *   One of: active|1|true| inactive|0|false| default||null|none.
   *
   * @command config-split:status-override
   *
   * @usage drush config-split:status-override development active
   *   Set (or get without a status argument) the status config override.
   *
   * @aliases csso
   */
  public function statusConfigOverride(string $name, $status = '') {
    return $this->cliService->statusOverride($name, $status, $this->io(), 'dt') ? DrushCommands::EXIT_SUCCESS : DrushCommands::EXIT_FAILURE;
  }

  /**
   * React to the config export, write the splits to their storages.
   *
   * @hook post-command config:export
   */
  public function postConfigExport($result, CommandData $commandData) {
    // The config export command aborts if it is not exporting.
    // So here we know that the config was exported, so we need to also export
    // the split config to where they need to be.
    $this->cliService->postExportAll();
  }

}
