<?php

namespace Drupal\config_update;

use Drupal\Component\EventDispatcher\Event;

/**
 * Event context class for configuration pre-revert/pre-import events.
 *
 * This class is passed in as the event when the
 * \Drupal\config_update\ConfigRevertInterface::PRE_IMPORT and
 * \Drupal\config_update\ConfigRevertInterface::PRE_REVERT events are triggered.
 */
class ConfigPreRevertEvent extends Event {

  /**
   * The type of configuration that is being imported or reverted.
   *
   * @var string
   */
  protected $type;

  /**
   * The name of the config item being imported or reverted, without prefix.
   *
   * @var string
   */
  protected $name;

  /**
   * The current configuration to be applied.
   *
   * @var array
   */
  protected $value;

  /**
   * The active configuration.
   *
   * Only available on \Drupal\config_update\ConfigRevertInterface::PRE_REVERT.
   *
   * @var array|null
   */
  protected $active;

  /**
   * Constructs a new ConfigPreRevertEvent.
   *
   * @param string $type
   *   The type of configuration being imported or reverted.
   * @param string $name
   *   The name of the config item being imported/reverted, without prefix.
   * @param array $value
   *   The current configuration.
   * @param array|null $active
   *   The active configuration.
   */
  public function __construct($type, $name, array $value, $active) {
    $this->type = $type;
    $this->name = $name;
    $this->active = $active;
    $this->value = $value;
  }

  /**
   * Returns the type of configuration being imported or reverted.
   *
   * @return string
   *   The type of configuration, either 'system.simple' or a config entity
   *   type machine name.
   */
  public function getType() {
    return $this->type;
  }

  /**
   * Returns the name of the config item, without prefix.
   *
   * @return string
   *   The name of the config item being imported/reverted/deleted, with the
   *   prefix.
   */
  public function getName() {
    return $this->name;
  }

  /**
   * Returns the current configuration value to be applied.
   *
   * @return array
   *   The configuration value.
   */
  public function getValue() {
    return $this->value;
  }

  /**
   * Set the configuration value to import or revert to after the event.
   *
   * This new value will be imported in place of the configuration value
   * originally read from the filesystem.
   *
   * @param array $value
   *   The configuration value.
   */
  public function setValue(array $value) {
    $this->value = $value;
  }

  /**
   * Returns the active configuration.
   *
   * Only available on \Drupal\config_update\ConfigRevertInterface::PRE_REVERT.
   *
   * @return array|null
   *   The active configuration.
   */
  public function getActive() {
    return $this->active;
  }

}
