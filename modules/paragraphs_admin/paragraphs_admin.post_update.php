<?php

/**
 * @file
 * Post update functions for Paragraphs Admin.
 */

/**
 * Update paragrsphs admin view permission.
 */
function paragraphs_admin_post_update_paragrsphs_admin_view_permission() {
  $config_factory = \Drupal::configFactory();
  $config = $config_factory->getEditable('views.view.paragraphs');
  $current_perm = $config->get('display.default.display_options.access.options.perm');
  if ($current_perm === 'access content') {
    $config->set('display.default.display_options.access.options.perm', 'administer paragraphs')
      ->save(TRUE);
  }
}
