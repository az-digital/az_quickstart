<?php

namespace Drupal\az_core\Hook;

use Drupal\Core\Extension\Extension;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for az_core.
 */
class AZCoreHooks {

  /**
   * Implements hook_system_info_alter().
   *
   * Adds the AZ Bootstrap CSS location to the CKEditor5 stylesheets for the
   * AZ Barrio theme and any of its subthemes.
   */
  #[Hook('system_info_alter')]
  public function systemInfoAlter(array &$info, Extension $file, string $type): void {
    if ($type !== 'theme') {
      return;
    }

    $theme_name = $file->getName();
    if ($theme_name !== 'az_barrio') {
      // Check if the theme is a subtheme of az_barrio.
      $base_theme = $info['base theme'] ?? NULL;
      if ($base_theme === NULL) {
        return;
      }
      $themes = \Drupal::service('theme_handler')->listInfo();
      while ($theme_name !== 'az_barrio' && $base_theme !== NULL) {
        $theme_name = $base_theme;
        $base_theme = $themes[$base_theme]->base_theme ?? NULL;
      }
      if ($theme_name !== 'az_barrio') {
        return;
      }
    }

    // Get the AZ Bootstrap CSS location. The state key must match the
    // AZ_BOOTSTRAP_LOCATION constant defined in az_barrio/includes/common.inc.
    $az_bootstrap_css_location = \Drupal::state()->get('az_bootstrap_location');
    if (is_string($az_bootstrap_css_location)
      && $az_bootstrap_css_location !== ''
      && array_key_exists('ckeditor5-stylesheets', $info)) {
      $info['ckeditor5-stylesheets'][] = $az_bootstrap_css_location;
    }
  }

}
