<?php

/**
 * @file
 * Post update functions for the externalauth module.
 */

use Drupal\Core\Config\FileStorage;
use Drupal\Core\Config\InstallStorage;
use Drupal\views\Entity\View;

/**
 * Imports new view for authmap entries.
 */
function externalauth_post_update_add_view_authmap() {
  if (\Drupal::moduleHandler()->moduleExists('views') && !View::load('authmap')) {
    $module_path = \Drupal::moduleHandler()->getModule('externalauth')->getPath();
    $file_storage = new FileStorage($module_path . '/' . InstallStorage::CONFIG_OPTIONAL_DIRECTORY);
    $view = \Drupal::entityTypeManager()->getStorage('view')->create($file_storage->read('views.view.authmap'));
    $view->save();
  }
}
