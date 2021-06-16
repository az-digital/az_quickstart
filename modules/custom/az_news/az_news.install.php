<?php

/**
 * @file
 * az_news.install
 */

/**
 * Install az_paragraphs_photo_gallery.
 */
function az_news_update_9201() {
  \Drupal::service('module_installer')->install(['az_paragraphs_photo_gallery']);
}
