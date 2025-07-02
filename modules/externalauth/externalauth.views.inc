<?php

/**
 * @file
 * Views integration.
 */

/**
 * Implements hook_views_data().
 */
function externalauth_views_data(): array {
  $data = [];

  $data['authmap'] = [
    // Table.
    'table' => [
      'group' => t('External Authentication Map'),
      'join' => [
        'users_field_data' => [
          'left_field' => 'uid',
          'field' => 'uid',
        ],
      ],
      'base' => [
        'field' => 'uid',
        'title' => t('External Authentication Map'),
      ],
    ],
    'provider_field' => [
      'real field' => 'provider',
      'title' => t('Provider'),
      'help' => t('The name of the authentication provider providing the authname.'),
      'field' => [
        'id' => 'standard',
      ],
      'sort' => [
        'id' => 'standard',
      ],
      'filter' => [
        'id' => 'string',
      ],
      'argument' => [
        'id' => 'string',
      ],
    ],
    'authname' => [
      'title' => t('Authname'),
      'help' => t('Unique authentication name provided by authentication provider.'),
      'field' => [
        'id' => 'standard',
      ],
      'sort' => [
        'id' => 'standard',
      ],
      'filter' => [
        'id' => 'string',
      ],
      'argument' => [
        'id' => 'string',
      ],
    ],
    'uid' => [
      'title' => t('Drupal User ID'),
      'help' => t('The user linked to the authname.'),
      'relationship' => [
        'base' => 'users_field_data',
        'base field' => 'uid',
        'id' => 'standard',
        'label' => t('Linked Drupal user'),
      ],
      'field' => [
        'id' => 'numeric',
      ],
      'filter' => [
        'id' => 'numeric',
      ],
      'sort' => [
        'id' => 'standard',
      ],
    ],
    'delete_link' => [
      'title' => t('Link to delete @label entry', ['@label' => 'authmap']),
      'help' => t('Provide a link to delete the @label entry.', ['@label' => 'authmap']),
      'field' => [
        'id' => 'authmap_link_delete',
        'additional fields' => ['uid', 'provider'],
      ],
    ],
  ];

  return $data;
}
