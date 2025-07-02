<?php

namespace Drupal\config_provider_foo_provider_test\Plugin\ConfigProvider;

use Drupal\config_provider\Plugin\ConfigProviderBase;
use Drupal\Core\Config\StorageInterface;

/**
 * Class for providing configuration from an 'foo' directory.
 *
 * @ConfigProvider(
 *   id = "config/foo",
 *   weight = -10,
 *   label = @Translation("Foo"),
 *   description = @Translation("Configuration to be installed when an extension is installed."),
 * )
 */
class Foo extends ConfigProviderBase {

  /**
   * The configuration provider ID.
   */
  const ID = 'config/foo';

  /**
   * {@inheritdoc}
   */
  public function addConfigToCreate(array &$config_to_create, StorageInterface $storage, $collection, $prefix = '', array $profile_storages = []) {
    $foo_config_to_create = $this->getFooConfig();
    $config_to_create = array_merge($foo_config_to_create, $config_to_create);
  }

  /**
   * {@inheritdoc}
   */
  public function addInstallableConfig(array $extensions = []) {
    foreach ($this->getFooConfig($extensions) as $name => $value) {
      $this->providerStorage->write($name, $value);
    }
  }

  /**
   * Helper to fetch config items from config/foo folders.
   *
   * @param array $extensions
   *   Extensions to consider when listing available config. Empty array
   *   for all extensions.
   *
   * @return array
   *   Associative array with config items in config/foo folder(s), index by
   *   config file names.
   */
  private function getFooConfig(array $extensions = []): array {
    $storage = $this->getExtensionInstallStorage(static::ID);

    $config_names = $this->listConfig($storage, $extensions);
    return $storage->readMultiple($config_names);
  }

}
