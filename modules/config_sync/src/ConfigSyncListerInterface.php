<?php

namespace Drupal\config_sync;

/**
 * Provides methods related to config listing.
 */
interface ConfigSyncListerInterface {

  /**
   * Mode in which available updates are merged into the active configuration.
   */
  const UPDATE_MODE_MERGE = 1;

  /**
   * Mode in which available updates reset the active configuration.
   *
   * An available update is a difference between the Any customizations of these
   * items in the active configuration are discarded.
   */
  const UPDATE_MODE_PARTIAL_RESET = 2;

  /**
   * Mode in which the active configuration is reset to the provided state.
   *
   * Unlike ::UPDATE_MODE_PARTIAL_RESET, this mode applies to all provided
   * configuration--not only what has available updates. Any customizations in
   * the active configuration are discarded.
   */
  const UPDATE_MODE_FULL_RESET = 3;

  /**
   * The default update mode.
   */
  const DEFAULT_UPDATE_MODE = self::UPDATE_MODE_MERGE;

  /**
   * Returns a change list for all installed extensions.
   *
   * @param array $extension_names
   *   Array with keys of extension types ('module', 'theme') and values arrays
   *   of extension names.
   *
   * @return array
   *   Associative array of configuration changes keyed by extension type
   *   (module or theme) in which values are arrays keyed by extension name.
   */
  public function getExtensionChangelists(array $extension_names = []);

  /**
   * Returns a change list for a given module or theme.
   *
   * @param string $type
   *   The type of extension (module or theme).
   * @param string $name
   *   The machine name of the extension.
   *
   * @return array
   *   Associative array of configuration changes keyed by the type of change
   *   in which values are arrays of configuration item labels keyed by item
   *   name.
   */
  public function getExtensionChangelist($type, $name);

}
