<?php

/**
 * @file
 * Hooks specific to the Config Ignore module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Alter the list of config entities that should be ignored.
 */
function hook_config_ignore_settings_alter(array &$settings) {
  $settings[] = 'system.site';
  $settings[] = 'field.*';
}

/**
 * Alter the list of config entities that should be ignored.
 */
function hook_config_ignore_ignored_alter(\Drupal\config_ignore\ConfigIgnoreConfig $ignored) {
  $list = $ignored->getList('import', 'create');
  $list = array_filter($list, fn($line) => $line !== 'webform.webform.*');
  $ignored->setList('import', 'create', $list);
}

/**
 * @} End of "addtogroup hooks".
 */
