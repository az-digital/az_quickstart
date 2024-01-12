<?php

/**
 * @file
 * Provide views data tied to the az_publication module.
 */

/**
 * Implements hook_views_data().
 */
function az_publication_views_data() {

  $data['views']['az_citation_style_argument'] = [
    'title' => t('Citation Style'),
    'help' => t('Allow a contextual argument to select citation style.'),
    'argument' => [
      'id' => 'az_citation_style_argument',
    ],
  ];

  return $data;
}
