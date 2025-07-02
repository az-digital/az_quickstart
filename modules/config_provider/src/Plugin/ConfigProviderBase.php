<?php

namespace Drupal\config_provider\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Component\Utility\Crypt;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ConfigManagerInterface;
use Drupal\Core\Config\ExtensionInstallStorage;
use Drupal\Core\Config\FileStorage;
use Drupal\Core\Config\InstallStorage;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Extension\ExtensionPathResolver;

/**
 * Base class for Configuration provider plugins.
 */
abstract class ConfigProviderBase extends PluginBase implements ConfigProviderInterface {

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The active configuration storages, keyed by collection.
   *
   * @var \Drupal\Core\Config\StorageInterface[]
   */
  protected $activeStorages;

  /**
   * The configuration manager.
   *
   * @var \Drupal\Core\Config\ConfigManagerInterface
   */
  protected $configManager;

  /**
   * The name of the currently active installation profile.
   *
   * @var string
   */
  protected $installProfile;

  /**
   * The provider configuration storage.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $providerStorage;

  /**
   * The extension path resolver.
   *
   * @var \Drupal\Core\Extension\ExtensionPathResolver
   */
  protected $extensionPathResolver;

  /**
   * {@inheritdoc}
   */
  public function providesFullConfig() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getDirectory() {
    // @phpstan-ignore classConstant.notFound
    return static::ID;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfigFactory(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function setActiveStorages(StorageInterface $active_storage) {
    $this->activeStorages[$active_storage->getCollectionName()] = $active_storage;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfigManager(ConfigManagerInterface $config_manager) {
    $this->configManager = $config_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function setInstallProfile($install_profile) {
    $this->installProfile = $install_profile;
  }

  /**
   * {@inheritdoc}
   */
  public function setProviderStorage(StorageInterface $provider_storage) {
    $this->providerStorage = $provider_storage;
  }

  /**
   * {@inheritdoc}
   */
  public function setExtensionPathResolver(ExtensionPathResolver $extension_path_resolver) {
    $this->extensionPathResolver = $extension_path_resolver;
  }

  /**
   * Gets the storage for a designated configuration provider.
   *
   * @param string $directory
   *   The configuration directory (for example, config/install).
   * @param string $collection
   *   (optional) The configuration collection. Defaults to the default
   *   collection.
   *
   * @return \Drupal\Core\Config\StorageInterface
   *   The configuration storage that provides the default configuration.
   */
  protected function getExtensionInstallStorage($directory, $collection = StorageInterface::DEFAULT_COLLECTION) {
    return new ExtensionInstallStorage($this->getActiveStorages($collection), $directory, $collection, TRUE, $this->installProfile);
  }

  /**
   * Gets the configuration storage that provides the active configuration.
   *
   * @param string $collection
   *   (optional) The configuration collection. Defaults to the default
   *   collection.
   *
   * @return \Drupal\Core\Config\StorageInterface
   *   The configuration storage that provides the default configuration.
   */
  protected function getActiveStorages($collection = StorageInterface::DEFAULT_COLLECTION) {
    if (!isset($this->activeStorages[$collection])) {
      $this->activeStorages[$collection] = reset($this->activeStorages)->createCollection($collection);
    }
    return $this->activeStorages[$collection];
  }

  /**
   * Gets the profile storage to use to check for profile overrides.
   *
   * The install profile can override module configuration during a module
   * install. Both the install and optional directories are checked for matching
   * configuration. This allows profiles to override default configuration for
   * modules they do not depend on.
   *
   * This is based on the ConfigInstaller::getProfileStorages(). Contrary to
   * the core method, here we don't use an argument for the extension being
   * installed, since our usage isn't in the context of extension installation.
   *
   * @return \Drupal\Core\Config\StorageInterface[]|null
   *   Storages to access configuration from the installation profile.
   */
  protected function getProfileStorages() {
    $profile = $this->installProfile;
    $profile_storages = [];
    if ($profile) {
      $profile_path = $this->extensionPathResolver->getPath('module', $profile);
      foreach ([InstallStorage::CONFIG_INSTALL_DIRECTORY, InstallStorage::CONFIG_OPTIONAL_DIRECTORY] as $directory) {
        if (is_dir($profile_path . '/' . $directory)) {
          $profile_storages[] = new FileStorage($profile_path . '/' . $directory, StorageInterface::DEFAULT_COLLECTION);
        }
      }
    }
    return $profile_storages;
  }

  /**
   * Gets the list of enabled extensions including both modules and themes.
   *
   * @return array
   *   A list of enabled extensions which includes both modules and themes.
   */
  protected function getEnabledExtensions() {
    // Read enabled extensions directly from configuration to avoid circular
    // dependencies on ModuleHandler and ThemeHandler.
    $extension_config = $this->configFactory->get('core.extension');
    $enabled_extensions = (array) $extension_config->get('module');
    $enabled_extensions += (array) $extension_config->get('theme');
    // Core can provide configuration.
    $enabled_extensions['core'] = 'core';
    return array_keys($enabled_extensions);
  }

  /**
   * Validates an array of config data that contains dependency information.
   *
   * @param string $config_name
   *   The name of the configuration object that is being validated.
   * @param array $data
   *   Configuration data.
   * @param array $enabled_extensions
   *   A list of all the currently enabled modules and themes.
   * @param array $all_config
   *   A list of all the active configuration names.
   *
   * @return bool
   *   TRUE if the dependencies are met, FALSE if not.
   */
  protected function validateDependencies($config_name, array $data, array $enabled_extensions, array $all_config) {
    if (isset($data['dependencies'])) {
      $all_dependencies = $data['dependencies'];

      // Ensure enforced dependencies are included.
      if (isset($all_dependencies['enforced'])) {
        $all_dependencies = array_merge($all_dependencies, $data['dependencies']['enforced']);
        unset($all_dependencies['enforced']);
      }
      // Ensure the configuration entity type provider is in the list of
      // dependencies.
      [$provider] = explode('.', $config_name, 2);
      if (!isset($all_dependencies['module'])) {
        $all_dependencies['module'][] = $provider;
      }
      elseif (!in_array($provider, $all_dependencies['module'])) {
        $all_dependencies['module'][] = $provider;
      }

      foreach ($all_dependencies as $type => $dependencies) {
        $list_to_check = [];
        switch ($type) {
          case 'module':
          case 'theme':
            $list_to_check = $enabled_extensions;
            break;

          case 'config':
            $list_to_check = $all_config;
            break;
        }
        if (!empty($list_to_check)) {
          $missing = array_diff($dependencies, $list_to_check);
          if (!empty($missing)) {
            return FALSE;
          }
        }
      }
    }
    return TRUE;
  }

  /**
   * Returns a list of all configuration items or those of extensions.
   *
   * @param \Drupal\Core\Config\StorageInterface $storage
   *   A configuration storage.
   * @param \Drupal\Core\Extension\Extension[] $extensions
   *   An associative array of Extension objects, keyed by extension name.
   *
   * @return array
   *   An array containing configuration object names.
   */
  protected function listConfig(StorageInterface $storage, array $extensions = []) {
    if (!empty($extensions)) {
      $config_names = [];
      /** @var \Drupal\Core\Extension\Extension $extension */
      foreach ($extensions as $name => $extension) {
        $config_names = array_merge($config_names, array_keys($storage->getComponentNames([
          $name => $extension,
        ])));
      }
    }
    else {
      $config_names = $storage->listAll();
    }

    return $config_names;
  }

  /**
   * Wrapper for drupal_get_path().
   *
   * @param string $type
   *   The type of the item; one of 'core', 'profile', 'module', 'theme', or
   *   'theme_engine'.
   * @param string $name
   *   The name of the item for which the path is requested. Ignored for
   *   $type 'core'.
   *
   * @return string
   *   The path to the requested item or an empty string if the item is not
   *   found.
   *
   * @deprecated in config_provider:3.0.0-alpha2 and is removed from config_provider:3.1.0.
   *   ConfigProviderBase::extensionPathResolver::getPath() should be used
   *   instead.
   * @see https://www.drupal.org/project/config_provider/issues/3500568
   */
  protected function drupalGetPath(string $type, string $name) {
    @trigger_error(__METHOD__ . '() is deprecated in config_provider:3.0.0-alpha2 and is removed from config_provider:3.1.0. ConfigProviderBase::extensionPathResolver::getPath() should be used instead. See https://www.drupal.org/project/config_provider/issues/3500568', E_USER_DEPRECATED);
    return $this->extensionPathResolver->getPath($type, $name);
  }

  /**
   * Gets the install profile from settings.
   *
   * @return string|null
   *   The name of the installation profile or NULL if no installation profile
   *   is currently active. This is the case for example during the first steps
   *   of the installer or during unit tests.
   *
   * @deprecated in config_provider:3.0.0-alpha2 and is removed from config_provider:3.1.0.
   *   ConfigProviderBase::installProfile should be used instead.
   * @see https://www.drupal.org/project/config_provider/issues/3500568
   */
  protected function drupalGetProfile() {
    @trigger_error(__METHOD__ . '() is deprecated in config_provider:3.0.0-alpha2 and is removed from config_provider:3.1.0. ConfigProviderBase::installProfile should be used instead. See https://www.drupal.org/project/config_provider/issues/3500568', E_USER_DEPRECATED);
    return $this->installProfile;
  }

  /**
   * Adds default_config_hash for proper localization of the config objects.
   *
   * Use this method only on unchanged config from config/install or
   * config/optional folders.
   *
   * @param array $data
   *   Config to install read directly from config/install or config/optional.
   *
   * @return array
   *   Config with default_config_hash property.
   */
  public function addDefaultConfigHash(array $data) {
    if (empty($data['_core']['default_config_hash'])) {
      $data['_core']['default_config_hash'] = Crypt::hashBase64(serialize($data));
    }
    return $data;
  }

}
