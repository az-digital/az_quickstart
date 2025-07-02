<?php

/**
 * @file
 * Post update functions for Honeypot.
 */

/**
 * Implements hook_removed_post_updates().
 */
function honeypot_removed_post_updates() {
  return [
    'honeypot_post_update_joyride_location_to_position' => '2.2.0',
    'honeypot_post_update_rebuild_service_container' => '2.2.0',
  ];
}
