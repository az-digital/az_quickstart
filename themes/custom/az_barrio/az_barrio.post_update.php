<?php

/**
 * @file
 * Post update functions for AZ Barrio.
 */

/**
 * Migrates az_bootstrap_cdn_version to separate CSS/JS keys and removes it.
 */
function az_barrio_post_update_split_bootstrap_cdn_version(&$sandbox = NULL) {
  $config_factory = \Drupal::configFactory();
  $theme_settings = $config_factory->getEditable('az_barrio.settings');

  $old_version = $theme_settings->get('az_bootstrap_cdn_version');

  // Only apply migration if old value exists.
  if ($old_version !== NULL) {
    // Set new values if not already set.
    if ($theme_settings->get('az_bootstrap_css_cdn_version') === NULL) {
      $theme_settings->set('az_bootstrap_css_cdn_version', $old_version);
    }
    if ($theme_settings->get('az_bootstrap_js_cdn_version') === NULL) {
      $theme_settings->set('az_bootstrap_js_cdn_version', $old_version);
    }

    // Remove old value.
    $theme_settings->clear('az_bootstrap_cdn_version');

    // Save all changes.
    $theme_settings->save();
  }
}
