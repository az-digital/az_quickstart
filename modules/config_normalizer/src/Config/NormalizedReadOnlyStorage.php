<?php

namespace Drupal\config_normalizer\Config;

use Drupal\config_normalizer\ConfigItemNormalizer;
use Drupal\config_normalizer\Plugin\ConfigNormalizerManager;
use Drupal\Core\Config\ReadOnlyStorage;
use Drupal\Core\Config\StorageInterface;

/**
 * Defines the normalized read only storage.
 */
class NormalizedReadOnlyStorage extends ReadOnlyStorage implements NormalizedReadOnlyStorageInterface {

  /**
   * The config normalizer manager.
   *
   * @var \Drupal\config_normalizer\Plugin\ConfigNormalizerManager
   */
  protected $normalizerManager;

  /**
   * The config item normalizer.
   *
   * @var \Drupal\config_normalizer\ConfigItemNormalizer
   */
  protected $configItemNormalizer;

  /**
   * An array of key-value pairs to pass additional context when needed.
   *
   * @var array
   */
  protected $context;

  /**
   * Create a NormalizedReadOnlyStorage decorating another storage.
   *
   * @param \Drupal\Core\Config\StorageInterface $storage
   *   The decorated storage.
   * @param \Drupal\config_normalizer\Plugin\ConfigNormalizerManager $normalizer_manager
   *   The normalization manager.
   * @param array $context
   *   (optional) An array of key-value pairs to pass additional context when
   *   needed.
   */
  public function __construct(StorageInterface $storage, ConfigNormalizerManager $normalizer_manager, array $context = []) {
    parent::__construct($storage);
    $this->normalizerManager = $normalizer_manager;
    $this->configItemNormalizer = new ConfigItemNormalizer($normalizer_manager, $context);
    $this->setContext($context);
  }

  /**
   * {@inheritdoc}
   */
  public function getContext() {
    return $this->context;
  }

  /**
   * {@inheritdoc}
   */
  public function setContext(array $context = []) {
    $context += NormalizedReadOnlyStorageInterface::DEFAULT_CONTEXT;

    $this->context = $context;
  }

  /**
   * {@inheritdoc}
   */
  public function read($name) {
    $data = parent::read($name);

    $data = $this->normalize($name, $data);

    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function readMultiple(array $names) {
    $list = parent::readMultiple($names);

    foreach ($list as $name => &$data) {
      $data = $this->normalize($name, $data);
    }

    return $list;
  }

  /**
   * {@inheritdoc}
   */
  public function createCollection($collection) {
    return new static(
      $this->storage->createCollection($collection),
      $this->normalizerManager,
      $this->context
    );
  }

  /**
   * Normalizes configuration data.
   *
   * @param string $name
   *   The name of a configuration object to load.
   * @param array $data
   *   The configuration data to normalize.
   *
   * @return array|bool
   *   The configuration data stored for the configuration object name. If no
   *   configuration data exists for the given name, FALSE is returned.
   */
  protected function normalize($name, $data) {
    if (!is_bool($data)) {
      $data = $this->configItemNormalizer->normalize($name, $data, $this->context);
    }

    return $data;
  }

}
