<?php

/**
 * @file
 * Contains views API hooks in the views_execution group for Flag module.
 */

use Drupal\views\ViewExecutable;

/**
 * Implements hook_views_query_substitutions().
 */
function flag_views_query_substitutions(ViewExecutable $view) {
  // Only act on views with flag relationships.
  if (!in_array('flagging', $view->getBaseTables())) {
    return [];
  }

  // Allow replacement of current user's session id so we can cache these
  // queries.
  $flag_service = \Drupal::service('flag');
  return [
    '***FLAG_CURRENT_USER_SID***' => $flag_service->getAnonymousSessionId(),
  ];
}
