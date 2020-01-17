<?php

namespace Drupal\az_config;

use Drupal\Component\Serialization\Yaml;
use Drupal\Core\Config\ConfigInstaller;
use Drupal\Core\Config\FileStorage;
use Drupal\Core\Config\PreExistingConfigException;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Config\UnmetDependenciesException;

/**
 * Class for customizing the test for pre existing configuration.
 *
 * Decorates the ConfigInstaller with checkConfigurationToInstall() modified.
 * Additionally adds checkAllowedOverrides() to parse yaml-specified overrides.
 */
class QuickstartConfigInstaller extends ConfigInstaller {

  /**
   * {@inheritdoc}
   */
  public function checkConfigurationToInstall($type, $name) {
    if ($this
      ->isSyncing()) {

      // Configuration is assumed to already be checked by the config importer
      // validation events.
      return;
    }
    $config_install_path = $this
      ->getDefaultConfigDirectory($type, $name);
    if (!is_dir($config_install_path)) {
      return;
    }
    $storage = new FileStorage($config_install_path, StorageInterface::DEFAULT_COLLECTION);
    $enabled_extensions = $this
      ->getEnabledExtensions();

    // Add the extension that will be enabled to the list of enabled extensions.
    $enabled_extensions[] = $name;

    // Gets profile storages to search for overrides if necessary.
    $profile_storages = $this
      ->getProfileStorages($name);

    // Check the dependencies of configuration provided by the module.
    list($invalid_default_config, $missing_dependencies) = $this
      ->findDefaultConfigWithUnmetDependencies($storage, $enabled_extensions, $profile_storages);
    if (!empty($invalid_default_config)) {
      throw UnmetDependenciesException::create($name, array_unique($missing_dependencies, SORT_REGULAR));
    }

    // Install profiles can not have config clashes. Configuration that
    // has the same name as a module's configuration will be used instead.
    if ($name != $this
      ->drupalGetProfile()) {

      // Throw an exception if the module being installed contains configuration
      // that already exists. Additionally, can not continue installing more
      // modules because those may depend on the current module being installed.
      $existing_configuration = $this
        ->findPreExistingConfiguration($storage);

      // Allow a module to explicitly specify its configuration overrides.
      $existing_configuration = $this
        ->checkAllowedOverrides($existing_configuration, $type, $name);

      // Any overrides that are not explictly stated are errors.
      if (!empty($existing_configuration)) {
        throw PreExistingConfigException::create($name, $existing_configuration);
      }
    }
  }

  /**
   * Check to allow modules to explicitly specify configuration overrides.
   *
   * This allows the QuickstartConfigInstaller to give modules an opportunity
   * to explicitly indicate they are overwriting configuration that already
   * exists. This is done by modifying the preExistingConfiguration record
   * before the array is examined for the potential need to throw a
   * PreExistingConfigException.
   *
   * @param array $configuration
   *   The configuration collection.
   * @param string $type
   *   Extension type, eg. theme, module.
   * @param string $name
   *   Name of the extension.
   *
   * @return array
   *   Modified configuration collection.
   */
  protected function checkAllowedOverrides(array $configuration, string $type, string $name) {

    $modified_configuration = [];
    $allowed_overrides = [];
    $app_root = \Drupal::root();
    $filename = $app_root . '/' . drupal_get_path($type, $name) . '/' . $name . '.az_config_overrides.yml';

    if (file_exists($filename)) {
      // If an override file exists, parse it for overrides.
      $yaml = Yaml::decode(file_get_contents($filename));
      if (!empty($yaml['overrides'])) {
        foreach ($yaml['overrides'] as $override) {
          $allowed_overrides[] = $override;
        }
      }
    }

    foreach ($configuration as $collection => $config_names) {
      // Strip entries in the collection that are allowed overrides.
      $config_names = array_diff($config_names, $allowed_overrides);
      if (!empty($config_names)) {
        $modified_configuration[$collection] = $config_names;
      }
    }

    return $modified_configuration;
  }

}
