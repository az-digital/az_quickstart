<?php

/**
 * @file
 * Provide views data for the PokeAPI.
 */

declare(strict_types=1);

/**
 * Implements hook_views_data().
 */
function views_remote_data_pokeapi_views_data(): array {
  $data = [];

  $data['views_remote_data_pokeapi']['table']['group'] = 'views_remote_data_pokeapi';
  $data['views_remote_data_pokeapi']['table']['entity type'] = 'node';
  $data['views_remote_data_pokeapi']['table']['entity revision'] = FALSE;
  $data['views_remote_data_pokeapi']['table']['base'] = [
    'title' => 'PokeAPI',
    'query_id' => 'views_remote_data_query',
  ];

  return $data;
}
