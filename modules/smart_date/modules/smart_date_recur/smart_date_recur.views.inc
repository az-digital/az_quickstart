<?php

/**
 * @file
 * Provides views data for the smart_date_recur module.
 */

/**
 * Implements hook_field_views_data().
 */
function smart_date_recur_views_data_alter(array &$data) {
  $data['smart_date_rule']['freq']['filter']['id'] = 'recur_freq';
}
