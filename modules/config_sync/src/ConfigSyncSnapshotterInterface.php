<?php

namespace Drupal\config_sync;

/**
 * ConfigSyncSnapshotterInterface.
 *
 * Provides helper functions for taking snapshots of extension-provided
 * configuration.
 */
interface ConfigSyncSnapshotterInterface {

  /**
   * Install snapshot mode.
   */
  const SNAPSHOT_MODE_INSTALL = 'install';

  /**
   * Import snapshot mode.
   */
  const SNAPSHOT_MODE_IMPORT = 'import';

  /**
   * Name of snapshot set for config_sync.
   */
  const CONFIG_SNAPSHOT_SET = 'config_sync';

  /**
   * Takes a snapshot of configuration from specified modules or themes.
   *
   * Two modes are supported: install and import. Install mode is invoked when
   * an extension is initially installed, while import mode is invoked on
   * subsequent import of configuration updates.
   *
   * The distinction between install and import modes has implications for the
   * handling of extension-provided configuration alters. Alters are considered
   * to be "owned" by the extension that provides them. On install, existing
   * snapshots should be altered only the newly-installed module or modules.
   * This approach ensures the snapshot mirrors the installed state of the
   * extension-provided configuration. In contrast, on import, alters should be
   * applied from all installed modules.
   *
   * @param string $type
   *   The type of extension to snapshot.
   * @param array $names
   *   An array of extension names.
   * @param string $mode
   *   The snapshot mode. Valid values are:
   *   - ConfigSyncSnapshotterInterface::SNAPSHOT_MODE_INSTALL
   *   - ConfigSyncSnapshotterInterface::SNAPSHOT_MODE_IMPORT.
   */
  public function refreshExtensionSnapshot($type, array $names, $mode);

  /**
   * Takes a snapshot of configuration from all installed modules and themes.
   */
  public function createSnapshot();

}
