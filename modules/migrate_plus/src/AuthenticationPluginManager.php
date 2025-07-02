<?php

declare(strict_types = 1);

namespace Drupal\migrate_plus;

use Drupal\migrate_plus\Annotation\Authentication;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Provides a plugin manager for authentication handlers.
 *
 * @see \Drupal\migrate_plus\Annotation\DataFetcher
 * @see \Drupal\migrate_plus\DataFetcherPluginBase
 * @see \Drupal\migrate_plus\DataFetcherPluginInterface
 * @see plugin_api
 */
class AuthenticationPluginManager extends DefaultPluginManager {

  /**
   * Constructs a new AuthenticationPluginManager.
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
    parent::__construct('Plugin/migrate_plus/authentication', $namespaces, $module_handler, AuthenticationPluginInterface::class, Authentication::class);

    $this->alterInfo('authentication_info');
    $this->setCacheBackend($cache_backend, 'migrate_plus_plugins_authentication');
  }

}
