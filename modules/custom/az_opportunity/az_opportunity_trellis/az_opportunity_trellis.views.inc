<?php

/**
 * @file
 * Provides views data for the az_opportunity_trellis module.
 */

/**
 * Implements hook_views_data().
 */
function az_opportunity_trellis_views_data(): array {
  $data = [];

  $data['az_opportunity_trellis_data']['table']['group'] = 'az_opportunity_trellis_data';
  $data['az_opportunity_trellis_data']['table']['base'] = [
    'title' => t('Trellis Opportunities'),
    'query_id' => 'views_remote_data_query',
  ];
  $data['az_opportunity_trellis_data']['Id'] = [
    'title' => t('Trellis Opportunity ID'),
    'help' => t('Unique key of Trellis opportunity.'),
    'field' => [
      'id' => 'az_opportunity_trellis_views_field',
    ],
  ];
  $data['az_opportunity_trellis_data']['Last_Modified_Date'] = [
    'title' => t('Trellis opportunity modification time'),
    'help' => t('Time of the Trellis opportunity was last modified'),
    'field' => [
      'id' => 'date',
    ],
  ];
  $data['az_opportunity_trellis_data']['Start_DateTime'] = [
    'title' => t('Trellis opportunity start time'),
    'help' => t('Start time of the Trellis opportunity.'),
    'field' => [
      'id' => 'date',
    ],
  ];
  $data['az_opportunity_trellis_data']['End_DateTime'] = [
    'title' => t('Trellis opportunity end time'),
    'help' => t('End time of the Trellis opportunity.'),
    'field' => [
      'id' => 'date',
    ],
  ];
  $data['az_opportunity_trellis_data']['Attributes'] = [
    'title' => t('Enterprise Attribute'),
    'help' => t('Trellis opportunity search API enterprise attributes'),
    'filter' => [
      'id' => 'az_opportunity_trellis_views_attribute_filter',
    ],
  ];
  $data['az_opportunity_trellis_data']['Approval'] = [
    'title' => t('Approval status'),
    'help' => t('Trellis opportunity search API approval status'),
    'filter' => [
      'id' => 'az_opportunity_trellis_views_approval_filter',
    ],
  ];
  $data['az_opportunity_trellis_data']['Date'] = [
    'title' => t('Trellis Date'),
    'help' => t('Trellis opportunity search API dates'),
    'filter' => [
      'id' => 'az_opportunity_trellis_views_date_filter',
    ],
  ];
  return $data;
}
