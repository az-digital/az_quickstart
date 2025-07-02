<?php

namespace Drupal\config_sync\Plugin;

use Drupal\config_provider\Plugin\ConfigCollectorInterface;

/**
 * Class for invoking configuration providers.
 */
interface SyncConfigCollectorInterface extends ConfigCollectorInterface {

  /**
   * Adds configuration for snapshotting.
   *
   * Call this method instead of ::addInstallableConfig() to add only unaltered
   * configuration.
   *
   * @param \Drupal\Core\Extension\Extension[] $extensions
   *   (Optional) An associative array of Extension objects, keyed by extension
   *   name. If provided, data loaded will be limited to these extensions.
   */
  public function addConfigForSnapshotting(array $extensions = []);

  /**
   * Alters configuration snapshots.
   *
   * In certain cases, the configuration suitable for snapshotting will differ
   * from that suitable for comparing to a snapshot. The snapshot should
   * reflect the current installed state. If alters are in effect, the
   * snapshot should be updated accordingly as a new module is installed or
   * updated.
   *
   * @param \Drupal\Core\Extension\Extension[] $extensions
   *   (Optional) An associative array of Extension objects, keyed by extension
   *   name. If provided, data loaded will be limited to these extensions.
   */
  public function alterConfigSnapshots(array $extensions = []);

}
