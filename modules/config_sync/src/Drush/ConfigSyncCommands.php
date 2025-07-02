<?php

declare(strict_types=1);

namespace Drupal\config_sync\Drush;

use Consolidation\AnnotatedCommand\CommandData;
use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Drupal\config_sync\ConfigSyncListerInterface;
use Drupal\Core\Config\ConfigManagerInterface;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\State\StateInterface;
use Drush\Commands\DrushCommands;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Drush integration for the Configuration Synchronizer module.
 */
final class ConfigSyncCommands extends DrushCommands {

  /**
   * The config synchronization lister service.
   *
   * @var \Drupal\config_sync\ConfigSyncListerInterface
   */
  protected $configSyncLister;

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
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Constructs a new ConfigSyncCommands object.
   *
   * @param \Drupal\config_sync\ConfigSyncListerInterface $config_sync_lister
   *   The config synchronization lister service.
   * @param \Drupal\Core\Config\StorageInterface $active_storage
   *   The active configuration storage.
   * @param \Drupal\Core\Config\ConfigManagerInterface $config_manager
   *   The configuration manager.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   */
  public function __construct(
    ConfigSyncListerInterface $config_sync_lister,
    StorageInterface $active_storage,
    ConfigManagerInterface $config_manager,
    StateInterface $state,
  ) {
    parent::__construct();
    $this->configSyncLister = $config_sync_lister;
    $this->activeStorage = $active_storage;
    $this->configManager = $config_manager;
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new static(
      $container->get('config_sync.lister'),
      $container->get('config.storage'),
      $container->get('config.manager'),
      $container->get('state')
    );
  }

  /**
   * Displays a list of all extensions with available configuration updates.
   *
   * @command config-sync-list-updates
   * @usage drush config-sync-list-updates
   *   Display a list of all extensions with available configuration updates.
   * @aliases cs-list
   * @field-labels
   *   type: Operation type
   *   id: Config ID
   *   collection: Collection
   *   label: Label
   *   extension_type: Extension type
   *   extension: Extension
   * @default-fields extension,type,label
   *
   * @return \Consolidation\OutputFormatters\StructuredData\RowsOfFields
   *   List of extensions with available configuration updates.
   */
  public function syncListUpdates($options = ['format' => 'table']) {
    $rows = [];
    foreach ($this->configSyncLister->getExtensionChangelists() as $extension_type => $extensions) {
      foreach ($extensions as $extension_id => $collection_changelists) {
        foreach ($collection_changelists as $collection => $operation_types) {
          foreach ($operation_types as $operation_type => $configurations) {
            foreach ($configurations as $config_id => $config_label) {
              $rows[$config_id] = [
                'type' => $operation_type,
                'id' => $config_id,
                'collection' => $collection === '' ? 'default' : $collection,
                'label' => $config_label,
                'extension_type' => $extension_type,
                'extension' => $extension_id,
              ];
            }
          }
        }
      }
    }

    return new RowsOfFields($rows);
  }

  /**
   * Adds an option to the config-distro-update command.
   *
   * @hook option config-distro-update
   * @option update-mode Specify a mode for updates. Options are 1 (merge), 2 (partial reset), and 3 (full reset).
   * @usage drush config-distro-update --update-mode=2
   *   Run a config distro update with the update mode of 2 (partial reset).
   */
  public function optionsetConfigDistroUpdate($options = ['update-mode' => self::OPT]) {
  }

  /**
   * Sets the specified update mode in the state.
   *
   * @hook pre-command config-distro-update
   */
  public function preConfigDistroUpdate(CommandData $commandData) {
    $updateMode = $commandData->input()->getOption('update-mode');
    if (!empty($updateMode)) {
      $this->state->set('config_sync.update_mode', (int) $updateMode);
    }
  }

}
