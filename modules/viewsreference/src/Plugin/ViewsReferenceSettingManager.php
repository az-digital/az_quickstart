<?php

namespace Drupal\viewsreference\Plugin;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Provides the views reference setting plugin manager.
 */
class ViewsReferenceSettingManager extends DefaultPluginManager {

  /**
   * Constructor for ViewsReferenceSettingManager objects.
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
    parent::__construct('Plugin/ViewsReferenceSetting', $namespaces, $module_handler, 'Drupal\viewsreference\Plugin\ViewsReferenceSettingInterface', 'Drupal\viewsreference\Annotation\ViewsReferenceSetting');

    $this->alterInfo('viewsreference_viewsreference_setting_info');
    $this->setCacheBackend($cache_backend, 'viewsreference_viewsreference_setting_plugins');
  }

}
