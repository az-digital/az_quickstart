<?php

namespace Drupal\config_normalizer\Plugin;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Provides the Config normalizer plugin manager.
 */
class ConfigNormalizerManager extends DefaultPluginManager {

  use DependencySerializationTrait;

  /**
   * Constructs a new ConfigNormalizerManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/ConfigNormalizer', $namespaces, $module_handler, 'Drupal\config_normalizer\Plugin\ConfigNormalizerInterface', 'Drupal\config_normalizer\Annotation\ConfigNormalizer');

    $this->alterInfo('config_normalizer_normalizer_info');
    $this->setCacheBackend($cache_backend, 'config_normalizer_normalizer_plugins');
  }

}
