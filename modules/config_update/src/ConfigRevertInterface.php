<?php

namespace Drupal\config_update;

/**
 * Defines an interface for config import and revert operations.
 */
interface ConfigRevertInterface {

  /**
   * Name of the event triggered before configuration import.
   *
   * @see \Drupal\config_update\ConfigPreRevertEvent
   * @see \Drupal\config_update\ConfigRevertInterface::import()
   */
  const PRE_IMPORT = 'config_update.pre_import';

  /**
   * Name of the event triggered on configuration import.
   *
   * @see \Drupal\config_update\ConfigRevertEvent
   * @see \Drupal\config_update\ConfigRevertInterface::import()
   */
  const IMPORT = 'config_update.import';

  /**
   * Name of the event triggered before configuration revert.
   *
   * @see \Drupal\config_update\ConfigPreRevertEvent
   * @see \Drupal\config_update\ConfigRevertInterface::revert()
   */
  const PRE_REVERT = 'config_update.pre_revert';

  /**
   * Name of the event triggered on configuration revert.
   *
   * @see \Drupal\config_update\ConfigRevertEvent
   * @see \Drupal\config_update\ConfigRevertInterface::revert()
   */
  const REVERT = 'config_update.revert';

  /**
   * Imports configuration from extension storage to active storage.
   *
   * This action triggers the ConfigRevertInterface::PRE_IMPORT and
   * ConfigRevertInterface::IMPORT events if the configuration could be
   * imported.
   *
   * @param string $type
   *   The type of configuration.
   * @param string $name
   *   The name of the config item, without the prefix.
   *
   * @return bool
   *   TRUE if the operation succeeded; FALSE if the configuration could not
   *   be found to import. May also throw exceptions if there is a problem
   *   during saving the configuration.
   *
   * @see \Drupal\config_update\ConfigRevertInterface::PRE_IMPORT
   * @see \Drupal\config_update\ConfigRevertInterface::IMPORT
   */
  public function import($type, $name);

  /**
   * Reverts configuration to the value from extension storage.
   *
   * This action triggers the ConfigRevertInterface::PRE_REVERT and
   * ConfigRevertInterface::REVERT events if the configuration could be
   * reverted.
   *
   * @param string $type
   *   The type of configuration.
   * @param string $name
   *   The name of the config item, without the prefix.
   *
   * @return bool
   *   TRUE if the operation succeeded; FALSE if the base configuration could
   *   not be found to revert to. May also throw exceptions if there is a
   *   problem during saving the configuration.
   *
   * @see \Drupal\config_update\ConfigRevertInterface::PRE_REVERT
   * @see \Drupal\config_update\ConfigRevertInterface::REVERT
   */
  public function revert($type, $name);

  /**
   * Gets the current active value of configuration.
   *
   * @param string $type
   *   The type of configuration. Or pass '' to indicate that $name is the full
   *   name.
   * @param string $name
   *   The name of the config item, without the prefix.
   *
   * @return array
   *   The configuration value, or FALSE if it is not found.
   */
  public function getFromActive($type, $name);

  /**
   * Gets the extension storage value of configuration.
   *
   * This is the value from a file in the config/install or config/optional
   * directory of a module, theme, or install profile.
   *
   * @param string $type
   *   The type of configuration. Or pass '' to indicate that $name is the full
   *   name.
   * @param string $name
   *   The name of the config item, without the prefix.
   *
   * @return array|false
   *   The configuration value, or FALSE if it could not be located.
   */
  public function getFromExtension($type, $name);

}
