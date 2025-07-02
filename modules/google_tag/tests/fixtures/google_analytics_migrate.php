<?php

/**
 * @file
 * A database agnostic dump for testing purposes.
 */

use Drupal\Core\Database\Database;
use Symfony\Component\Yaml\Yaml;

$database = Database::getConnection();

$database
  ->insert('config')
  ->fields([
    'name',
    'data',
  ])
  ->values([
    'google_analytics.settings',
    get_google_analytics_settings_config(),
  ])->execute();

/**
 * Returns google tag settings config string.
 *
 * @return string
 *   Config string.
 */
function get_google_analytics_settings_config(): string {
  $request_path = '/admin\n/admin/*\n/batch\n/node/add*\n/node/*/*\n/user/*/*';
  $default_settings = "_core:
  default_config_hash: dwMYPgAnj9KBO77SLEv9Z42NDJAbuxe0uU9eGC8qw3M
account: 'G-ABCD1A2B3C,G-ABCD1A2B3D'
domain_mode: 0
cross_domains: ''
visibility:
  request_path_mode: 0
  request_path_pages: $request_path
  user_role_mode: 0
  user_role_roles:
    authenticated: authenticated
    content_editor: content_editor
  user_account_mode: 1
track:
  outbound: true
  mailto: true
  tel: true
  files: true
  files_extensions: '7z|aac|arc|arj|asf|asx|avi|bin|csv|doc(x|m)?|dot(x|m)?|exe|flv|gif|gz|gzip|hqx|jar|jpe?g|js|mp(2|3|4|e?g)|mov(ie)?|msi|msp|pdf|phps|png|ppt(x|m)?|pot(x|m)?|pps(x|m)?|ppam|sld(x|m)?|thmx|qtm?|ra(m|r)?|sea|sit|tar|tgz|torrent|txt|wav|wma|wmv|wpd|xls(x|m|b)?|xlt(x|m)|xlam|xml|z|zip'
  colorbox: true
  linkid: false
  urlfragments: false
  userid: false
  messages: {  }
  site_search: false
  adsense: false
  displayfeatures: true
privacy:
  anonymizeip: true
custom:
  parameters:
    dimension1:
      type: dimension
      name: x-axis
      value: '100'
    dimension2:
      type: dimension
      name: y-axis
      value: '150'
    metric1:
      type: metric
      name: accuracy
      value: '85'
    metric2:
      type: metric
      name: precision
      value: '90'
codesnippet:
  create: {  }
  before: ''
  after: ''
translation_set: false
cache: false
debug: false
";
  return serialize(Yaml::parse($default_settings));
}
