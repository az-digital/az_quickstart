<?php

/**
 * @file
 * Drush commands.
 */

/**
 * Implements drush_hook_COMMAND().
 */
function config_inspector_drush_command() {
  $items['inspect_config'] = [
    'description' => 'Inspect config for schema errors.',
    'arguments' => ['key' => 'Configuration key'],
    'options' => [
      'only-error' => 'Display only errors.',
      'detail' => 'Show detailed errors.',
      'skip-keys' => 'Configuration keys to skip',
    ],
    'outputformat' => [
      'default' => 'table',
      'pipe-format' => 'list',
      'field-labels' => ['key' => 'Configuration key', 'status' => 'Status'],
      'output-data-type' => 'format-table',
    ],
  ];

  return $items;
}

/**
 * Implements drush_hook_COMMAND().
 */
function drush_config_inspector_inspect_config($key = NULL) {
  $rows = [];
  $keys = is_null($key) ? \Drupal::service('config.storage')->listAll() : [$key];
  $onlyError = drush_get_option('only-error');
  $detail = drush_get_option('detail');
  $skipKeys = array_fill_keys(explode(',', drush_get_option('skip-keys')), '1');
  $inspector = \Drupal::service('config_inspector.manager');

  foreach ($keys as $name) {
    if (isset($skipKeys[$name])) {
      continue;
    }
    if (!$inspector->hasSchema($name)) {
      $status = dt('No schema');
    }
    else {
      $result = $inspector->checkValues($name);
      if (is_array($result)) {
        if ($detail) {
          foreach ($result as $key => $error) {
            $rows[$key] = ['key' => $key, 'status' => $error];
          }
          continue;
        }
        else {
          $status = dt('@count errors', ['@count' => count($result)]);
        }
      }
      else {
        if ($onlyError) {
          continue;
        }
        $status = dt('Correct');
      }
    }
    $rows[$name] = ['key' => $name, 'status' => $status];
  }

  return $rows;
}
