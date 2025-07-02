<?php

namespace Drupal\flag\FlagType;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * The plugin manager for flag type plugins.
 */
class FlagTypePluginManager extends DefaultPluginManager {

  /**
   * Constructs a new FlagTypePluginManager.
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
    parent::__construct('Plugin/Flag', $namespaces, $module_handler, 'Drupal\flag\FlagType\FlagTypePluginInterface', 'Drupal\flag\Annotation\FlagType');

    $this->alterInfo('flag_type_info');
    $this->setCacheBackend($cache_backend, 'flag');
  }

  /**
   * Gets all flag types.
   *
   * @return array
   *   Returns an array of all flag type titles keyed by plugin ID.
   */
  public function getAllFlagTypes() {
    $flag_types = [];

    foreach ($this->getDefinitions() as $plugin_id => $plugin_def) {
      $flag_types[$plugin_id] = $plugin_def['title'];
    }
    asort($flag_types);

    return $flag_types;
  }

}
