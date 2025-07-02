<?php

namespace Drupal\better_exposed_filters\Plugin;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Symfony\Component\DependencyInjection\Container;

/**
 * Provides the Better exposed filters widget plugin manager.
 */
class BetterExposedFiltersWidgetManager extends DefaultPluginManager {

  /**
   * The widget type.
   *
   * @var string
   */
  protected string $type;

  /**
   * Constructs a new BetterExposedFiltersFilterWidgetManager object.
   *
   * @param string $type
   *   The plugin type, for example filter, pager or sort.
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct($type, \Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    $plugin_interface = 'Drupal\better_exposed_filters\Plugin\BetterExposedFiltersWidgetInterface';
    $plugin_definition_annotation_name = 'Drupal\better_exposed_filters\Annotation\BetterExposedFilters' . Container::camelize($type) . 'Widget';
    parent::__construct("Plugin/better_exposed_filters/$type", $namespaces, $module_handler, $plugin_interface, $plugin_definition_annotation_name);

    $this->type = $type;
    $this->alterInfo('better_exposed_filters_better_exposed_filters_' . $type . '_widget_info');
    $this->setCacheBackend($cache_backend, 'better_exposed_filters:' . $type . '_widget');
  }

}
