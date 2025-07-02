<?php

namespace Drupal\config_provider\Plugin;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Provides the Configuration provider plugin manager.
 */
class ConfigProviderManager extends DefaultPluginManager {

  /**
   * Constructor for ConfigProviderManager objects.
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
    parent::__construct('Plugin/ConfigProvider', $namespaces, $module_handler, 'Drupal\config_provider\Plugin\ConfigProviderInterface', 'Drupal\config_provider\Annotation\ConfigProvider');

    $this->alterInfo('config_provider_config_provider_info');
    $this->setCacheBackend($cache_backend, 'config_provider_config_provider_plugins');
  }

  /**
   * {@inheritdoc}
   */
  public function getDefinitions() {
    $definitions = parent::getDefinitions();
    // Sort definitions by weight.
    uasort($definitions, '\Drupal\Component\Utility\SortArray::sortByWeightElement');
    return $definitions;
  }

}
