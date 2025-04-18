<?php

namespace Drupal\az_core;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Extension\ModuleHandler;
use Drupal\az_core\Plugin\ConfigProvider\QuickstartConfigProvider;
use Drupal\config_provider\Plugin\ConfigCollector;
use Drupal\config_snapshot\ConfigSnapshotStorageTrait;
use Drupal\config_sync\ConfigSyncSnapshotter;
use Drupal\config_sync\ConfigSyncSnapshotterInterface;
use Drupal\config_update\ConfigListByProviderInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

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
class AZConfigOverride implements LoggerAwareInterface {

  use ConfigSnapshotStorageTrait;
  use LoggerAwareTrait;

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
   * Drupal\Core\Extension\ModuleExtensionList definition.
   *
   * @var \Drupal\Core\Extension\ModuleHandler
   */
  protected $moduleHandler;

  /**
   * Constructs a new AZConfigOverride object.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ModuleExtensionList $extension_list_module, ConfigCollector $config_collector, ConfigSyncSnapshotter $config_sync_snapshotter, ConfigListByProviderInterface $config_update_lister, ModuleHandler $module_handler) {
    $this->configFactory = $config_factory;
    $this->extensionListModule = $extension_list_module;
    $this->configCollector = $config_collector;
    $this->configSyncSnapshotter = $config_sync_snapshotter;
    $this->configUpdateLister = $config_update_lister;
    $this->moduleHandler = $module_handler;
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
        // Only load permissions in partial steps if profile is done.
        if ($this->moduleHandler->moduleExists('az_quickstart')) {
          $permissions = $provider->findProfilePermissions($extensions);
          $overrides = $permissions + $overrides;
        }

        $snapshots = [];
        // Edit active configuration for each explicit override.
        foreach ($overrides as $name => $data) {
          $this->logger->info("Applying override config to @config_id.", [
            '@config_id' => $name,
          ]);
          $config = $this->configFactory->getEditable($name);
          // Generate a UUID for the configuration if one doesn't exist.
          if (!isset($data['uuid']) || empty($data['uuid'])) {
            $data['uuid'] = \Drupal::service('uuid')->generate();
          }
          $config->setData($data);
          $config->Save();

          // Determine the extension owner of the configuration (array tuple).
          $provided_by = $this->configUpdateLister->getConfigProvider($name);
          if (!empty($provided_by[1])) {
            $type = $provided_by[0];
            $owner = $provided_by[1];

            // Record we need to do a snapshot.
            $snapshots[$type][$owner][$name] = $data;
          }
        }

        // Update the config_snapshot of the modules that owned the config.
        foreach ($snapshots as $type => $owners) {
          foreach ($owners as $owner => $names) {
            $snapshot_storage = $this->getConfigSnapshotStorage(ConfigSyncSnapshotterInterface::CONFIG_SNAPSHOT_SET, $type, $owner);
            foreach ($names as $name => $data) {
              $this->logger->info("Snapshotting @config_id for @module.", [
                '@module' => $owner,
                '@config_id' => $name,
              ]);
              $snapshot_storage->write($name, $data);
            }
          }
        }
      }
    }
  }

}
