<?php
/**
 * @file
 * Views integration for az_documentation.
 */

use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Implements hook_views_data().
 */
function az_documentation_views_data() {
  $data = [];

  // Attach a pseudo-field to the node base table so the custom filter handler
  // becomes selectable when building node views. The filter handler itself
  // does not rely on an underlying column; it just needs the base table alias
  // (provided by ensureMyTable()).
  $data['node_field_data']['az_path_alias_prefix'] = [
    'title' => 'Path alias prefix',
    'help' => 'Filter nodes by the current path alias prefix (e.g. /admin/documentation).',
    'group' => 'Documentation',
    'filter' => [
      'id' => 'az_path_alias_prefix',
    ],
  ];

  return $data;
}
