<?php

/**
 * @file
 * Post update functions for XML Sitemap Engines.
 */

/**
 * Force cache clear for new hook_entity_type_build().
 */
function xmlsitemap_engines_post_update_remove_bing() {
  $config = \Drupal::configFactory()->getEditable('xmlsitemap_engines.settings');
  $engines = $config->get('engines');
  if (in_array('bing', $engines, TRUE)) {
    $config->set('engines', array_diff($engines, ['bing']));
    $config->save();
  }
}
