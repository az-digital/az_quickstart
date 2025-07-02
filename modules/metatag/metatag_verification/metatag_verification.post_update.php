<?php

/**
 * @file
 * Post update functions for Metatag Verification.
 */

use Drupal\Core\Config\Entity\ConfigEntityUpdater;
use Drupal\metatag\Entity\MetatagDefaults;

/**
 * Fix regressions from when the duplicate "google" meta tag fixed.
 */
function metatag_verification_post_update_fix_google_tag_regression(&$sandbox) {
  $config_entity_updater = \Drupal::classResolver(ConfigEntityUpdater::class);
  $config_entity_updater->update($sandbox, 'metatag_defaults', function (MetatagDefaults $metatag_defaults) {
    if ($metatag_defaults->hasTag('google')) {
      $tags = $metatag_defaults->get('tags');

      // Don't do anything if the google_site_verification tag is already
      // defined.
      if (!empty($tags['google_site_verification'])) {
        return FALSE;
      }

      // Only change the data if the 'google' value is not one of the accepted
      // values for that tag.
      if (strpos($tags['google'], 'nositelinkssearchbox') === FALSE
          && strpos($tags['google'], 'nopagereadaloud') === FALSE
          && strpos($tags['google'], 'notranslate') === FALSE) {
        // Set the verification tag to the old value, delete the old value and
        // then update the record.
        $tags['google_site_verification'] = $tags['google'];
        unset($tags['google']);
        $metatag_defaults->set('tags', $tags);
        return TRUE;
      }
    }
    return FALSE;
  });
}
