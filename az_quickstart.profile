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
