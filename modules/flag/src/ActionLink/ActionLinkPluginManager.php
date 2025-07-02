<?php

namespace Drupal\flag\ActionLink;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Plugin manager for link types.
 *
 * @see Drupal\flag\ActionLink\ActionLinkTypeBase
 */
class ActionLinkPluginManager extends DefaultPluginManager {

  /**
   * {@inheritdoc}
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/ActionLink', $namespaces, $module_handler, 'Drupal\flag\ActionLink\ActionLinkTypePluginInterface', 'Drupal\flag\Annotation\ActionLinkType');
    $this->alterInfo('flag_link_type_info');
    $this->setCacheBackend($cache_backend, 'flag_link_type_plugins');
  }

  /**
   * Get an array of all link type labels keyed by plugin ID.
   *
   * @return array
   *   An array of all link type plugins.
   */
  public function getAllLinkTypes() {
    $link_types = [];
    foreach ($this->getDefinitions() as $plugin_id => $plugin_def) {
      $link_types[$plugin_id] = $plugin_def['label'];
    }

    return $link_types;
  }

}
