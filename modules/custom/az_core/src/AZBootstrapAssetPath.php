<?php

namespace Drupal\az_core;

/**
 * Builds Arizona Bootstrap asset paths.
 */
class AZBootstrapAssetPath {

  /**
   * Gets the Arizona Bootstrap asset path.
   *
   * @param string $type
   *   The asset type to load, e.g. css or js.
   * @param array $settings
   *   Optional theme settings override values.
   *
   * @return string
   *   The resolved asset URL or path.
   */
  public function getAssetPath(string $type, array $settings = []): string {
    $theme_settings = \Drupal::service('Drupal\Core\Extension\ThemeSettingsProvider');
    $source = $settings['az_bootstrap_source'] ?? $theme_settings->getSetting('az_bootstrap_source');
    $version = $settings['az_bootstrap_cdn_version_' . $type] ?? $theme_settings->getSetting('az_bootstrap_cdn_version_' . $type);
    $stable_version = $settings['az_bootstrap_cdn_stable_version'] ?? \Drupal::state()->get('az_bootstrap_cdn_stable_version');
    $minified = $settings['az_bootstrap_minified'] ?? $theme_settings->getSetting('az_bootstrap_minified');

    if ($source === 'cdn') {
      if ($version === 'stable') {
        $version = $stable_version;
      }
      $path = 'https://cdn.digital.arizona.edu/lib/arizona-bootstrap/' . $version;
    }
    else {
      $path = base_path() . 'libraries/arizona-bootstrap';
    }

    $path .= '/' . $type . '/arizona-bootstrap';
    if ($type === 'js') {
      $path .= '.bundle';
    }
    if ($minified) {
      $path .= '.min';
    }

    $path .= '.' . $type;

    if ($source === 'cdn' && $version !== 'stable') {
      $query_string = \Drupal::state()->get('asset.css_js_query_string') ?: '0';
      $path .= '?=' . $query_string;
    }

    return $path;
  }

}
