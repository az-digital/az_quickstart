<?php

/**
 * @file
 * Post update functions for AZ Barrio.
 */

/**
 * Adds the az_bootstrap_cdn_version_css and az_bootstrap_cdn_version_js
 * settings to the az_barrio.settings configuration.
 */
function az_barrio_post_update_split_bootstrap_cdn_version(&$sandbox = NULL) {
  $config_factory = \Drupal::configFactory();
  $theme_settings = $config_factory->getEditable('az_barrio.settings');

  $old_version = $theme_settings->get('az_bootstrap_cdn_version');

  // Only apply migration if old value exists.
  if ($old_version !== NULL) {
    // Set new values if not already set.
    if ($theme_settings->get('az_bootstrap_cdn_version_css') === NULL) {
      $theme_settings->set('az_bootstrap_cdn_version_css', $old_version);
    }
    if ($theme_settings->get('az_bootstrap_cdn_version_js') === NULL) {
      $theme_settings->set('az_bootstrap_cdn_version_js', $old_version);
    }

    // Save all changes.
    $theme_settings->save();
  }
}
