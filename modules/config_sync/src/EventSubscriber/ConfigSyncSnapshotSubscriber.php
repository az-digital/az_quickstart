<?php

namespace Drupal\config_sync\EventSubscriber;

use Drupal\config_distro\Event\ConfigDistroEvents;
use Drupal\config_distro\Event\DistroStorageImportEvent;
use Drupal\config_filter\ConfigFilterManagerInterface;
use Drupal\config_sync\ConfigSyncSnapshotterInterface;
use Drupal\Core\State\StateInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Updates the snapshot when config is imported.
 */
class ConfigSyncSnapshotSubscriber implements EventSubscriberInterface {

  /**
   * The snapshotter.
   *
   * @var \Drupal\config_sync\ConfigSyncSnapshotterInterface
   */
  protected $snapshotter;

  /**
   * The filter manager.
   *
   * @var \Drupal\config_filter\ConfigFilterManagerInterface
   */
  protected $configFilterManager;

  /**
   * The state storage object.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Constructs the ConfigSnapshotSubscriber object.
   *
   * @param \Drupal\config_sync\ConfigSyncSnapshotterInterface $snapshotter
   *   The snapshotter.
   * @param \Drupal\config_filter\ConfigFilterManagerInterface $config_filter_manager
   *   The filter manager.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state storage object.
   */
  public function __construct(ConfigSyncSnapshotterInterface $snapshotter, ConfigFilterManagerInterface $config_filter_manager, StateInterface $state) {
    $this->snapshotter = $snapshotter;
    $this->configFilterManager = $config_filter_manager;
    $this->state = $state;
  }

  /**
   * Refreshes the snapshot for extensions whose updates were imported.
   *
   * @param \Drupal\config_distro\Event\DistroStorageImportEvent $event
   *   The Event to process.
   */
  public function onConfigDistroImport(DistroStorageImportEvent $event) {
    $filters = $this->configFilterManager->getDefinitions();
    $extensions = [];
    // There is a filter for each extension that had updates.
    foreach ($filters as $filter) {
      // Only process our own filters.
      if (($filter['provider'] === 'config_sync') &&
        // The updates were imported if the filter was enabled.
        $filter['status'] &&
        // We're only responding to events for filters using the storage
        // provided by config_distro.
        in_array('config_distro.storage.distro', $filter['storages'])) {
        $extensions[$filter['extension_type']][] = $filter['extension_name'];
      }
    }

    foreach ($extensions as $type => $names) {
      $this->snapshotter->refreshExtensionSnapshot($type, $names, ConfigSyncSnapshotterInterface::SNAPSHOT_MODE_IMPORT);
    }

    // Clear data on previously-selected plugins.
    $this->state->delete('config_sync.plugins');
    $this->configFilterManager->clearCachedDefinitions();
  }

  /**
   * Registers the methods in this class that should be listeners.
   *
   * @return array
   *   An array of event listener definitions.
   */
  public static function getSubscribedEvents() {
    $events[ConfigDistroEvents::IMPORT][] = ['onConfigDistroImport', 40];
    return $events;
  }

}
