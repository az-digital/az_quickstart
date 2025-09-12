<?php

namespace Drupal\az_media_trellis;

/**
 * Provides various helper functions for the az_media_trellis module.
 *
 * This service handles context detection and utility functions for Trellis
 * form integration. The primary purpose is to determine when forms should
 * behave differently based on the current Drupal context (editing vs viewing).
 */
class AzMediaTrellisService {

  /**
   * Determines if the current context is an editing environment.
   *
   * This method checks various indicators to determine if the user is currently
   * in an editing context where form behavior should be modified. In editing
   * contexts, Trellis forms may have reduced validation requirements or
   * different display characteristics.
   *
   * Editing contexts include:
   * - Node add/edit forms
   * - Media library interfaces
   * - Inline media editing
   * - Content preview modes
   *
   * @return bool
   *   TRUE if the current context is considered an editing environment,
   *   FALSE if it's a standard viewing context.
   *
   * @see az_media_trellis_preprocess_media()
   * @see AzMediaRemoteTrellisFormatter::viewElements()
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
