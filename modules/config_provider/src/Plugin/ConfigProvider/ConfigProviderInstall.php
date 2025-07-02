<?php

namespace Drupal\config_provider\Plugin\ConfigProvider;

use Drupal\config_provider\Plugin\ConfigProviderBase;
use Drupal\Core\Config\InstallStorage;
use Drupal\Core\Config\StorageInterface;

/**
 * Class for providing configuration from an install directory.
 *
 * @ConfigProvider(
 *   id = \Drupal\config_provider\Plugin\ConfigProvider\ConfigProviderInstall::ID,
 *   weight = -10,
 *   label = @Translation("Install"),
 *   description = @Translation("Configuration to be installed when an extension is installed."),
 * )
 */
class ConfigProviderInstall extends ConfigProviderBase {

  /**
   * The configuration provider ID.
   */
  const ID = InstallStorage::CONFIG_INSTALL_DIRECTORY;

  /**
   * {@inheritdoc}
   */
  public function addConfigToCreate(array &$config_to_create, StorageInterface $storage, $collection, $prefix = '', array $profile_storages = []) {
    // The caller will already have loaded config for install.
  }

  /**
   * {@inheritdoc}
   */
  public function addInstallableConfig(array $extensions = []) {
    $storage = $this->getExtensionInstallStorage(static::ID);

    // Gather information about all the supported collections.
    $collection_info = $this->configManager
      ->getConfigCollectionInfo();
    foreach ($collection_info
      ->getCollectionNames() as $collection) {
      if ($storage->getCollectionName() !== $collection) {
        $storage = $storage->createCollection($collection);
      }
      $config_names = $this->listConfig($storage, $extensions);

      $data = $storage->readMultiple($config_names);

      // Check to see if the corresponding override storage has any overrides.
      foreach ($this->getProfileStorages() as $profile_storage) {
        if ($profile_storage->getCollectionName() !== $collection) {
          $profile_storage = $profile_storage->createCollection($collection);
        }
        $data = $profile_storage->readMultiple(array_keys($data)) + $data;
      }

      foreach ($data as $name => $value) {
        if ($this->providerStorage->getCollectionName() !== $collection) {
          $this->providerStorage = $this->providerStorage->createCollection($collection);
        }
        $value = $this->addDefaultConfigHash($value);
        $this->providerStorage->write($name, $value);
      }
    }

  }

}
