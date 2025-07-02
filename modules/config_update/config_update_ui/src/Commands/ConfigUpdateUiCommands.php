<?php

namespace Drupal\config_update_ui\Commands;

use Drupal\Component\Diff\DiffFormatter;
use Drupal\config_update\ConfigDiffer;
use Drupal\config_update\ConfigListerWithProviders;
use Drupal\config_update\ConfigReverter;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drush\Commands\DrushCommands;
use Drush\Drush;

/**
 * A set of Drush commands for Config Update Manager.
 */
class ConfigUpdateUiCommands extends DrushCommands {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityManager;

  /**
   * The config differ.
   *
   * @var \Drupal\config_update\ConfigDiffer
   */
  protected ConfigDiffer $configDiff;

  /**
   * The config lister.
   *
   * @var \Drupal\config_update\ConfigListerWithProviders
   */
  protected ConfigListerWithProviders $configList;

  /**
   * The config reverter.
   *
   * @var \Drupal\config_update\ConfigReverter
   */
  protected ConfigReverter $configUpdate;

  /**
   * Constructs a ConfigUpdateUiCommands object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityManager
   *   The entity type manager.
   * @param \Drupal\config_update\ConfigDiffer $configDiff
   *   The config differ.
   * @param \Drupal\config_update\ConfigListerWithProviders $configList
   *   The config lister.
   * @param \Drupal\config_update\ConfigReverter $configUpdate
   *   The config reverter.
   */
  public function __construct(EntityTypeManagerInterface $entityManager, ConfigDiffer $configDiff, ConfigListerWithProviders $configList, ConfigReverter $configUpdate) {
    parent::__construct();
    $this->entityManager = $entityManager;
    $this->configDiff = $configDiff;
    $this->configList = $configList;
    $this->configUpdate = $configUpdate;
    $this->logger = Drush::logger();
  }

  /**
   * Lists config types.
   *
   * @return \Consolidation\OutputFormatters\StructuredData\RowsOfFields
   *   A structured data object of rows of configuration types.
   *
   * @command config:list-types
   * @aliases clt,config-list-types
   */
  public function listTypes() {
    $definitions = $this->configList->listTypes();

    return array_keys($definitions);

  }

  /**
   * Displays added config items.
   *
   * Displays a list of config items that did not come from your installed
   * modules, themes, or install profile.
   *
   * @param string $name
   *   The type of config to report on. See config-list-types to list them.
   *   You can also use system.all for all types, or system.simple for simple
   *   config.
   *
   * @return \Consolidation\OutputFormatters\StructuredData\RowsOfFields
   *   A structured data object of rows of added configuration items.
   *
   * @usage drush config-added-report action
   *   Displays the added config report for action config.
   *
   * @command config:added-report
   * @aliases cra,config-added-report
   */
  public function addedReport(string $name) {
    [$activeList, $installList, $optionalList] = $this->configList->listConfig('type', $name);
    $addedItems = array_diff($activeList, $installList, $optionalList);
    if (!count($addedItems)) {
      $this->logger->success(dt('No added config.'));
    }
    sort($addedItems);

    return $addedItems;
  }

  /**
   * Displays missing config items.
   *
   * Displays a list of config items from your installed modules, themes, or
   * install profile that are not currently in your active config.
   *
   * @param string $type
   *   Run the report for: module, theme, profile, or "type" for config entity
   *   type.
   * @param string $name
   *   The machine name of the module, theme, etc. to report on. See
   *   config-list-types to list types for config entities; you can also use
   *   system.all for all types, or system.simple for simple config.
   *
   * @return \Consolidation\OutputFormatters\StructuredData\RowsOfFields
   *   A structured data object of rows of missing configuration items.
   *
   * @usage drush config-missing-report type action
   *   Displays the missing config report for action config.
   *
   * @command config:missing-report
   * @aliases crm,config-missing-report
   */
  public function missingReport(string $type, string $name) {
    [$activeList, $installList] = $this->configList->listConfig($type, $name);
    $missingItems = array_diff($installList, $activeList);
    if (!count($missingItems)) {
      $this->logger->success(dt('No missing config.'));
    }
    sort($missingItems);

    return $missingItems;
  }

