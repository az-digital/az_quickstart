<?php

/**
 * @file
 * Provide views data for the az_marketing_cloud module.
 */

/**
 * Implements hook_views_data().
 */
function az_marketing_cloud_views_data() {
  $data['az_dropbutton']['table']['join'] = [
  // #global is a special flag which allows a table to appear all the time.
    '#global' => [],
  ];

  $data['az_dropbutton']['az_dropbutton_field'] = [
    'group' => t('Global'),
    'title' => t('AZ Dropbutton'),
    'help' => t('Provides a drop button for links.'),
    'field' => [
      'id' => 'az_dropbutton',
    ],
  ];

  return $data;
}
