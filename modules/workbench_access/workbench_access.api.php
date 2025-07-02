<?php

/**
 * @file
 * API documentation for Workbench Access.
 */

use Drupal\Core\Config\Config;

/**
 * Converts scheme settings to use the AccessScheme entity type.
 *
 * This hook has no return value. Modify $settings by reference to match the
 * array defined by your plugin's implementation of
 * AccessControlHierarchyInterface::defaultConfiguration().
 *
 * @param array $settings
 *   An array of settings for the plugin. Likely empty. Be certain to only act
 *   on your plugin scheme.
 * @param Drupal\Core\Config\Config $config
 *   Current data object for Workbench Access configuration.
 */
function hook_workbench_access_scheme_update_alter(array &$settings, Config $config) {
  if ($config->get('scheme') === 'my_plugin_scheme') {
    $fields = [];
    foreach ($config->get('fields') as $entity_type => $field_info) {
      foreach (array_filter($field_info) as $bundle => $field_name) {
        $fields[] = [
          'entity_type' => $entity_type,
          'bundle' => $bundle,
          'field' => $field_name,
        ];
      }
    }
    $settings = [
      'my_scheme_type' => array_values($config->get('parents')),
      'fields' => $fields,
    ];
  }
}
