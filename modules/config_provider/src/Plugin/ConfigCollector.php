<?php

namespace Drupal\config_provider\Plugin;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ConfigManagerInterface;
use Drupal\Core\Extension\ExtensionPathResolver;
use Drupal\Core\Config\StorageInterface;

/**
 * Class for invoking configuration providers..
 */
class ConfigCollector implements ConfigCollectorInterface {

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The active configuration storage.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $activeStorage;

  /**
   * The configuration manager.
   *
   * @var \Drupal\Core\Config\ConfigManagerInterface
   */
  protected $configManager;

  /**
   * The provider configuration storage.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $providerStorage;

  /**
   * The configuration provider manager.
   *
   * @var \Drupal\config_provider\Plugin\ConfigProviderManager
   */
  protected $configProviderManager;

  /**
   * The name of the currently active installation profile.
   *
   * @var string
   */
  protected $installProfile;

  /**
   * The extension path resolver.
   *
   * @var \Drupal\Core\Extension\ExtensionPathResolver|null
   */
  protected $extensionPathResolver;

  /**
   * The configuration provider plugin instances.
   *
   * @var \Drupal\config_provider\Plugin\ConfigProvider
   */
  protected $configProviders;

  /**
   * Constructor for ConfigCollector objects.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\Core\Config\StorageInterface $active_storage
   *   The active configuration storage.
   * @param \Drupal\Core\Config\ConfigManagerInterface $config_manager
   *   The configuration manager.
   * @param \Drupal\Core\Config\StorageInterface $provider_storage
   *   The provider configuration storage.
   * @param \Drupal\config_provider\Plugin\ConfigProviderManager $config_provider_manager
   *   The configuration provider manager.
   * @param string $install_profile
   *   The current installation profile.
   * @param \Drupal\Core\Extension\ExtensionPathResolver|null $extension_path_resolver
   *   The extension path resolver.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    StorageInterface $active_storage,
    ConfigManagerInterface $config_manager,
    StorageInterface $provider_storage,
    ConfigProviderManager $config_provider_manager,
    $install_profile,
    ?ExtensionPathResolver $extension_path_resolver = NULL,
  ) {
    $this->configFactory = $config_factory;
    $this->activeStorage = $active_storage;
    $this->configManager = $config_manager;
    $this->providerStorage = $provider_storage;
    $this->configProviderManager = $config_provider_manager;
    $this->installProfile = $install_profile;
    if (!$extension_path_resolver instanceof ExtensionPathResolver) {
      @trigger_error('Calling ConfigCollector::__construct() without the $extension_path_resolver argument is deprecated in config_provider:3.0.0-alpha2 and it will be required in config_provider:3.1.0. See https://www.drupal.org/project/config_provider/issues/3511302', E_USER_DEPRECATED);
    }
    $this->extensionPathResolver = $extension_path_resolver;
    $this->configProviders = [];
  }

  /**
   * Returns the extension path resolver.
   *
   * @return \Drupal\Core\Extension\ExtensionPathResolver
   *   The extension path resolver.
   */
  protected function extensionPathResolver(): ExtensionPathResolver {
    if ($this->extensionPathResolver instanceof ExtensionPathResolver) {
      return $this->extensionPathResolver;
    }
    // @phpstan-ignore-next-line
    return \Drupal::service('extension.path.resolver');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfigProviders() {
    if (empty($this->configProviders)) {
      $definitions = $this->configProviderManager->getDefinitions();
      foreach (array_keys($definitions) as $id) {
        $this->initConfigProviderInstance($id);
      }
    }
    return $this->configProviders;
  }

  /**
   * {@inheritdoc}
   */
  public function addInstallableConfig(array $extensions = []) {
    // Start with an empty storage.
    $this->providerStorage->deleteAll();
    foreach ($this->providerStorage->getAllCollectionNames() as $collection) {
      $provider_collection = $this->providerStorage->createCollection($collection);
      $provider_collection->deleteAll();
    }

    /** @var \Drupal\config_provider\Plugin\ConfigProviderInterface[] $providers */
    $providers = $this->getConfigProviders();

    foreach ($providers as $provider) {
      $provider->addInstallableConfig($extensions);
    }
  }

  /**
   * Initializes an instance of the specified configuration provider.
   *
   * @param string $id
   *   The string identifier of the configuration provider.
   */
  protected function initConfigProviderInstance($id) {
    if (!isset($this->configProviders[$id])) {
      $instance = $this->configProviderManager->createInstance($id, []);
      $instance->setConfigFactory($this->configFactory);
      $instance->setActiveStorages($this->activeStorage);
      $instance->setConfigManager($this->configManager);
      $instance->setProviderStorage($this->providerStorage);
      $instance->setInstallProfile($this->installProfile);
      $instance->setExtensionPathResolver($this->extensionPathResolver());
      $this->configProviders[$id] = $instance;
    }
  }

}
