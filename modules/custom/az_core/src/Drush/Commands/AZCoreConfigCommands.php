<?php

namespace Drupal\az_core\Drush\Commands;

use Drupal\az_core\Plugin\ConfigProvider\QuickstartConfigProvider;
use Drupal\Component\Diff\Diff;
use Drupal\Component\Serialization\Yaml;
use Drupal\config_provider\Plugin\ConfigCollector;
use Drupal\config_update\ConfigDiffer;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Config\FileStorage;
use Drupal\Core\Config\InstallStorage;
use Drupal\Core\Config\StorageException;
use Drupal\Core\Extension\Exception\UnknownExtensionException;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\user\Entity\Role;
use Drupal\user\PermissionHandler;
use Drush\Commands\DrushCommands;
use Symfony\Component\Yaml\Yaml as SymfonyYaml;

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
   * Drupal\user\PermissionHandler definition.
   *
   * @var \Drupal\user\PermissionHandler
   */
  protected $permissionHandler;

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
   * @param \Drupal\user\PermissionHandler $permissionHandler
   *   The user permissions service.
   */
  public function __construct(ConfigFactory $configFactory, ConfigCollector $configCollector, ConfigDiffer $configDiffer, ModuleExtensionList $extensionLister, Yaml $yamlSerialization, PermissionHandler $permissionHandler) {
    $this->configFactory = $configFactory;
    $this->configCollector = $configCollector;
    $this->configDiffer = $configDiffer;
    $this->extensionLister = $extensionLister;
    $this->yamlSerialization = $yamlSerialization;
    $this->permissionHandler = $permissionHandler;
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
    $installed = array_intersect_key($extensions, $this->extensionLister->getAllInstalledInfo());
    $rules = $this->loadExportRules();
    $metatag_rules = $rules['strip_metatags'] ?? [];

    $overrides = [];
    $arguments = [];
    if (!empty($modules)) {
      $arguments = explode(',', $modules);
    }
    $providers = $this->configCollector->getConfigProviders();
    foreach ($providers as $provider) {
      if ($provider instanceof QuickstartConfigProvider) {
        $overrides = $provider->getOnlyOverrideConfig($installed);
      }
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
          // Skip if the configuration is overridden.
          if (isset($overrides[$item]) && ($dir !== QuickstartConfigProvider::ID)) {
            $this->output()->writeln(dt('    -- skipping overridden config -- @item', [
              '@item' => $item,
              '@dir' => $dir,
            ]));
            continue;
          }
          if ((strpos($item, 'user.role.') === 0)) {
            // Make role alterations if a role config.
            $active = $this->prepareRoleConfig($active, $original);
          }
          if (in_array($item, $metatag_rules)) {
            // Strip metatags workaround. Replace with more robust array logic.
            $active = $this->stripMetatags($active);
          }
          if (isset($rules['merge'][$item])) {
            // Add export rules for merge.
            $active = array_merge($active, $rules['merge'][$item]);
          }
          // Diff the state of configuration to check for changes.
          $diff = $this->configDiffer->diff($original, $active);
          if (!$this->diffIsEmpty($diff)) {
            if ($this->io()->confirm(dt('    Update [@key/@dir] @item?', [
              '@key' => $key,
              '@dir' => $dir,
              '@item' => $item,
            ]))) {
              try {
                $storage->write($item, $active);
                $original = $active;
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
    $this->exportDependencies();
  }

  /**
   * Export new dependencies found in the distribution.
   */
  protected function exportDependencies() {
    $rules = $this->loadExportRules();
    $ignores = $rules['ignore_config'] ?? [];
    $extensions = $this->extensionLister->getList();
    $providers = $this->configCollector->getConfigProviders();
    $seen = [];
    $dependencies = [];
    $choices = [];

    foreach ($extensions as $key => $extension) {
      // Only run for distribution extensions.
      if (substr($key, 0, 3) !== "az_") {
        continue;
      }
      $choices[$key] = $key;
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
          // Maintain list of every config item we've seen in modules.
          $seen[$item] = $item;
          $config = $storage->read($item);
          $item_deps = $config['dependencies']['config'] ?? [];
          // Build list of potential module dependencies.
          foreach ($item_deps as $dep) {
            $dependencies[$dep] = $dep;
          }
        }
      }
    }
    // Restripe array.
    $dependencies = array_values($dependencies);
    while (TRUE) {
      $new_dependencies = [];
      foreach ($dependencies as $dependency) {
        if (!isset($seen[$dependency]) && (!in_array($dependency, $ignores))) {
          if ($this->io()->confirm(dt('Add NEW configuration @item?', [
            '@item' => $dependency,
          ]))) {
            $active = $this->configFactory->get($dependency)->get();
            unset($active['_core']);
            unset($active['uuid']);
            $item_deps = $active['dependencies']['config'] ?? [];
            foreach ($item_deps as $nested_dependency) {
              if (!in_array($nested_dependency, $dependencies,)) {
                $new_dependencies[$nested_dependency] = $nested_dependency;
              }
            }
            $choice = $this->io()->choice(dt('Where should @item be exported?', [
              '@item' => $dependency,
            ]), $choices);
            $path = $this->extensionLister->getPath($choice) . DIRECTORY_SEPARATOR . InstallStorage::CONFIG_INSTALL_DIRECTORY;
            $storage = new FileStorage($path);
            try {
              $storage->write($dependency, $active);
              $seen[$dependency] = $dependency;
            }
            catch (StorageException $e) {
              $this->output()->writeln(dt('Failed to write NEW configuration @item', [
                '@item' => $dependency,
              ]));
            }
          }
        }
      }
      if (empty($new_dependencies)) {
        break;
      }
      $dependencies = array_values($new_dependencies);
    }

  }

  /**
   * Load special case rules for distribution export.
   *
   * @return array
   *   The export rules.
   */
  protected function loadExportRules() {
    $rules = [
      'strip_metatags' => [],
      'ignore_config' => [],
      'merge' => [],
    ];
    try {
      $rules = SymfonyYaml::parseFile($this->extensionLister->getPath('az_core') . '/az_core.distribution_export.yml');
    }
    catch (\Exception $e) {
    }

    return $rules;
  }

  /**
   * Remove metatag rules (placeholder).
   *
   * @param array $config
   *   The config with metatags.
   *
   * @return array
   *   The prepared configuration.
   */
  protected function stripMetatags($config) {
    // @todo replace with more general-use yml diff logic.
    if (!empty($config['dependencies']['config']) && is_array($config['dependencies']['config'])) {
      // Filter out metatag dependency.
      $matches = preg_filter('/field\.field\.node\.az_\w+\.field_az_metatag/', '$0', $config['dependencies']['config']);
      $config['dependencies']['config'] = array_values(array_diff($config['dependencies']['config'], $matches));
    }

    // Filter out disabled field display settings.
    unset($config['hidden']['field_az_metatag']);
    return $config;
  }

  /**
   * Prepare role permissions for export.
   *
   * @param array $active
   *   The active site role config.
   * @param array $original
   *   A original role config in a module.
   *
   * @return array
   *   The prepared active permissions.
   */
  protected function prepareRoleConfig($active, $original) {
    // Dependencies are calculated by our config provider on import.
    $active['dependencies'] = [];
    $active_perms = $active['permissions'] ?? [];
    $original_perms = $original['permissions'] ?? [];
    // Compute which permissions are seemingly being removed.
    $removed_perms = array_diff($original_perms, $active_perms);
    $retain_perms = [];
    // Persist invalid permissions from the remove list.
    // We don't want to remove permissions from inactive modules.
    $permission_list = $this->permissionHandler->getPermissions();
    foreach ($removed_perms as $permission) {
      if (!isset($permission_list[$permission])) {
        $retain_perms[] = $permission;
      }
    }
    // Add the inactive permissions back into the config.
    $final_perms = array_merge($active_perms, $retain_perms);
    sort($final_perms);
    $active['permissions'] = $final_perms;
    return $active;
  }

  /**
   * Checks if a diff is empty.
   *
   * @param \Drupal\Component\Diff\Diff $diff
   *   A diff object.
   *
   * @return bool
   *   True if two sequences were identical.
   */
  protected function diffIsEmpty(Diff $diff) {
    foreach ($diff->getEdits() as $edit) {
      if ($edit->type !== 'copy') {
        return FALSE;
      }
    }
    return TRUE;
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
