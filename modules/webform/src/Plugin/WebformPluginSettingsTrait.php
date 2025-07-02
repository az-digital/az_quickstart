<?php

namespace Drupal\webform\Plugin;

/**
 * Provides a plugin settings trait.
 */
trait WebformPluginSettingsTrait {

  /**
   * {@inheritdoc}
   */
  public function getSettings() {
    $configuration = $this->getConfiguration();
    return $configuration['settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function setSettings(array $settings) {
    $configuration = $this->getConfiguration();
    $configuration['settings'] = $settings + $configuration['settings'];
    $this->setConfiguration($configuration);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSetting($key) {
    $configuration = $this->getConfiguration();
    return $configuration['settings'][$key] ?? NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setSetting($key, $value) {
    $configuration = $this->getConfiguration();
    $configuration['settings'][$key] = $value;
    return $this->setConfiguration($configuration);
  }

}
