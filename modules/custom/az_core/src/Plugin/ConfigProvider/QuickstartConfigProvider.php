<?php

namespace Drupal\az_core\Plugin\ConfigProvider;

use Drupal\config_provider\Plugin\ConfigProviderBase;
use Drupal\Core\Config\InstallStorage;
use Drupal\Core\Config\StorageInterface;

/**
 * Class for providing configuration from a quickstart default directory.
 *
 * @ConfigProvider(
 *   id = \Drupal\az_core\Plugin\ConfigProvider\QuickstartConfigProvider::ID,
 *   weight = 20,
 *   label = @Translation("Quickstart Override"),
 *   description = @Translation("Configuration from Quickstart Overrides."),
 * )
 */
class QuickstartConfigProvider extends ConfigProviderBase {

  /**
   * The configuration provider ID.
   */
  const ID = 'config/quickstart';

  /**
   * {@inheritdoc}
   */
  public function addConfigToCreate(array &$config_to_create, StorageInterface $storage, $collection, $prefix = '', array $profile_storages = []) {
    // The caller will aready have loaded config for install.
  }

  /**
   * {@inheritdoc}
   */
  public function addInstallableConfig(array $extensions = []) {

    // Get the install configuraiton present for the specified modules.
    $storage = $this->getExtensionInstallStorage(InstallStorage::CONFIG_INSTALL_DIRECTORY);
    $config_names = $this->listConfig($storage, $extensions);
    $profile_storages = $this->getProfileStorages();
    $data = $storage->readMultiple($config_names);

    // Check for quickstart defaults for the same configuration names.
    $override_storage = $this->getExtensionInstallStorage(static::ID);
    $override_data = $override_storage->readMultiple($config_names);

    // Merge the two configs, with preference for the quickstart defaults.
    $data = array_merge($data, $override_data);

    // Check to see if the corresponding profile storage has any overrides.
    foreach ($profile_storages as $profile_storage) {
      $data = $profile_storage->readMultiple(array_keys($data)) + $data;
    }

    // Add the configuration changes.
    foreach ($data as $name => $value) {
      $value = $this->addDefaultConfigHash($value);
      $this->providerStorage->write($name, $value);
    }

  }

}
