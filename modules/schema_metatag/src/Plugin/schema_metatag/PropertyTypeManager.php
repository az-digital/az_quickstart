<?php

namespace Drupal\schema_metatag\Plugin\schema_metatag;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Provides the Property type plugin manager.
 */
class PropertyTypeManager extends DefaultPluginManager {

  /**
   * Constructs a new PropertyTypeManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(
    \Traversable $namespaces,
    CacheBackendInterface $cache_backend,
    ModuleHandlerInterface $module_handler,
  ) {
    parent::__construct(
      'Plugin/schema_metatag/PropertyType',
      $namespaces,
      $module_handler,
      'Drupal\schema_metatag\Plugin\schema_metatag\PropertyTypeInterface',
      'Drupal\schema_metatag\Annotation\SchemaPropertyType');

    $this->alterInfo('schema_metatag_property_type_plugins');
    $this->setCacheBackend($cache_backend, 'schema_metatag_property_type_plugins');
  }

}
