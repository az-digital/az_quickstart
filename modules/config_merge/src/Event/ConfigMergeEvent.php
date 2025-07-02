<?php

namespace Drupal\config_merge\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * Configuration Merge Event class.
 *
 * @package Drupal\config_merge\Event
 */
class ConfigMergeEvent extends Event {

  /**
   * Config name that is being processed.
   *
   * @var string
   */
  protected $configName;

  /**
   * Array of logs keyed by operation name.
   *
   * @var array
   */
  protected $logs;

  /**
   * Config provider type.
   *
   * @var string
   */
  protected $providerType;

  /**
   * Config provider name.
   *
   * @var string
   */
  protected $providerName;

  /**
   * ConfigMergeEvent constructor.
   *
   * @param string $config_name
   *   The name of the configuration object being changed.
   * @param array $logs
   *   The logs array of merged or unmerged properties with the reason.
   * @param string $provider_type
   *   The type of the configuration provider.
   * @param string $provider_name
   *   The name of the configuration provider.
   */
  public function __construct($config_name, array $logs, $provider_type = '', $provider_name = '') {
    $this->configName = $config_name;
    $this->logs = $logs;
    $this->providerType = $provider_type;
    $this->providerName = $provider_name;
  }

  /**
   * Gets the name of the configuration object that was in process.
   *
   * @return string
   *   The name of the configuration object being changed.
   */
  public function getConfigName() {
    return $this->configName;
  }

  /**
   * Gets the logs array of the merge process.
   *
   * @return array
   *   The logs array of merged or unmerged properties with the reason.
   */
  public function getLogs() {
    return $this->logs;
  }

  /**
   * Gets the config provider type.
   *
   * @return string
   *   The type of the configuration provider.
   */
  public function getProviderType() {
    return $this->providerType;
  }

  /**
   * Gets the config provider name.
   *
   * @return string
   *   The name of the configuration provider.
   */
  public function getProviderName() {
    return $this->providerName;
  }

}
