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
 *  Set account creation rules, default to admin_only.
 */
function az_quickstart_update_9202() {
  $config = \Drupal::service('config.factory')->getEditable('user.settings');
  $config->setData(['register' => 'admin_only'])
    ->save();
}