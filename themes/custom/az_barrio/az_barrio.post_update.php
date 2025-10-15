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
 * Migrate Arizona Bootstrap 2.x versions to 5.x equivalents.
 */
function az_barrio_post_update_migrate_bootstrap_2x_to_5x(&$sandbox = NULL) {
  $config_factory = \Drupal::configFactory();
  $theme_settings = $config_factory->getEditable('az_barrio.settings');

  $updated = FALSE;

  // Migration mapping from 2.x to 5.x versions.
  $version_mapping = [
    'latest-2.x' => 'latest-5.x',
    '2.x' => '5.x',
  ];

  // Update CSS version if it's a 2.x version.
  $current_css_version = $theme_settings->get('az_bootstrap_cdn_version_css');
  if (isset($version_mapping[$current_css_version])) {
    $theme_settings->set('az_bootstrap_cdn_version_css', $version_mapping[$current_css_version]);
    $updated = TRUE;
  }

  // Update JS version if it's a 2.x version.
  $current_js_version = $theme_settings->get('az_bootstrap_cdn_version_js');
  if (isset($version_mapping[$current_js_version])) {
    $theme_settings->set('az_bootstrap_cdn_version_js', $version_mapping[$current_js_version]);
    $updated = TRUE;
  }

  // Save changes if any were made.
  if ($updated) {
    $theme_settings->save();
    \Drupal::logger('az_quickstart')->notice('Migrated Arizona Bootstrap 2.x versions to 5.x equivalents for Arizona Bootstrap 5 compatibility.');
  }
}

/**
 * Sets az_remove_sidebar_menu_mobile to TRUE if it does NOT exist.
 */
function az_barrio_post_update_set_sidebar_menu_mobile_setting(&$sandbox = NULL) {
  $config_factory = \Drupal::configFactory();
  $theme_settings = $config_factory->getEditable('az_barrio.settings');
  // Check for the existence of the new setting.
  $current_setting = $theme_settings->get('az_remove_sidebar_menu_mobile');
  // Only set the new setting if it does not exist (is NULL).
  if ($current_setting === NULL) {
    $theme_settings->set('az_remove_sidebar_menu_mobile', TRUE);
    $theme_settings->save();
    \Drupal::logger('az_quickstart')->notice('Created az_remove_sidebar_menu_mobile and set to TRUE during post update.');
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

/**
 * Updates theme settings for schema compliance.
 */
function az_barrio_post_update_theme_settings_schema(&$sandbox = NULL) {
  $config_factory = \Drupal::configFactory();
  $theme_settings = $config_factory->getEditable('az_barrio.settings');

  // Convert boolean values to proper types.
  $type_updates = [
    // Convert boolean false to integer 0, boolean true to integer 1.
    'bootstrap_barrio_enable_color' => FALSE,
    'bootstrap_barrio_image_fluid' => FALSE,
    'bootstrap_barrio_navbar_flyout' => FALSE,
    'bootstrap_barrio_navbar_slide' => FALSE,
    'bootstrap_barrio_button' => FALSE,
    'bootstrap_barrio_button_outline' => FALSE,
    'bootstrap_barrio_navbar_top_navbar' => FALSE,
    'bootstrap_barrio_navbar_top_affix' => FALSE,
    'bootstrap_barrio_navbar_affix' => FALSE,
    'bootstrap_barrio_sidebar_first_affix' => FALSE,
    'bootstrap_barrio_sidebar_second_affix' => FALSE,
    'bootstrap_barrio_hide_node_label' => FALSE,
    'bootstrap_barrio_table_hover' => FALSE,
    'bootstrap_barrio_bootstrap_icons' => FALSE,
    'footer_default_logo' => FALSE,
  ];

  foreach ($type_updates as $key) {
    $current_value = $theme_settings->get($key);
    if ($current_value !== NULL) {
      // Convert boolean false to integer 0, boolean true to integer 1.
      if (is_bool($current_value)) {
        $theme_settings->set($key, $current_value ? 1 : 0);
      }
    }
  }

  $theme_settings->save();

  return t('Updated theme settings for schema compliance.');
}

/**
 * Convert footer_default_logo from integer to boolean.
 */
function az_barrio_post_update_convert_footer_default_logo_to_boolean(&$sandbox = NULL) {
  $config_factory = \Drupal::configFactory();
  $theme_settings = $config_factory->getEditable('az_barrio.settings');

  $current_value = $theme_settings->get('footer_default_logo');
  if ($current_value !== NULL) {
    // Convert integer value to boolean (0 => FALSE, anything else => TRUE).
    $boolean_value = (bool) $current_value;
    $theme_settings->set('footer_default_logo', $boolean_value);
    $theme_settings->save();
  }

  return t('Converted footer_default_logo from integer to boolean.');
}

/**
 * Adds langcode to az_barrio.settings.yml if it was missing.
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

/**
 * Deletes obsolete az_barrio_navbar_offcanvas key if it exists.
 */
function az_barrio_post_update_delete_navbar_offcanvas_setting(&$sandbox = NULL) {
  $config_factory = \Drupal::configFactory();
  $theme_settings = $config_factory->getEditable('az_barrio.settings');

  // Delete the navbar offcanvas key if it exists.
  if ($theme_settings->get('az_barrio_navbar_offcanvas')) {
    $theme_settings
      ->clear('az_barrio_navbar_offcanvas')
      ->save();
    \Drupal::logger('az_quickstart')->notice('Deleted obsolete az_barrio_navbar_offcanvas configuration key during post update.');
  }
}

/**
 * Deletes obsolete az_barrio_navbar_offcanvas key if it exists.
 *
 * (New version to ensure the key is deleted. See az_quickstart #4927.)
 */
function az_barrio_post_update_delete_navbar_offcanvas_setting_fix_2(&$sandbox = NULL) {
  $config_factory = \Drupal::configFactory();
  $theme_settings = $config_factory->getEditable('az_barrio.settings');

  if ($theme_settings->get('az_barrio_navbar_offcanvas') !== NULL) {
    $theme_settings
      ->clear('az_barrio_navbar_offcanvas')
      ->save();
    \Drupal::logger('az_quickstart')->notice('Deleted obsolete az_barrio_navbar_offcanvas configuration key during post update.');
  }
}
