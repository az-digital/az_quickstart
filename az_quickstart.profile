<?php

/**
 * @file
 * az_quickstart.profile
 */

/**
 * Install az_metrics.
 */
function az_quickstart_update_9201() {
  \Drupal::service('module_installer')->install(['az_metrics']);
}

/**
 * Set account creation rules, default to admin_only.
 */
function az_quickstart_update_9202() {
  $config = \Drupal::service('config.factory')->getEditable('user.settings');
  $config->set('register', 'admin_only')
    ->save(TRUE);
}

/**
 * Enable the role_delegation module.
 */
function az_quickstart_update_9203() {
  $module_list = ['role_delegation'];
  \Drupal::service('module_installer')->install($module_list);
}

/**
 * Save default barrio settings for new branding theme region and place sytem branding block in new region.
 */
function az_quickstart_update_9204() {
  $config = \Drupal::service('config.factory')->getEditable('az_barrio.settings');
  $config
    ->set('bootstrap_barrio_region_clean_branding', FALSE)
    ->set('bootstrap_barrio_region_class_branding', '')
    ->save(TRUE);

  $config = \Drupal::service('config.factory')->getEditable('block.block.az_barrio_branding');
  $config
    ->set('status', TRUE)
    ->set('region', 'branding')
    ->set('weight', -9)
    ->set('settings.use_site_name', FALSE)
    ->set('settings.use_site_slogan', FALSE)
    ->save(TRUE);
}
