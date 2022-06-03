<?php

namespace Drupal\az_migration;

/**
 * Interface of FileEntityDealerManager.
 *
 * @see \Drupal\az_migration\FileEntityDealerManager
 */
interface FileEntityDealerManagerInterface {

  /**
   * Gets the plugin definitions for the specified file entity type.
   *
   * @param string $type
   *   The file entity type.
   * @param string $scheme
   *   The URI scheme.
   *
   * @return \Drupal\az_migration\FileEntityDealerPluginInterface|null
   *   A fully configured plugin instance or NULL if no applicable plugin was
   *   found.
   */
  public function createInstanceFromTypeAndScheme(string $type, string $scheme);

}
