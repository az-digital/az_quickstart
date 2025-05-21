<?php

namespace Drupal\az_media_trellis;

/**
 * Provides various helper functions for the az_media_trellis module.
 */
class AzMediaTrellisService {

  /**
   * Returns TRUE if on an "editing" route, FALSE otherwise.
   */
  public static function isEditingContext() {
    // Check if the current route is an editing context.
    $route_name = \Drupal::routeMatch()->getRouteName();
    return in_array($route_name, [
      // Node edit form.
      'entity.node.edit_form',
      // Node add form.
      'entity.node.add_form',
      // Media library.
      'media_library.ui',
      // When editing the media inline?
      'media.filter.preview',
    ]);
  }

}
