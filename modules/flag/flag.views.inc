<?php

/**
 * @file
 * Contains views API hooks for Flag module.
 */

/**
 * Implements hook_views_data().
 */
function flag_views_data() {
  $data = [];

  $data['flag_counts']['count'] = [
    'title' => t('Flag counter'),
    'help' => t('The number of times a piece of content is flagged by any user.'),
    'field' => [
      'id' => 'numeric',
      // 'click sortable' => TRUE,
    ],
    /*'sort' => array(
      'id' => 'groupby_numeric',
    ),
    'filter' => array(
      'id' => 'numeric',
    ),
    'argument' => array(
      'id' => 'numeric',
    ),*/
  ];

  $data['flag_counts']['last_updated'] = [
    'title' => t('Time last flagged'),
    'help' => t('The time a piece of content was most recently flagged by any user.'),
    'field' => [
      'id' => 'date',
      // 'click sortable' => TRUE,
    ],
    /*'sort' => array(
      'id' => 'date',
    ),
    'filter' => array(
      'id' => 'date',
    ),
    'argument' => array(
      'id' => 'date',
    ),*/
  ];

  return $data;
}

/**
 * Implements hook_views_data_alter().
 */
function flag_views_data_alter(array &$data) {
  $flags = \Drupal::service('flag')->getAllFlags();
  $entity_type_manager = \Drupal::entityTypeManager();

  foreach ($flags as $flag) {
    $entity_type_id = $flag->getFlaggableEntityTypeId();

    $entity_type = $entity_type_manager->getDefinition($entity_type_id);
    if ($entity_type->hasHandlerClass('views_data')) {
      $base_table = $entity_type_manager->getHandler($entity_type_id, 'views_data')->getViewsTableForEntityType($entity_type);
      $data[$base_table]['flag_relationship'] = [
        'title' => t('@entity_label flag', ['@entity_label' => $entity_type->getLabel()]),
        'help' => t('Limit results to only those entity flagged by a certain flag; Or display information about the flag set on a entity.'),
        'relationship' => [
          'group' => t('Flag'),
          'label' => t('Flags'),
          'base' => 'flagging',
          'base field' => 'entity_id',
          'relationship field' => $entity_type->getKey('id'),
          'id' => 'flag_relationship',
          'flaggable' => $entity_type_id,
        ],
      ];
    }

  }
}
