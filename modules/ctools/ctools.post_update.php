<?php

/**
 * @file
 * ctools updates once other modules have made their own updates.
 */

/**
 * Invalidate the service container to force EntityBundleConstriant is Removed.
 */
function ctools_post_update_remove_entitybundleconstraint() {
  // Reload the service container.
  $kernel = \Drupal::service('kernel');
  $kernel->invalidateContainer();
}
