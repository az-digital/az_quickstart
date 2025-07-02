<?php

/**
 * @file
 * Provides views data for views_remote_data and its integrators.
 */

declare(strict_types=1);

use Drupal\views\Views;

/**
 * Implements hook_views_data_alter().
 *
 * Adds the `property` field to views data which uses the remote data query.
 */
function views_remote_data_views_data_alter(array &$data): void {
  foreach ($data as $key => $item) {
    $query_id = $item['table']['base']['query_id'] ?? '';
    if ($query_id !== 'views_remote_data_query') {
      continue;
    }
    $data[$key]['table']['wizard_id'] = 'views_remote_data';
    $data[$key]['property'] = [
      'title' => t('Property value'),
      'help' => t('A property value in the remote data object.'),
      'argument' => [
        'id' => 'views_remote_data_property',
      ],
      'field' => [
        'id' => 'views_remote_data_property',
      ],
      'filter' => [
        'id' => 'views_remote_data_property',
      ],
      'sort' => [
        'id' => 'views_remote_data_property',
      ],
    ];

    if (!empty($item['table']['entity type'])) {
      $data[$key]['rendered_entity'] = [
        'field' => [
          'title' => t('Rendered entity'),
          'help' => t('Renders an entity in a view mode.'),
          'id' => 'rendered_entity',
        ],
      ];
    }
  }
}

/**
 * Implements hook_views_plugins_cache_alter().
 */
function views_remote_data_views_plugins_cache_alter(array &$plugins) {
  $views_data = Views::viewsData();
  $valid_bases = array_keys(array_filter($views_data->getAll(), static function (array $data): bool {
    $query_id = $data['table']['base']['query_id'] ?? '';
    return $query_id === 'views_remote_data_query';
  }));
  $plugins['views_remote_data_tag']['base'] = $valid_bases;
  $plugins['views_remote_data_time']['base'] = $valid_bases;
}
