<?php

namespace Drupal\az_core;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\config_provider\Plugin\ConfigCollector;
use Drupal\az_core\Plugin\ConfigProvider\QuickstartConfigProvider;
use Drupal\config_sync\ConfigSyncSnapshotter;
use Drupal\config_sync\ConfigSyncSnapshotterInterface;
use Drupal\config_update\ConfigListByProviderInterface;

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
   * Drupal\config_sync\ConfigSyncSnapshotter definition.
   *
   * @var \Drupal\config_sync\ConfigSyncSnapshotter
   */
  protected $configSyncSnapshotter;

  /**
   * Drupal\config_update\ConfigListByProviderInterface definition.
   *
   * @var \Drupal\config_update\ConfigListByProviderInterface
   */
  protected $configUpdateLister;

  /**
   * Drupal\Core\Extension\ModuleExtensionList definition.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected $extensionListModule;

  /**
   * Constructs a new AZConfigOverride object.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ModuleExtensionList $extension_list_module, ConfigCollector $config_collector, ConfigSyncSnapshotter $config_sync_snapshotter, ConfigListByProviderInterface $config_update_lister) {
    $this->configFactory = $config_factory;
    $this->extensionListModule = $extension_list_module;
    $this->configCollector = $config_collector;
    $this->configSyncSnapshotter = $config_sync_snapshotter;
    $this->configUpdateLister = $config_update_lister;
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

    // Previously enabled extensions.
    $old_extensions = array_diff_key($module_list, $module_keys);

    // Ask the override provider for direct overrides available.
    foreach ($providers as $provider) {
      // Only query config for the Quickstart provider.
      if ($provider instanceof QuickstartConfigProvider) {
        $overrides = $provider->getOverrideConfig($extensions, $old_extensions);
        $permissions = $provider->findProfilePermissions($extensions);
        $overrides = $permissions + $overrides;

        // Edit active configuration for each explicit override.
        foreach ($overrides as $name => $data) {
          $config = $this->configFactory->getEditable($name);
          $config->setData($data);
          $config->Save();

          // Determine the extension owner of the configuration (array tuple).
          $provided_by = $this->configUpdateLister->getConfigProvider($name);
          if (!empty($provided_by[1])) {
            $type = $provided_by[0];
            $owner = $provided_by[1];

            // Update the config_snapshot of the module that owns the config.
            $this->configSyncSnapshotter->refreshExtensionSnapshot($type, [$owner],
              ConfigSyncSnapshotterInterface::SNAPSHOT_MODE_IMPORT);
          }
        }
      }
    }
  }

}
