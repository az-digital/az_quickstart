<?php

namespace Drupal\config_sync;

/**
 * Provides extension methods.
 */
trait ConfigSyncExtensionsTrait {

  /**
   * Returns the names of currently installed modules and themes.
   *
   * @return array[]
   *   Associative array in which keys are extension types (module or theme)
   *   and values are arrays of extension names.
   */
  protected function getSyncExtensions() {
    $types = [
      'module' => array_keys(\Drupal::service('module_handler')->getModuleList()),
      'theme' => array_keys(\Drupal::service('theme_handler')->listInfo()),
    ];

    return $types;
  }

}
