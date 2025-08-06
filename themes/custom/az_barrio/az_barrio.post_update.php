<?php

/**
 * @file
 * Post update functions for AZ Barrio.
 */

/**
 * Adds az_bootstrap_cdn_version_css and az_bootstrap_cdn_version_js settings.
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

/**
 * Updates theme settings for improved schema compliance.
 *
 * Updates data types, and reorganizes configuration
 * to match the updated schema.
 */
function az_barrio_post_update_update_theme_settings_schema(&$sandbox = NULL) {
  $config_factory = \Drupal::configFactory();
  $theme_settings = $config_factory->getEditable('az_barrio.settings');
  // Update data types for schema compliance.
  $type_updates = [
    // Convert boolean values to proper types.
    'bootstrap_barrio_enable_color' => FALSE,
    'bootstrap_barrio_image_fluid' => FALSE,
    'bootstrap_barrio_navbar_flyout' => FALSE,
    'bootstrap_barrio_navbar_slide' => FALSE,

    // Convert integer values.
    'bootstrap_barrio_sidebar_collapse' => 0,
    'bootstrap_barrio_hide_node_label' => 0,
    'bootstrap_barrio_sidebar_first_affix' => 0,
    'bootstrap_barrio_sidebar_second_affix' => 0,
    'bootstrap_barrio_messages_widget_toast_delay' => 0,
    'footer_default_logo' => 0,

    // Convert string values.
    'bootstrap_barrio_float_label' => '',
    'bootstrap_barrio_navbar_offcanvas' => '',
  ];

  foreach ($type_updates as $key => $value) {
    $current_value = $theme_settings->get($key);
    if ($current_value !== NULL) {
      $theme_settings->set($key, $value);
    }
  }

  // Update region clean settings to use integers instead of booleans.
  $region_clean_settings = [
    'bootstrap_barrio_region_clean_header',
    'bootstrap_barrio_region_clean_help',
    'bootstrap_barrio_region_clean_sidebar_first',
    'bootstrap_barrio_region_clean_sidebar_second',
    'bootstrap_barrio_region_clean_highlighted',
    'bootstrap_barrio_region_clean_breadcrumb',
    'bootstrap_barrio_region_clean_content',
  ];

  foreach ($region_clean_settings as $key) {
    $current_value = $theme_settings->get($key);
    if ($current_value !== NULL) {
      // Convert boolean false to integer 0, boolean true to integer 1.
      $theme_settings->set($key, $current_value ? 1 : 0);
    }
  }

  $theme_settings->save();

  return t('Updated theme settings for schema compliance.');
}
