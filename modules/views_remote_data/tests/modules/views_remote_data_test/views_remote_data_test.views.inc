<?php

/**
 * @file
 * Provide views data for tests.
 */

declare(strict_types=1);

/**
 * Implements hook_views_data().
 */
function views_remote_data_test_views_data(): array {
  $data = [];

  $data['views_remote_data_test_simple']['table']['group'] = 'views_remote_data_test_simple';
  $data['views_remote_data_test_simple']['table']['base'] = [
    'title' => 'views_remote_data_test',
    'query_id' => 'views_remote_data_query',
  ];

  $data['views_remote_data_test_none']['table']['group'] = 'views_remote_data_test_none';
  $data['views_remote_data_test_none']['table']['base'] = [
    'title' => 'views_remote_data_test_none',
    'query_id' => 'views_remote_data_query',
  ];

  // @todo create a derivative of \Drupal\views\Plugin\views\row\EntityRow.
  $data['views_remote_data_test_entity_test']['table']['group'] = 'views_remote_data_test_entity_test';
  $data['views_remote_data_test_entity_test']['table']['entity type'] = 'entity_test';
  $data['views_remote_data_test_entity_test']['table']['entity revision'] = FALSE;
  $data['views_remote_data_test_entity_test']['table']['base'] = [
    'title' => 'views_remote_data_test_entity_test',
    'query_id' => 'views_remote_data_query',
  ];

  return $data;
}
