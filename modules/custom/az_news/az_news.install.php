<?php

/**
 * @file
 * Install, update and uninstall functions for az_news module.
 */

/**
 * Install az_paragraphs_photo_gallery.
 */
function az_news_update_9201() {
  \Drupal::service('module_installer')->install(['az_paragraphs_photo_gallery']);
}
