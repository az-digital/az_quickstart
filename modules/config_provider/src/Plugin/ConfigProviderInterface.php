<?php

namespace Drupal\config_provider\Plugin;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ConfigManagerInterface;
use Drupal\Core\Config\StorageInterface;

/**
 * Defines an interface for Configuration provider plugins.
 */
interface ConfigProviderInterface extends PluginInspectionInterface {

  /**
   * Indicates if config items returned by provider are full (not partials).
   *
   * A partial is a subset of a full configuration item and typically would be
   * merged into the item. Example: an array of user permissions to be merged
   * into a user role configuration item.
   *
   * @return bool
   *   TRUE if the configuration returned is full; otherwise, FALSE.
   */
  public function providesFullConfig();

  /**
   * Returns the configuration directory.
   *
   * @return string
   *   The configuration directory for this provider.
   */
  public function getDirectory();

  /**
   * Injects the active configuration storage.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   */
  public function setConfigFactory(ConfigFactoryInterface $config_factory);

  /**
   * Injects the active configuration storage.
   *
   * @param \Drupal\Core\Config\StorageInterface $active_storage
   *   The configuration storage to read configuration from.
   */
  public function setActiveStorages(StorageInterface $active_storage);

  /**
   * Injects the active configuration storage.
   *
   * @param \Drupal\Core\Config\ConfigManagerInterface $config_manager
   *   The configuration manager.
   */
  public function setConfigManager(ConfigManagerInterface $config_manager);

  /**
   * Sets the install profile.
   *
   * @param string $install_profile
   *   The name of the install profile.
   */
  public function setInstallProfile($install_profile);

  /**
   * Adds configuration to be installed.
   *
   * This method is invoked at extension install time. A given configuration
   * provider can add configuration to be installed or alter configuration
   * as provided by a prior extension.
   *
   * In some cases, installation of configuration may be handled separately,
   * meaning that no configuration need be added here.
   *
   * @param array $config_to_create
   *   An array of configuration data to create, keyed by name. Passed by
   *   reference.
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
   * @see \Drupal\config_provider\ConfigProviderConfigInstaller::getConfigToCreate()
   * @see \Drupal\config_provider\Plugin\ConfigProviderInterface\addInstallableConfig()
   */
  public function addConfigToCreate(array &$config_to_create, StorageInterface $storage, $collection, $prefix = '', array $profile_storages = []);

  /**
   * Adds configuration that is available to be installed or updated.
   *
   * Not intended to be called an install time, this method instead facilitates
   * determining what configuration updates are available.
   *
   * Implementing plugins should write configuration as appropriate to the
   * ::providerStorage storage.
   *
   * @param \Drupal\Core\Extension\Extension[] $extensions
   *   (Optional) An associative array of Extension objects, keyed by extension
   *   name. If provided, data loaded will be limited to these extensions.
   *
   * @see \Drupal\config_provider\Plugin\ConfigProviderInterface\addConfigToCreate()
   */
  public function addInstallableConfig(array $extensions = []);

}
