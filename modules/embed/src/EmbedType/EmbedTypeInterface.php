<?php

namespace Drupal\embed\EmbedType;

use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Component\Plugin\DependentPluginInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Provides an interface for an embed type and its metadata.
 *
 * @ingroup embed_api
 */
interface EmbedTypeInterface extends ConfigurableInterface, DependentPluginInterface, PluginFormInterface, PluginInspectionInterface {

  /**
   * Gets a configuration value.
   *
   * @param string $name
   *   The name of the plugin configuration value.
   * @param mixed $default
   *   The default value to return if the configuration value does not exist.
   *
   * @return mixed
   *   The currently set configuration value, or the value of $default if the
   *   configuration value is not set.
   */
  public function getConfigurationValue($name, $default = NULL);

  /**
   * Sets a configuration value.
   *
   * @param string $name
   *   The name of the plugin configuration value.
   * @param mixed $value
   *   The value to set.
   */
  public function setConfigurationValue($name, $value);

  /**
   * Gets the default icon URL for the embed type.
   *
   * @return string
   *   The URL to the default icon. This can be a relative path from the Drupal
   *   root.
   */
  public function getDefaultIconUrl();

}
