<?php

namespace Drupal\blazy\Config\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides a common config entity for Slick, Splide, ElevateZoomPLus, etc.
 *
 * This will allow ElevateZoomPLus to support both Slick and Splide.
 *
 * @internal
 *   This is an internal part of the Blazy system and should only be used by
 *   blazy-related code in Blazy module, or its sub-modules.
 */
interface BlazyConfigEntityBaseInterface extends ConfigEntityInterface {

  /**
   * Returns the options by group, or property.
   *
   * @param string $group
   *   The name of setting group: settings, etc.
   * @param string $property
   *   The name of specific property.
   *
   * @return mixed|array|null
   *   Available options by $group, $property, all, or NULL.
   */
  public function getOptions($group = NULL, $property = NULL);

  /**
   * Sets the array of settings.
   *
   * @param array $options
   *   The array of options to merge.
   * @param bool $merged
   *   Whether to merge, or replace.
   *
   * @return $this
   *   The class instance that this method is called on.
   */
  public function setOptions(array $options, $merged = TRUE): self;

  /**
   * Returns the value of an option group.
   *
   * @param string $group
   *   The group name: settings, icon, etc.
   *
   * @return mixed
   *   The option value merged with defaults.
   */
  public function getOption($group);

  /**
   * Sets the value of an option.
   *
   * @param string $name
   *   The option name: settings, etc.
   * @param array|bool|int|string|null $value
   *   The option value.
   *
   * @return $this
   *   The class is being called.
   */
  public function setOption($name, $value): self;

  /**
   * Returns the array of settings.
   *
   * @param bool $ansich
   *   Whether to return the settings as is, normally without defaults.
   *
   * @return array
   *   The array of settings.
   */
  public function getSettings($ansich = FALSE): array;

  /**
   * Sets the array of settings.
   *
   * @param array $values
   *   The new array of setting values.
   * @param bool $merged
   *   Whether to merge with default values.
   *
   * @return $this
   *   The class instance that this method is called on.
   */
  public function setSettings(array $values, $merged = TRUE): self;

  /**
   * Returns the value of a setting.
   *
   * @param string $name
   *   The setting name.
   * @param bool|int|string|null $default
   *   The default value.
   *
   * @return mixed
   *   The setting value.
   */
  public function getSetting($name, $default = NULL);

  /**
   * Sets the value of a setting.
   *
   * @param string $name
   *   The setting name.
   * @param bool|int|string|null $value
   *   The setting value.
   *
   * @return $this
   *   The class instance that this method is called on.
   */
  public function setSetting($name, $value): self;

}
