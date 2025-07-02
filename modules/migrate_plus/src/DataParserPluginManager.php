<?php

declare(strict_types = 1);

namespace Drupal\migrate_plus;

use Drupal\migrate_plus\Annotation\DataParser;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Provides a plugin manager for data parsers.
 *
 * @see \Drupal\migrate_plus\Annotation\DataParser
 * @see \Drupal\migrate_plus\DataParserPluginBase
 * @see \Drupal\migrate_plus\DataParserPluginInterface
 * @see plugin_api
 */
class DataParserPluginManager extends DefaultPluginManager {

  /**
   * Constructs a new DataParserPluginManager.
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
    parent::__construct('Plugin/migrate_plus/data_parser', $namespaces, $module_handler, DataParserPluginInterface::class, DataParser::class);

    $this->alterInfo('data_parser_info');
    $this->setCacheBackend($cache_backend, 'migrate_plus_plugins_data_parser');
  }

}
