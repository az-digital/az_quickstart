<?php

/**
 * @file
 * Provide views data for quick_node_clone.module.
 */

/**
 * Implements hook_views_data_alter().
 */
function quick_node_clone_views_data_alter(&$data) {
  $data['node']['clone_link'] = [
    'field' => [
      'title' => t('Add clone link'),
      'help' => t('Provide a clone link to the Content.'),
      'id' => 'quick_node_clone_link',
    ],
  ];
}
