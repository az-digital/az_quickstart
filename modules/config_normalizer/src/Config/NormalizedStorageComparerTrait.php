<?php

namespace Drupal\config_normalizer\Config;

use Drupal\config_normalizer\Plugin\ConfigNormalizerManager;
use Drupal\Core\Config\ConfigManagerInterface;
use Drupal\Core\Config\StorageComparer;
use Drupal\Core\Config\StorageInterface;

/**
 * Using this trait will add a ::createStorageComparer() method to the class.
 *
 * If the class is capable of injecting services from the container, it should
 * inject the 'config.manager' service by calling $this->setConfigManager() and
 * the 'plugin.manager.config_normalizer' service by calling
 * $this->setNormalizerManager().
 */
trait NormalizedStorageComparerTrait {

  /**
   * The normalizer plugin manager.
   *
   * @var \Drupal\config_normalizer\Plugin\ConfigNormalizerManager
   */
  protected $normalizerManager;

  /**
   * The configuration manager.
   *
   * @var \Drupal\Core\Config\ConfigManagerInterface
   */
  protected $configManager;

  /**
   * Creates and returns a storage comparer.
   *
   * @param \Drupal\Core\Config\StorageInterface $source_storage
   *   The source storage.
   * @param \Drupal\Core\Config\StorageInterface $target_storage
   *   The target storage.
   * @param string $mode
   *   (optional) The normalization mode.
   *
   * @return \Drupal\Core\Config\StorageComparer
   *   A storage comparer.
   */
  protected function createStorageComparer(StorageInterface $source_storage, StorageInterface $target_storage, $mode = NormalizedReadOnlyStorageInterface::DEFAULT_NORMALIZATION_MODE) {
    $source_context = [
      'normalization_mode' => $mode,
      'reference_storage_service' => $target_storage,
    ];

    $target_context = [
      'normalization_mode' => $mode,
      'reference_storage_service' => $source_storage,
    ];

    // Set up a storage comparer using normalized storages.
    $storage_comparer = new StorageComparer(
      new NormalizedReadOnlyStorage($source_storage, $this->getNormalizerManager(), $source_context),
      new NormalizedReadOnlyStorage($target_storage, $this->getNormalizerManager(), $target_context),
      $this->getConfigManager()
    );

    return $storage_comparer;
  }

  /**
   * Gets the configuration manager service.
   *
   * @return \Drupal\Core\Config\ConfigManagerInterface
   *   The configuration manager.
   */
  protected function getConfigManager() {
    if (!$this->configManager) {
      $this->configManager = \Drupal::service('config.manager');
    }
    return $this->configManager;
  }

  /**
   * Sets the configuration manager service to use.
   *
   * @param \Drupal\Core\Config\ConfigManagerInterface $config_manager
   *   The configuration manager service.
   *
   * @return $this
   */
  public function setConfigManager(ConfigManagerInterface $config_manager) {
    $this->configManager = $config_manager;
    return $this;
  }

  /**
   * Gets the normalizer manager service.
   *
   * @return \Drupal\config_normalizer\Plugin\ConfigNormalizerManager
   *   The normalizer manager.
   */
  protected function getNormalizerManager() {
    if (!$this->normalizerManager) {
      $this->normalizerManager = \Drupal::service('plugin.manager.config_normalizer');
    }
    return $this->normalizerManager;
  }

  /**
   * Sets the normalizer manager service to use.
   *
   * @param \Drupal\config_normalizer\Plugin\ConfigNormalizerManager $normalizer_manager
   *   The normalizer manager service.
   *
   * @return $this
   */
  public function setNormalizerManager(ConfigNormalizerManager $normalizer_manager) {
    $this->normalizerManager = $normalizer_manager;
    return $this;
  }

}
