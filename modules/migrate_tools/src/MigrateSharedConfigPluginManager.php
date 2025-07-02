<?php

declare(strict_types = 1);

namespace Drupal\migrate_tools;

use Drupal\Component\Plugin\Discovery\DiscoveryInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Plugin\Discovery\YamlDiscovery;
use Drupal\Core\Plugin\Factory\ContainerFactory;
use Drupal\migrate_tools\Discovery\YamlDiscoveryDecorator;

/**
 * Defines a plugin manager to deal with migrate_shared_configuration.
 *
 * Modules can define migrate_shared_configuration in a
 * MODULE_NAME.migrate_shared_configuration.yml file contained in the module's
 * base directory. The migrate_shared_configuration has the following structure:
 *
 * @code
 *   MACHINE_NAME:
 *     source:
 *       key: drupal7
 *   MACHINE_NAME_2:
 *     source:
 *       batch_size: 1000
 * @endcode
 *
 * Where everything besides MACHINE_NAME is the shared configuration.
 *
 * @see \Drupal\migrate_tools\MigrateSharedConfigDefault
 * @see \Drupal\migrate_tools\MigrateSharedConfigInterface
 * @see plugin_api
 */
final class MigrateSharedConfigPluginManager extends DefaultPluginManager {

  /**
   * {@inheritdoc}
   */
  protected $defaults = [
    // The migrate_shared_configuration id. Set by the plugin system based on
    // the top-level YAML key.
    'id' => '',
    // Default plugin class.
    'class' => MigrateSharedConfigDefault::class,
  ];

  public function __construct(ModuleHandlerInterface $module_handler, CacheBackendInterface $cache_backend) {
    $this->factory = new ContainerFactory($this);
    $this->moduleHandler = $module_handler;
    $this->alterInfo('migrate_shared_configuration_info');
    $this->setCacheBackend($cache_backend, 'migrate_shared_configuration_plugins');
  }

  /**
   * {@inheritdoc}
   */
  protected function getDiscovery(): DiscoveryInterface {
    if (!isset($this->discovery)) {
      // @todo Remove this in 7.0.0.
      $old_discovery = new YamlDiscovery('migrate_shared_configuration', $this->moduleHandler->getModuleDirectories());

      $directories = array_map(function ($directory) {
        return [$directory . '/migrate_shared_configuration'];
      }, $this->moduleHandler->getModuleDirectories());
      $this->discovery = new YamlDiscoveryDecorator($old_discovery, $directories, 'migrate_shared_configuration');
    }
    return $this->discovery;
  }

}
