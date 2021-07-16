<?php

/**
 * @file
 * az_quickstart.profile
 */
use Drupal\Core\Config\FileStorage;

/**
 * Install az_metrics.
 */
function az_quickstart_update_9201() {
  \Drupal::service('module_installer')->install(['az_metrics']);
}

/**
 * Force import of user.settings config.
 */
function az_quickstart_update_9202() {
  // Set account creation rules, default to only admin can create accounts.
  $config = \Drupal::service('config.factory')->getEditable('user.settings');
  $config->setData([
    'register' => 'admin_only'
  ])
    ->save();
}
