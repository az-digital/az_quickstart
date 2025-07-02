<?php

namespace Drupal\config_provider\Plugin\ConfigProvider;

use Drupal\config_provider\Plugin\ConfigProviderBase;
use Drupal\Core\Config\Entity\ConfigDependencyManager;
use Drupal\Core\Config\FileStorage;
use Drupal\Core\Config\InstallStorage;
use Drupal\Core\Config\StorageInterface;

/**
 * Class for providing configuration from an install directory.
 *
 * @ConfigProvider(
 *   id = \Drupal\config_provider\Plugin\ConfigProvider\ConfigProviderOptional::ID,
 *   weight = 10,
 *   label = @Translation("Optional"),
 *   description = @Translation("Configuration to be installed when an extension is installed."),
 * )
 */
class ConfigProviderOptional extends ConfigProviderBase {

  /**
   * The configuration provider ID.
   */
  const ID = InstallStorage::CONFIG_OPTIONAL_DIRECTORY;

  /**
   * {@inheritdoc}
   */
  public function addConfigToCreate(array &$config_to_create, StorageInterface $storage, $collection, $prefix = '', array $profile_storages = []) {
    // Optional configuration is installed subsequently, so we can't add it
    // here.
  }

  /**
   * {@inheritdoc}
   */
  public function addInstallableConfig(array $extensions = []) {
    // This method is adapted from ConfigInstaller::installOptionalConfig().
    // Non-default configuration collections are not supported for
    // config/optional.
    $storage = $this->getExtensionInstallStorage(static::ID);

    if (!empty($profile)) {
      // Creates a profile storage to search for overrides.
      $profile_install_path = $this->extensionPathResolver->getPath('module', $this->installProfile) . '/' . InstallStorage::CONFIG_OPTIONAL_DIRECTORY;
      $profile_storage = new FileStorage($profile_install_path, StorageInterface::DEFAULT_COLLECTION);
    }
    else {
      // Profile has not been set yet. For example during the first steps of the
      // installer or during unit tests.
      $profile_storage = NULL;
    }

    $enabled_extensions = $this->getEnabledExtensions();
    $existing_config = $this->getActiveStorages()->listAll();

    $list = $this->listConfig($storage, $extensions);

    $list = array_filter($list, function ($config_name) {
      // Only list configuration that:
      // - is a configuration entity (this also excludes config that has an
      //   implicit dependency on modules that are not yet installed)
      // Contrary to the equivalent code in core, we don't filter out
      // items that exist in the active configuration storage, since we're
      // identifying configuration that meets the criteria for installation,
      // regardless of whether or not it has been installed.
      return (bool) $this->configManager->getEntityTypeIdByName($config_name);
    });

    $all_config = array_merge($existing_config, $list);
    // Merge in the configuration provided already by previous config
    // providers.
    $all_config = array_merge($all_config, $this->providerStorage->listAll());
    $all_config = array_combine($all_config, $all_config);
    $config_to_create = $storage->readMultiple($list);
    // Check to see if the corresponding override storage has any overrides or
    // new configuration that can be installed.
    if ($profile_storage) {
      $config_to_create = $profile_storage->readMultiple($list) + $config_to_create;
    }
    // Sort $config_to_create in the order of the least dependent first.
    $dependency_manager = new ConfigDependencyManager();
    $dependency_manager->setData($config_to_create);
    $config_to_create = array_merge(array_flip($dependency_manager->sortAll()), $config_to_create);

    foreach ($config_to_create as $config_name => $data) {
      // Remove configuration where its dependencies cannot be met.
      $remove = !$this->validateDependencies($config_name, $data, $enabled_extensions, $all_config);
      if ($remove) {
        // Remove from the list of configuration to create.
        unset($config_to_create[$config_name]);
        // Remove from the list of all configuration. This ensures that any
        // configuration that depends on this configuration is also removed.
        unset($all_config[$config_name]);
      }
      else {
        $data = $this->addDefaultConfigHash($data);
        $this->providerStorage->write($config_name, $data);
      }
    }

  }

}
