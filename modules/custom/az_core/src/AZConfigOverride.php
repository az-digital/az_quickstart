<?php

namespace Drupal\az_core;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\config_provider\Plugin\ConfigCollector;
use Drupal\az_core\Plugin\ConfigProvider\QuickstartConfigProvider;

/**
 * Class AZConfigOverride.
 *
 * This class is responsible for loading any override configuration into the
 * active site configuration after a module is enabled. These same updates
 * are presented as an update to the config_distro pipeline, and these
 * configuration overrides are only applied immediately as a convenience
 * to avoid needing to import the changes whenever an override module is
 * enabled, e.g. az_cas.
 */
class AZConfigOverride {

  /**
   * Drupal\config_provider\Plugin\ConfigCollector definition.
   *
   * @var \Drupal\config_provider\Plugin\ConfigCollector
   */
  protected $configCollector;

  /**
   * Drupal\Core\Config\ConfigFactoryInterface definition.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Drupal\Core\Extension\ModuleExtensionList definition.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected $extensionListModule;

  /**
   * Constructs a new AZConfigOverride object.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ModuleExtensionList $extension_list_module, ConfigCollector $config_collector) {
    $this->configFactory = $config_factory;
    $this->extensionListModule = $extension_list_module;
    $this->configCollector = $config_collector;
  }

  /**
   * Imports configuration overrides at module enable time.
   *
   * This makes the assumption that explicit config overrides are simple
   * configuration defaults that should not have runtime or database effects.
   *
   * @param array $modules
   *   Array of just-enabled modules by machine name.
   */
  public function importOverrides(array $modules) {

    // Get the list of Config Providers.
    $providers = $this->configCollector->getConfigProviders();

    // Get the full extension list and filter by the modules just enabled.
    $module_list = $this->extensionListModule->getList();
    $module_keys = array_flip($modules);
    $extensions = array_intersect_key($module_list, $module_keys);

    // Ask the override provider for direct overrides available.
    foreach ($providers as $provider) {
      // Only query config for the Quickstart provider.
      if ($provider instanceof QuickstartConfigProvider) {
        $overrides = $provider->getOverrideConfig($extensions);

        // Edit active configuration for each explicit override.
        foreach ($overrides as $name => $data) {
          $config = $this->configFactory->getEditable($name);
          $config->setData($data);
          $config->Save();
        }
      }
    }
  }

}
