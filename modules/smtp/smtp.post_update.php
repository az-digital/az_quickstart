<?php

/**
 * @file
 * Post update functions for Smtp.
 */

/**
 * Rebuild caches to ensure services changes are read in.
 */
function smtp_post_update_connection_tester() {
  // Empty update to cause a cache rebuild so that the service changes are read.
}

/**
 * Add SMTP timeout configuration and change default to 30.
 */
function smtp_post_update_set_smtp_timeout() {
  \Drupal::configFactory()->getEditable('smtp.settings')
    ->set('smtp_timeout', 30)
    ->save(TRUE);
}

/**
 * Add SMTP keepalive configuration and set default to FALSE.
 */
function smtp_post_update_set_smtp_keepalive() {
  \Drupal::configFactory()->getEditable('smtp.settings')
    ->set('smtp_keepalive', FALSE)
    ->save(TRUE);
}

/**
 * Add SMTP Auto TLS configuration and set default to TRUE.
 */
function smtp_post_update_set_smtp_autotls() {
  \Drupal::configFactory()->getEditable('smtp.settings')
    ->set('smtp_autotls', TRUE)
    ->save(TRUE);
}

/**
 * Rebuild caches to ensure the connection typo service change is updated.
 */
function smtp_post_update_connection_typo() {
  // Empty update to cause a cache rebuild so that the service changes are read.
  // Caused by this typo: https://www.drupal.org/project/smtp/issues/3150369
}
