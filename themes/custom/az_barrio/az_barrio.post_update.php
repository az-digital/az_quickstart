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
 * Migrate Material Design Sharp icons setting to Material Symbols Rounded.
 */
function az_barrio_post_update_migrate_material_symbols_icons(&$sandbox = NULL) {
  $config_factory = \Drupal::configFactory();
  $theme_settings = $config_factory->getEditable('az_barrio.settings');

  // Check if the old material design sharp icons setting is enabled.
  $old_material_icons = $theme_settings->get('az_barrio_material_design_sharp_icons');

  // Only migrate if the old setting was explicitly enabled.
  if ($old_material_icons === TRUE) {
    // Enable the new Material Symbols Rounded setting.
    $theme_settings->set('az_barrio_material_symbols_rounded', TRUE);

    // Disable the old setting.
    $theme_settings->set('az_barrio_material_design_sharp_icons', FALSE);

    // Save the changes.
    $theme_settings->save();

    \Drupal::logger('az_quickstart')->notice('Migrated Material Design Sharp icons setting to Material Symbols Rounded.');
  }
}

/**
 * Convert boolean values to integers for bootstrap_barrio settings.
 */
function az_barrio_post_update_convert_boolean_to_integer_settings(&$sandbox = NULL) {
  $config_factory = \Drupal::configFactory();
  $theme_settings = $config_factory->getEditable('az_barrio.settings');

  // Parent theme expects these as integers (0/1) not booleans
  // Only convert settings that are defined in the parent schema.
  $boolean_to_integer_settings = [
    'bootstrap_barrio_region_clean_header',
    'bootstrap_barrio_region_clean_sidebar_first',
    'bootstrap_barrio_region_clean_sidebar_second',
    'bootstrap_barrio_region_clean_highlighted',
    'bootstrap_barrio_region_clean_breadcrumb',
    'bootstrap_barrio_region_clean_content',
  ];

  $converted_count = 0;
  foreach ($boolean_to_integer_settings as $setting) {
    $value = $theme_settings->get($setting);
    if (is_bool($value)) {
      $theme_settings->set($setting, $value ? 1 : 0);
      $converted_count++;
    }
  }
  if ($converted_count > 0) {
    $theme_settings->save();
    \Drupal::logger('az_quickstart')->notice('Converted @count boolean values to integers for bootstrap_barrio parent theme settings.', ['@count' => $converted_count]);
  }
}
