<?php

/**
 * @file
 * Drush integration for Access Unpublished.
 */

/**
 * Implements hook_sql_sync_sanitize().
 */
function access_unpublished_drush_sql_sync_sanitize() {
  $sql = "UPDATE access_token SET value='sanitized';";
  drush_sql_register_post_sync_op('access_unpublished', dt('Remove Access Unpublished tokens.'), $sql);
}
