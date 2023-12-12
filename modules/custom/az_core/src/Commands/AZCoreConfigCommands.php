<?php

namespace Drupal\az_core\Commands;

use Drupal\az_core\Plugin\ConfigProvider\QuickstartConfigProvider;
use Drupal\config_provider\Plugin\ConfigCollector;
use Drupal\config_update\ConfigDiffer;
use Drupal\Component\Serialization\Yaml;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Config\FileStorage;
use Drupal\Core\Config\StorageException;
use Drupal\Core\Extension\Exception\UnknownExtensionException;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\user\Entity\Role;
use Drush\Commands\DrushCommands;

/**
 * Contains Quickstart configuration-related commands.
 */
class AZCoreConfigCommands extends DrushCommands {

  /**
   * Drupal\config_provider\Plugin\ConfigCollector definition.
   *
   * @var \Drupal\config_provider\Plugin\ConfigCollector
   */
  protected $configCollector;

  /**
   * Drupal\config_update\ConfigDiffer definition.
   *
   * @var \Drupal\config_update\ConfigDiffer
   */
  protected $configDiffer;

  /**
   * Drupal\Core\Config\ConfigFactory definition.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * Drupal\Core\Extension\ModuleExtensionList definition.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected $extensionLister;

  /**
   * Drupal\Component\Serialization\Yaml definition.
   *
   * @var \Drupal\Component\Serialization\Yaml
   */
  protected $yamlSerialization;

  /**
   * Constructs a new AZCoreConfigCommands object.
   *
   * This command class provides commands that deal with configuration
   * management of the distribution.
   *
   * @param \Drupal\Core\Config\ConfigFactory $configFactory
   *   The config.factory service.
   * @param \Drupal\config_provider\Plugin\ConfigCollector $configCollector
   *   The config_provider.collector service.
   * @param \Drupal\config_update\ConfigDiffer $configDiffer
   *   The config_update.config_diff service.
   * @param \Drupal\Core\Extension\ModuleExtensionList $extensionLister
   *   The extension.list.module service.
   * @param \Drupal\Component\Serialization\Yaml $yamlSerialization
   *   The serialization.yaml service.
   */
  public function __construct(ConfigFactory $configFactory, ConfigCollector $configCollector, ConfigDiffer $configDiffer, ModuleExtensionList $extensionLister, Yaml $yamlSerialization) {
    $this->configFactory = $configFactory;
    $this->configCollector = $configCollector;
    $this->configDiffer = $configDiffer;
    $this->extensionLister = $extensionLister;
    $this->yamlSerialization = $yamlSerialization;
  }

  /**
   * A custom Drush command to add missing installation profile permissions.
   *
   * @command az-core-config-add-permissions
   * @aliases az-core-add-perms
   */
  public function addMissingPermissions() {
    $permissionCount = 0;
    $permissions = [];
    try {
      // Get the list of Config Providers.
      $profile = $this->extensionLister->get('az_quickstart');
      $extensions = ['az_quickstart' => $profile];
      $providers = $this->configCollector->getConfigProviders();
      // Get the installation profile's permissions.
      foreach ($providers as $provider) {
        if ($provider instanceof QuickstartConfigProvider) {
          $permissions = $provider->findProfilePermissions($extensions);
        }
      }
      // Load all roles.
      $roles = Role::loadMultiple();
      // Loop through roles by ID.
      foreach ($roles as $id => $role) {
        $name = "user.role.$id";
        // Check if the installation profile maintains permissions for the role.
        if (!empty($permissions[$name]['permissions'])) {
          foreach ($permissions[$name]['permissions'] as $permName) {
            // Check if permission is missing from the role in active config.
            if (!$role->hasPermission($permName)) {
              $permissionCount++;
              // Ask if the permission should be added to the role.
              if ($this->io()->confirm(dt('Add permission "@perm" to @role role?', [
                '@perm' => $permName,
                '@role' => $role->label(),
              ]))) {
                // Add the permission if requested.
                $role->grantPermission($permName);
                $this->output()->writeln(dt('Added permission "@perm" to @role role.', [
                  '@perm' => $permName,
                  '@role' => $role->label(),
                ]));
              }
            }
          }
        }
        $role->save();
      }
    }
    catch (UnknownExtensionException $e) {
      $this->output()->writeln("Could not find the installation profile.");
    }
    if ($permissionCount < 1) {
      $this->output()->writeln("No missing permissions found.");
    }
  }

  /**
   * Rewrite changed distribution configuration files to modules.
   *
   * @param string $modules
   *   Optional comma-delimited module machine names.
   *
   * @command az-core-distribution-config
   */
  public function exportDistributionConfiguration($modules = '') {
    $extensions = $this->extensionLister->getList();
    $installed = $this->extensionLister->getAllInstalledInfo();
    $arguments = [];
    if (!empty($modules)) {
      $arguments = explode(',', $modules);
    }
    foreach ($extensions as $key => $extension) {
      // Only run for distribution extensions.
      if (substr($key, 0, 3) !== "az_") {
        continue;
      }
      // If argument list supplied, only run for given modules.
      if (!empty($arguments) && !in_array($key, $arguments)) {
        continue;
      }
      // Only run for enabled extensions.
      if (!isset($installed[$key])) {
        $this->output()->writeln(dt('@extension not installed.', [
          '@extension' => $key,
        ]));
        continue;
      }
      $this->output()->writeln(dt('@extension...', [
        '@extension' => $key,
      ]));
      $providers = $this->configCollector->getConfigProviders();
      foreach ($providers as $provider) {
        $dir = $provider->getDirectory();
        // Only examine providers with a defined storage directory.
        if (empty($dir)) {
          continue;
        }
        // Find out which config items the module's storage has available.
        $path = $extension->getPath() . DIRECTORY_SEPARATOR . $dir;
        $storage = new FileStorage($path);
        $all = $storage->listAll();
        foreach ($all as $item) {
          // Get the state of module configuration versus active configuration.
          $original = $storage->read($item);
          $active = $this->configFactory->get($item)->get();
          // Remove site-specific identifiers.
          unset($original['_core']);
          unset($original['uuid']);
          unset($active['_core']);
          unset($active['uuid']);
          // Diff the state of configuration to check for changes.
          $diff = $this->configDiffer->diff($original, $active);
          if (!$diff->isEmpty()) {

            if ($this->io()->confirm(dt('    Update [@key/@dir] @item?', [
              '@key' => $key,
              '@dir' => $dir,
              '@item' => $item,
            ]))) {
              try {
                $storage->write($item, $active);
              }
              catch (StorageException $e) {
                $this->output()->writeln(dt('    Failed to write @item', [
                  '@item' => $item,
                ]));
              }
            }
          }
          else {
            $this->output()->writeln(dt('    -- unmodified -- @item', [
              '@item' => $item,
              '@dir' => $dir,
            ]));
          }
        }
      }
    }

  }

  /**
   * Fetch a single configuration item from active storage with hashes removed.
   *
   * @param string $item
   *   Configuration item to export.
   *
   * @command az-core-config-export-single
   */
  public function exportConfigSingle($item) {
    $config = $this->configFactory->get($item)->get();
    unset($config['_core']);
    unset($config['uuid']);
    $this->output()->writeln($this->yamlSerialization->encode($config));
  }

}