  /**
   * Displays optional config items.
   *
   * Displays a list of optional config items from your installed modules,
   * themes, or install profile that are not currently in your active config.
   *
   * @param string $type
   *   Run the report for: module, theme, profile, or "type" for config entity
   *   type.
   * @param string $name
   *   The machine name of the module, theme, etc. to report on. See
   *   config-list-types to list types for config entities; you can also use
   *   system.all for all types, or system.simple for simple config.
   *
   * @return \Consolidation\OutputFormatters\StructuredData\RowsOfFields
   *   A structured data object of rows of inactive configuration items.
   *
   * @usage drush config-inactive-report type action
   *   Displays the inactive config report for action config.
   *
   * @command config:inactive-report
   * @aliases cri,config-inactive-report
   */
  public function inactiveReport(string $type, string $name) {
    [$activeList, $optionalList] = $this->configList->listConfig($type, $name);
    $inactiveItems = array_diff($optionalList, $activeList);
    if (!count($inactiveItems)) {
      $this->logger->success(dt('No inactive config.'));
    }
    sort($inactiveItems);

    return $inactiveItems;
  }

  /**
   * Displays differing config items.
   *
   * Displays a list of config items that differ from the versions provided by
   * your installed modules, themes, or install profile. See config-diff to
   * show what the differences are.
   *
   * @param string $type
   *   Run the report for: module, theme, profile, or "type" for config entity
   *   type.
   * @param string $name
   *   The machine name of the module, theme, etc. to report on. See
   *   config-list-types to list types for config entities; you can also use
   *   system.all for all types, or system.simple for simple config.
   *
   * @return \Consolidation\OutputFormatters\StructuredData\RowsOfFields
   *   A structured data object of rows of differing configuration items.
   *
   * @usage drush config-different-report type action
   *   Displays the differing config report for action config.
   *
   * @command config:different-report
   * @aliases crd,config-different-report
   */
  public function differentReport(string $type, string $name) {
    $differentItems = $this->getDifferentItems($type, $name);
    if (!count($differentItems)) {
      $this->logger->success(dt('No different config'));
    }

    return $differentItems;
  }

  /**
   * Displays a diff of a config item.
   *
   * Displays line-by-line differences for one config item between your active
   * config and the version currently being provided by an installed module,
   * theme, or install profile.
   *
   * @param string $name
   *   The config item to diff. See config-different-report to list config
   *   items that are different.
   *
   * @return string
   *   The formatted diff output.
   *
   * @usage drush config-diff block.block.olivero_search
   *   Displays the config differences for the search block
   *   in the Olivero theme.
   *
   * @command config:diff
   * @aliases cfd,config-diff
   */
  public function diff(string $name): string {
    $extension = $this->configUpdate->getFromExtension('', $name);
    $active = $this->configUpdate->getFromActive('', $name);
    if ($extension && $active) {
      $diff = $this->configDiff->diff($extension, $active);
      // Drupal\Component\Diff\DiffFormatter does not expose a service so we
      // instantiate it manually here.
      $diffFormatter = new DiffFormatter();
      return $diffFormatter->format($diff);
    }

    $this->logger->error(dt('Config is missing, cannot diff.'));
    return '';
  }

  /**
   * Reverts a config item.
   *
   * Reverts one config item in active storage to the version provided by an
   * installed module, theme, or install profile.
   *
   * @param string $name
   *   The config item to revert. See config-different-report to list config
   *   items that are different.
   *
   * @usage drush config-revert block.block.olivero_search
   *   Revert the config for the search block in the Olivero theme to the
   *   version provided by the install profile.
   *
   * @command config:revert
   * @aliases cfr,config-revert
   */
  public function revert(string $name) {
    $type = $this->configList->getTypeNameByConfigName($name);
    // The lister gives NULL if simple configuration, but the reverter expects
    // 'system.simple' so we convert it.
    if ($type === NULL) {
      $type = 'system.simple';
    }
    $shortname = $this->getConfigShortname($type, $name);
    if ($this->configUpdate->revert($type, $shortname)) {
      $this->logger->success(dt('The configuration item @name was reverted to its source.', ['@name' => $name]));
    }
    else {
      $this->logger->error(dt('There was an error and the configuration item @name was not reverted.', ['@name' => $name]));
    }
  }

