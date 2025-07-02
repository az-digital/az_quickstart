<?php

/**
 * @file
 * Google Tag updates once other modules have made their own updates.
 */

/**
 * Update Google GTM Advanced updates entity.
 */
function google_tag_post_update_move_advanced_settings() {
  $google_tags = \Drupal::entityTypeManager()->getStorage('google_tag_container')->loadMultiple();
  /** @var \Drupal\google_tag\Entity\TagContainer $tag_container */
  foreach ($google_tags as $tag_container) {
    $gtm_tags = $tag_container->getGtmIds();
    foreach ($gtm_tags as $tag) {
      $advanced_settings['gtm'][$tag] = $tag_container->getGtmSettings();
    }
    $tag_container->set('advanced_settings', $advanced_settings);
    $tag_container->save();
  }
}
