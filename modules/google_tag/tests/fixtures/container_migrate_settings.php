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
    'google_tag.settings',
    get_google_tag_settings_config(),
  ])->execute();

/**
 * Returns google tag settings config string.
 *
 * @return string
 *   Config string.
 */
function get_google_tag_settings_config(): string {
  $default_settings = "_core:
  default_config_hash: RcnOtpw-9uV9JCrp6vO2_Xk1O_YjLqUCczGUBadQXoc
uri: 'public:/'
compact_snippet: true
include_file: true
rebuild_snippets: false
flush_snippets: false
debug_output: false
_default_container:
  container_id: ''
  path_toggle: 'exclude listed'
  path_list: |-
    /admin
    /admin/*
    /batch
    /batch/*
    /node/add*
    /node/*/edit
    /node/*/delete
    /node/*/layout
    /taxonomy/term/*/edit
    /taxonomy/term/*/layout
    /user/*/edit*
    /user/*/cancel*
    /user/*/layout
  role_toggle: 'exclude listed'
  role_list: {  }
  status_toggle: 'exclude listed'
  status_list: |-
    403
    404
  data_layer: dataLayer
  include_classes: false
  whitelist_classes: |-
    google
    nonGooglePixels
    nonGoogleScripts
    nonGoogleIframes
  blacklist_classes: |-
    customScripts
    customPixels
  include_environment: false
  environment_id: ''
  environment_token: ''
";
  return serialize(Yaml::parse($default_settings));
}
