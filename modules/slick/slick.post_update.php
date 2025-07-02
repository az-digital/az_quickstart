<?php

/**
 * @file
 * Post update hooks for Slick.
 */

/**
 * Removed deprecated old skins registration settings.
 */
function slick_post_update_remove_old_skins_settings() {
  $config = \Drupal::configFactory()->getEditable('slick.settings');
  $config->clear('disable_old_skins');
  $config->save(TRUE);
}
