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
   * Add the AZ Bootstrap CSS location to the CKEditor5 stylesheets.
   */
  #[Hook('system_info_alter')]
  public function systemInfoAlter(array &$info, Extension $file, string $type): void {
    if ($type !== 'theme' || $file->getName() !== 'az_barrio') {
      return;
    }

    // Get the AZ Bootstrap CSS location. The state key should match the
    // AZ_BOOTSTRAP_LOCATION constant defined in az_barrio/includes/common.inc.
    $az_bootstrap_css_location = \Drupal::state()->get('az_bootstrap_location');
    dpm($az_bootstrap_css_location, 'AZ Bootstrap CSS Location fetched in systemInfoAlter()');
    if (!is_string($az_bootstrap_css_location) || $az_bootstrap_css_location === '') {
      return;
    }
    $stylesheets = $info['ckeditor5-stylesheets'] ?? [];
    $stylesheets = is_array($stylesheets) ? $stylesheets : [];
    $stylesheets = array_filter(
      $stylesheets,
      static fn ($stylesheet): bool => is_string($stylesheet)
        && !str_contains($stylesheet, 'arizona-bootstrap')
    );
    $stylesheets[] = $az_bootstrap_css_location;
    $info['ckeditor5-stylesheets'] = array_values(array_unique($stylesheets));
  }

}
