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
 * Convert boolean theme settings to proper boolean types.
 */
function az_barrio_post_update_convert_boolean_settings(&$sandbox = NULL) {
  $config_factory = \Drupal::configFactory();
  $theme_settings = $config_factory->getEditable('az_barrio.settings');
  
  // Convert boolean values to proper types.
  $boolean_updates = [
    'bootstrap_barrio_enable_color' => FALSE,
    'bootstrap_barrio_image_fluid' => FALSE,
    'bootstrap_barrio_navbar_flyout' => FALSE,
    'bootstrap_barrio_navbar_slide' => FALSE,
    'footer_default_logo' => TRUE,
  ];

  foreach ($boolean_updates as $key => $default_value) {
    $current_value = $theme_settings->get($key);
    if ($current_value !== NULL) {
      // Convert existing value to proper boolean type.
      $theme_settings->set($key, (bool) $current_value);
    } else {
      // Set default value if setting doesn't exist.
      $theme_settings->set($key, $default_value);
    }
  }

  $theme_settings->save();
  return t('Converted boolean theme settings to proper boolean types.');
}

/**
 * Convert integer theme settings to proper integer types.
 */
function az_barrio_post_update_convert_integer_settings(&$sandbox = NULL) {
  $config_factory = \Drupal::configFactory();
  $theme_settings = $config_factory->getEditable('az_barrio.settings');
  
  // Convert integer values to proper types.
  $integer_updates = [
    'bootstrap_barrio_sidebar_collapse' => 0,
    'bootstrap_barrio_hide_node_label' => 0,
    'bootstrap_barrio_sidebar_first_affix' => 0,
    'bootstrap_barrio_sidebar_second_affix' => 0,
    'bootstrap_barrio_messages_widget_toast_delay' => 0,
  ];

  foreach ($integer_updates as $key => $default_value) {
    $current_value = $theme_settings->get($key);
    if ($current_value !== NULL) {
      // Convert existing value to proper integer type.
      $theme_settings->set($key, (int) $current_value);
    } else {
      // Set default value if setting doesn't exist.
      $theme_settings->set($key, $default_value);
    }
  }

  $theme_settings->save();
  return t('Converted integer theme settings to proper integer types.');
}

/**
 * Convert string theme settings to proper string types.
 */
function az_barrio_post_update_convert_string_settings(&$sandbox = NULL) {
  $config_factory = \Drupal::configFactory();
  $theme_settings = $config_factory->getEditable('az_barrio.settings');
  
  // Convert string values to proper types.
  $string_updates = [
    'bootstrap_barrio_float_label' => '',
    'bootstrap_barrio_navbar_offcanvas' => '',
  ];

  foreach ($string_updates as $key => $default_value) {
    $current_value = $theme_settings->get($key);
    if ($current_value !== NULL) {
      // Convert existing value to proper string type.
      $theme_settings->set($key, (string) $current_value);
    } else {
      // Set default value if setting doesn't exist.
      $theme_settings->set($key, $default_value);
    }
  }

  $theme_settings->save();
  return t('Converted string theme settings to proper string types.');
}

/**
 * Convert region clean settings from boolean to integer types.
 */
function az_barrio_post_update_convert_region_clean_settings(&$sandbox = NULL) {
  $config_factory = \Drupal::configFactory();
  $theme_settings = $config_factory->getEditable('az_barrio.settings');
  
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
  return t('Converted region clean settings from boolean to integer types.');
}

/**
 * Add langcode to az_barrio.settings.yml.
 */
function az_barrio_post_update_add_langcode_to_settings(&$sandbox = NULL) {
  $config_factory = \Drupal::configFactory();
  $theme_settings = $config_factory->getEditable('az_barrio.settings');

  // Check if langcode is already set.
  if ($theme_settings->get('langcode') === NULL) {
    // Set default langcode to 'en'.
    $theme_settings->set('langcode', 'en');
    $theme_settings->save();
  }

  return t('Added langcode to az_barrio.settings.yml if it was missing.');
}
