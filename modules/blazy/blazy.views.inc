<?php

/**
 * @file
 * Provides views data for blazy.module.
 */

/**
 * Implements hook_views_data_alter().
 */
function blazy_views_data_alter(&$data) {
  // @todo let's keep it for a while as this can be useful for EB.
  $data['file_managed']['blazy_file'] = [
    'title' => t('Blazy'),
    'help'  => t('Displays a preview of a File using Blazy, if applicable.'),
    'field' => [
      'id' => 'blazy_file',
      'click sortable' => FALSE,
    ],
  ];

  // @todo recheck to use core Media post VEF/ VEM removal.
  $data['media_field_data']['blazy_media'] = [
    'title' => 'Blazy',
    'help'  => t('Displays a preview of a Media using Blazy, if applicable.'),
    'field' => [
      'id' => 'blazy_media',
      'click sortable' => FALSE,
    ],
  ];
}

/**
 * Implements hook_views_plugins_style_alter().
 */
function blazy_views_plugins_style_alter(array &$plugins) {
  $plugins['blazy'] = [
    'id'             => 'blazy',
    'label'          => 'Blazy Grid',
    'description'    => t('Display the results in a Blazy grid.'),
    'class'          => 'Drupal\blazy\Plugin\views\style\BlazyViews',
    'display_types'  => ['normal'],
    'help'           => t('Works best with Views field containing Blazy.'),
    'parent'         => 'parent',
    'plugin_type'    => 'style',
    'register_theme' => FALSE,
    'short_title'    => 'Blazy',
    'title'          => 'Blazy Grid',
    'provider'       => 'blazy',
  ];
}
