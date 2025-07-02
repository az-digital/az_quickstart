<?php

/**
 * @file
 * Access unpublished post update hooks.
 */

/**
 * Set a default value for modify_http_headers.
 */
function access_unpublished_post_update_initialize_modify_http_headers_config(&$sandbox = NULL) {
  \Drupal::configFactory()->getEditable('access_unpublished.settings')
    ->set('modify_http_headers', [])
    ->save();
}
