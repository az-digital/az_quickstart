<?php

namespace Drupal\webform\Plugin;

/**
 * An interface for managing a plugin's settings\.
 */
interface WebformPluginSettingsInterface {

  /**
   * Returns the plugin's settings.
   *
   * @return array
   *   A structured array containing all the plugin's settings.
   */
  public function getSettings();

  /**
   * Update a plugin's settings.
   *
   * @param array $settings
   *   The structured array containing the plugin's settings to be updated.
   *
   * @return $this
   */
  public function setSettings(array $settings);

  /**
   * Returns the plugin setting for given key.
   *
   * @param string $key
   *   The key of the plugin setting to retrieve.
   *
   * @return mixed
   *   The settings value, or NULL if no settings exists.
   */
  public function getSetting($key);

  /**
   * Sets a plugin setting for a given key.
   *
   * @param string $key
   *   The key of the setting to be updated.
   * @param mixed $value
   *   The value for the settings..
   *
   * @return $this
   */
  public function setSetting($key, $value);

}
