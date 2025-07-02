<?php

namespace Drupal\config_provider;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ConfigInstaller;
use Drupal\Core\Config\ConfigInstallerInterface;
use Drupal\Core\Config\ConfigManagerInterface;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Extension\ExtensionPathResolver;
use Drupal\config_provider\Plugin\ConfigCollectorInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Overrides default ConfigInstaller to include config from config providers.
 */
class ConfigProviderConfigInstaller extends ConfigInstaller implements ConfigInstallerInterface {

  /**
   * The config collector.
   *
   * @var \Drupal\config_provider\Plugin\ConfigCollectorInterface
   */
  protected $configCollector;

  /**
   * Constructs the configuration installer.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\Core\Config\StorageInterface $active_storage
   *   The active configuration storage.
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $typed_config
   *   The typed configuration manager.
   * @param \Drupal\Core\Config\ConfigManagerInterface $config_manager
   *   The configuration manager.
   * @param \Symfony\Contracts\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   * @param string $install_profile
   *   The name of the currently active installation profile.
   * @param \Drupal\Core\Extension\ExtensionPathResolver $extension_path_resolver
   *   The extension path resolver.
   * @param \Drupal\config_provider\Plugin\ConfigCollectorInterface $config_collector
   *   The config collector.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    StorageInterface $active_storage,
    TypedConfigManagerInterface $typed_config,
    ConfigManagerInterface $config_manager,
    EventDispatcherInterface $event_dispatcher,
    $install_profile,
    ExtensionPathResolver $extension_path_resolver,
    ConfigCollectorInterface $config_collector,
  ) {
    $this->configFactory = $config_factory;
    $this->activeStorages[$active_storage->getCollectionName()] = $active_storage;
    $this->typedConfig = $typed_config;
    $this->configManager = $config_manager;
    $this->eventDispatcher = $event_dispatcher;
    $this->installProfile = $install_profile;
    $this->extensionPathResolver = $extension_path_resolver;
    $this->configCollector = $config_collector;
  }

  /**
   * Overrides \Drupal\Core\Config\ConfigInstaller::getConfigToCreate().
   *
   * When extensions are installed, consult all registered config providers.
   *
   * @param \Drupal\Core\Config\StorageInterface $storage
   *   The configuration storage to read configuration from.
   * @param string $collection
   *   The configuration collection to use.
   * @param string $prefix
   *   (optional) Limit to configuration starting with the provided string.
   * @param \Drupal\Core\Config\StorageInterface[] $profile_storages
   *   An array of storage interfaces containing profile configuration to check
   *   for overrides.
   *
   * @return array
   *   An array of configuration data read from the source storage keyed by the
   *   configuration object name.
   */
  protected function getConfigToCreate(StorageInterface $storage, $collection, $prefix = '', array $profile_storages = []) {
    // Determine if we have configuration to create.
    $config_to_create = parent::getConfigToCreate($storage, $collection, $prefix, $profile_storages);

    foreach ($this->configCollector->getConfigProviders() as $config_provider) {
      $config_provider->addConfigToCreate($config_to_create, $storage, $collection, $prefix, $profile_storages);
    }

    return $config_to_create;
  }

}
