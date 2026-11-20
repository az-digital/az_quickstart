<?php

/**
 * @file
 * Provides views data for the az_event_trellis module.
 */

/**
 * Implements hook_views_data().
 */
function az_event_trellis_views_data(): array {
  $data = [];

  $data['az_event_trellis_data']['table']['group'] = 'az_event_trellis_data';
  $data['az_event_trellis_data']['table']['base'] = [
    'title' => t('Trellis Events'),
    'query_id' => 'views_remote_data_query',
  ];
  $data['az_event_trellis_data']['Id'] = [
    'title' => t('Trellis Event ID'),
    'help' => t('Unique key of Trellis event.'),
    'field' => [
      'id' => 'az_event_trellis_views_field',
    ],
  ];
  $data['az_event_trellis_data']['Last_Modified_Date'] = [
    'title' => t('Trellis event modification time'),
    'help' => t('Time of the Trellis event was last modified'),
    'field' => [
      'id' => 'date',
    ],
  ];
  $data['az_event_trellis_data']['Start_DateTime'] = [
    'title' => t('Trellis event start time'),
    'help' => t('Start time of the Trellis event.'),
    'field' => [
      'id' => 'date',
    ],
  ];
  $data['az_event_trellis_data']['End_DateTime'] = [
    'title' => t('Trellis event end time'),
    'help' => t('End time of the Trellis event.'),
    'field' => [
      'id' => 'date',
    ],
  ];
  $data['az_event_trellis_data']['Attributes'] = [
    'title' => t('Enterprise Attribute'),
    'help' => t('Trellis event search API enterprise attributes'),
    'filter' => [
      'id' => 'az_event_trellis_views_attribute_filter',
    ],
  ];
  $data['az_event_trellis_data']['Approval'] = [
    'title' => t('Approval status'),
    'help' => t('Trellis event search API approval status'),
    'filter' => [
      'id' => 'az_event_trellis_views_approval_filter',
    ],
  ];
  $data['az_event_trellis_data']['Date'] = [
    'title' => t('Trellis Date'),
    'help' => t('Trellis event search API dates'),
    'filter' => [
      'id' => 'az_event_trellis_views_date_filter',
    ],
  ];
  return $data;
}
