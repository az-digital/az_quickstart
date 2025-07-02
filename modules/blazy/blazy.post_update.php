<?php

/**
 * @file
 * Post update hooks for Blazy.
 */

/**
 * Removed deprecated settings.
 */
function blazy_post_update_remove_deprecated_settings() {
  $config = \Drupal::configFactory()->getEditable('blazy.settings');
  foreach (['deprecated_class', 'responsive_image', 'use_theme_blazy'] as $key) {
    $config->clear($key);
  }
  $config->save(TRUE);
}

/**
 * Added max region count setting for Blazy layout.
 */
function blazy_post_update_added_max_region_count() {
  $config = \Drupal::configFactory()->getEditable('blazy.settings');
  $count = (int) $config->get('max_region_count');
  if ($count < 1) {
    $config->set('max_region_count', 0);
    $config->save(TRUE);
  }
}
