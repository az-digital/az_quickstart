<?php

namespace Drupal\config_filter\Plugin;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\config_filter\ConfigFilterManagerInterface;

/**
 * Provides the Config filter plugin plugin manager.
 */
class ConfigFilterPluginManager extends DefaultPluginManager implements ConfigFilterManagerInterface {

  /**
   * Constructor for ConfigFilterPluginManager objects.
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
    parent::__construct('Plugin/ConfigFilter', $namespaces, $module_handler, 'Drupal\config_filter\Plugin\ConfigFilterInterface', 'Drupal\config_filter\Annotation\ConfigFilter');

    $this->alterInfo('config_filter_info');
    $this->setCacheBackend($cache_backend, 'config_filter_plugins');
  }

  /**
   * {@inheritdoc}
   */
  public function getFiltersForStorages(array $storage_names, array $excluded = []) {
    $definitions = $this->getDefinitions();
    $filters = [];
    foreach ($definitions as $id => $definition) {
      if ($definition['status'] && array_intersect($storage_names, $definition['storages']) && !in_array($id, $excluded)) {
        $filters[$id] = $this->createInstance($id, $definition);
      }
    }

    return $filters;
  }

  /**
   * {@inheritdoc}
   */
  public function getFilterInstance($id) {
    $definitions = $this->getDefinitions();
    if (array_key_exists($id, $definitions)) {
      return $this->createInstance($id, $definitions[$id]);
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  protected function findDefinitions() {
    $definitions = array_map(function ($definition) {
      if (empty($definition['storages'])) {
        // The sync storage is the default.
        $definition['storages'] = ['config.storage.sync'];
      }
      return $definition;
    }, parent::findDefinitions());

    // Sort the definitions by weight.
    uasort($definitions, ['Drupal\Component\Utility\SortArray', 'sortByWeightElement']);

    return $definitions;
  }

}
