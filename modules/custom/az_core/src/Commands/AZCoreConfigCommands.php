<?php

namespace Drupal\az_core\Commands;

use Drupal\az_core\Plugin\ConfigProvider\QuickstartConfigProvider;
use Drupal\config_provider\Plugin\ConfigCollector;
use Drupal\Core\Extension\Exception\UnknownExtensionException;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\user\Entity\Role;
use Drush\Commands\DrushCommands;

/**
 * Add missing permissions from the AZ Quickstart profile to the active site.
 */
class AZCoreConfigCommands extends DrushCommands {

  /**
   * Drupal\config_provider\Plugin\ConfigCollector definition.
   *
   * @var \Drupal\config_provider\Plugin\ConfigCollector
   */
  protected $configCollector;

  /**
   * Drupal\Core\Extension\ModuleExtensionList definition.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList
   */
  protected $extensionLister;

  /**
   * Constructs a new AZCoreConfigCommands object.
   *
   * This command class provides commands that deal with configuration
   * management of the distribution.
   *
   * @param \Drupal\config_provider\Plugin\ConfigCollector $configCollector
   *   The config_provider.collector service.
   * @param \Drupal\Core\Extension\ModuleExtensionList $extensionLister
   *   The extension.list.module service.
   */
  public function __construct(ConfigCollector $configCollector, ModuleExtensionList $extensionLister) {
    $this->configCollector = $configCollector;
    $this->extensionLister = $extensionLister;
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

}