  /**
   * Imports missing config item.
   *
   * Imports a missing or inactive config item provided by an installed module,
   * theme, or install profile. Be sure that requirements are met.
   *
   * @param string $name
   *   The name of the config item to import (usually the ID you would see in
   *   the user interface). See config-missing-report to list config items that
   *   are missing, and config-inactive-report to list config items that are
   *   inactive.
   *
   * @usage drush config-import-missing block.block.olivero_search
   *   Import the config for the search block in the Olivero theme from the
   *   version provided by the install profile.
   *
   * @command config:import-missing
   * @aliases cfi,config-import-missing
   */
  public function importMissing(string $name) {
    $type = $this->configList->getTypeNameByConfigName($name);
    // The lister gives NULL if simple configuration, but the reverter expects
    // 'system.simple' so we convert it.
    if ($type === NULL) {
      $type = 'system.simple';
    }
    $shortname = $this->getConfigShortname($type, $name);
    if ($this->configUpdate->import($type, $shortname)) {
      $this->logger->success(dt('The configuration item @name was imported from its source.', ['@name' => $name]));
    }
    else {
      $this->logger->error(dt('There was an error and the configuration item @name was not imported.', ['@name' => $name]));
    }
  }

  /**
   * Reverts multiple config items to extension provided version.
   *
   * Reverts a set of config items to the versions provided by installed
   * modules, themes, or install profiles. A set is all differing items from
   * one extension, or one type of configuration.
   *
   * @param string $type
   *   Type of set to revert: "module" for all items from a module, "theme" for
   *   all items from a theme, "profile" for all items from the install profile,
   *   or "type" for all items of one config entity type. See
   *   config-different-report to list config items that are different.
   * @param string $name
   *   The machine name of the module, theme, etc. to revert items of. All
   *   items in the corresponding config-different-report will be reverted.
   *
   * @usage drush config-revert-multiple type action
   *   Revert all differing config items of type action.
   *
   * @command config:revert-multiple
   * @aliases cfrm,config-revert-multiple
   */
  public function revertMultiple(string $type, string $name) {
    $different = $this->getDifferentItems($type, $name);
    foreach ($different as $name) {
      $this->revert($name);
    }
  }

  /**
   * Lists differing config items.
   *
   * Lists config items that differ from the versions provided by your
   * installed modules, themes, or install profile. See config-diff to show
   * what the differences are.
   *
   * @param string $type
   *   Run the report for: module, theme, profile, or "type" for config entity
   *   type.
   * @param string $name
   *   The machine name of the module, theme, etc. to report on. See
   *   config-list-types to list types for config entities; you can also use
   *   system.all for all types, or system.simple for simple config.
   *
   * @return array
   *   An array of differing configuration items.
   */
  protected function getDifferentItems(string $type, string $name): array {
    [$activeList, $installList, $optionalList] = $this->configList->listConfig($type, $name);
    $addedItems = array_diff($activeList, $installList, $optionalList);
    $activeAndAddedItems = array_diff($activeList, $addedItems);
    $differentItems = [];
    foreach ($activeAndAddedItems as $name) {
      $active = $this->configUpdate->getFromActive('', $name);
      $extension = $this->configUpdate->getFromExtension('', $name);
      if (!$this->configDiff->same($active, $extension)) {
        $differentItems[] = $name;
      }
    }
    sort($differentItems);

    return $differentItems;
  }

  /**
   * Gets the config item shortname given the type and name.
   *
   * @param string $type
   *   The type of the config item.
   * @param string $name
   *   The name of the config item.
   *
   * @return string
   *   The shortname for the configuration item.
   */
  protected function getConfigShortname(string $type, string $name): string {
    $shortname = $name;
    if ($type != 'system.simple') {
      $definition = $this->entityManager->getDefinition($type);
      $prefix = $definition->getConfigPrefix() . '.';
      if (str_starts_with($name, $prefix)) {
        $shortname = substr($name, strlen($prefix));
      }
    }

    return $shortname;
  }

}
