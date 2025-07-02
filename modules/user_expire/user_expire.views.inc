<?php

/**
 * @file
 * Views integration for the User Expire module.
 */

/**
 * Implements hook_views_data().
 *
 * {@inheritdoc}
 */
function user_expire_views_data() {
  $data['user_expire']['table']['group'] = t('User');

  $data['user_expire']['table']['join'] = [
    'users_field_data' => [
      'left_field' => 'uid',
      'field' => 'uid',
    ],
  ];

  $data['user_expire']['expiration'] = [
    'title' => t('Expiration date'),
    'help' => t('The date on which this account will be disabled.'),
    'field' => [
      'id' => 'date',
    ],
    'filter' => [
      'id' => 'date',
    ],
    'sort' => [
      'id' => 'date',
    ],
  ];

  return $data;
}
