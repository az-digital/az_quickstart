<?php

namespace Drupal\az_core\Hook;

use Drupal\Core\Block\BlockPluginInterface;
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
   * AZ Barrio theme. Subthemes of AZ Barrio will also use the updated
   * CKEditor5 stylesheets property.
   */
  #[Hook('system_info_alter')]
  public function systemInfoAlter(array &$info, Extension $file, string $type): void {
    if ($type !== 'theme' || $file->getName() !== 'az_barrio') {
      return;
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

  /**
   * Implements hook_block_build_BASE_BLOCK_ID_alter().
   *
   * Adds a cache key to identify search form blocks in navigation_offcanvas.
   */
  #[Hook('block_build_search_form_block_alter')]
  public function blockBuildSearchFormBlockAlter(array &$build, BlockPluginInterface $block): void {
    $build['#cache']['keys'][] = 'search_form_block';
  }

}
