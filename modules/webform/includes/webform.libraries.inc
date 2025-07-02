<?php

/**
 * @file
 * Webform libraries.
 */

use Drupal\Core\Asset\AttachedAssetsInterface;
use Drupal\webform\Entity\Webform;
use Drupal\webform\Utility\WebformDialogHelper;

/**
 * Implements hook_library_info_build().
 */
function webform_library_info_build() {
  $base_path = base_path();
  // @todo Remove once Drupal 10.2.x is only supported.
  if (floatval(\Drupal::VERSION) < 10.2) {
    $default_query_string = \Drupal::state()->get('system.css_js_query_string') ?: '0';
  }
  else {
    $default_query_string = \Drupal::service('asset.query_string')->get();
  }

  /** @var \Drupal\webform\WebformInterface[] $webforms */
  $webforms = Webform::loadMultiple();
  $libraries = [];
  foreach ($webforms as $webform_id => $webform) {
    $assets = array_filter($webform->getAssets());
    foreach ($assets as $type => $value) {
      // Note:
      // Set 'type' to 'external' and manually build the CSS/JS file path
      // to prevent JS from being parsed by locale_js_alter()
      // @see locale_js_alter()
      // @see https://www.drupal.org/node/1803330
      $settings = ['type' => 'external', 'preprocess' => FALSE, 'minified' => FALSE];
      if ($type === 'css') {
        $libraries["webform.css.$webform_id"] = [
          'css' => ['theme' => ["{$base_path}webform/css/{$webform_id}/custom.css?{$default_query_string}" => $settings]],
        ];
      }
      else {
        $libraries["webform.javascript.$webform_id"] = [
          'js' => ["{$base_path}webform/javascript/{$webform_id}/custom.js?{$default_query_string}" => $settings],
        ];
      }
    }
  }
  return $libraries;
}

/**
 * Implements hook_library_info_alter().
 */
function webform_library_info_alter(&$libraries, $extension) {
  // Only alter modules that declare webform libraries.
  // @see hook_webform_libraries_info()
  if ($extension !== 'webform' && !\Drupal::moduleHandler()->hasImplementations('webform_libraries_info', $extension)) {
    return;
  }

  /** @var \Drupal\webform\WebformLibrariesManagerInterface $libraries_manager */
  $libraries_manager = \Drupal::service('webform.libraries_manager');

  // If old version of the progress tracker (< 2.0.7) is installed
  // use the progress-tracker.css in /docs instead of /src.
  if (isset($libraries['libraries.progress-tracker'])
    && $libraries_manager->exists('progress-tracker')
    && !file_exists($libraries_manager->find('progress-tracker') . '/src/styles/progress-tracker.css')) {
    $libraries['libraries.progress-tracker']['css']['component'] = [
      '/libraries/progress-tracker/docs/styles/progress-tracker.css' => [],
    ];
  }

  // If chosen_lib.module is installed, then update the dependency.
  if (\Drupal::moduleHandler()->moduleExists('chosen_lib')) {
    if (isset($libraries['webform.element.chosen'])) {
      $dependencies = &$libraries['webform.element.chosen']['dependencies'];
      foreach ($dependencies as $index => $dependency) {
        if ($dependency === 'webform/libraries.jquery.chosen') {
          $dependencies[$index] = 'chosen_lib/chosen';
          $dependencies[] = 'chosen_lib/chosen.css';
          break;
        }
      }
    }
  }

  // If select2.module is installed, then update the dependency.
  if (\Drupal::moduleHandler()->moduleExists('select2')) {
    if (isset($libraries['webform.element.select2'])) {
      $dependencies = &$libraries['webform.element.select2']['dependencies'];
      foreach ($dependencies as $index => $dependency) {
        if ($dependency === 'webform/libraries.jquery.select2') {
          $dependencies[$index] = 'select2/select2';
          break;
        }
      }
    }
  }

  // Map /library/* paths to CDN.
  // @see webform.libraries.yml.
  foreach ($libraries as $library_name => &$library) {
    // Remove excluded libraries.
    if ($libraries_manager->isExcluded($library_name)) {
      unset($libraries[$library_name]);
      continue;
    }
    // Skip libraries installed by other modules.
    if (isset($library['module'])) {
      continue;
    }

    if (!empty($library['dependencies'])) {
      // Remove excluded libraries from dependencies.
      foreach ($library['dependencies'] as $dependency_index => $dependency_name) {
        if ($libraries_manager->isExcluded($dependency_name)) {
          $library['dependencies'][$dependency_index] = NULL;
          $library['dependencies'] = array_filter($library['dependencies']);
        }
      }
    }

    // Handle CDN support.
    if (isset($library['cdn']) && isset($library['directory'])
      && !$libraries_manager->exists($library['directory'])) {
      _webform_library_info_alter_recursive($library, $library['cdn']);
    }
  }
}

/**
 * Recursive through a webform library.
 *
 * @param array $library
 *   A webform library defined in webform.libraries.yml.
 * @param array $cdn
 *   A associative array of library paths mapped to CDN URL.
 */
function _webform_library_info_alter_recursive(array &$library, array $cdn) {
  foreach ($library as $key => &$value) {
    // CSS and JS files and listed in associative arrays keyed via string.
    if (!is_string($key) || !is_array($value)) {
      continue;
    }

    // Ignore the CDN's associative array.
    if ($key === 'cdn') {
      continue;
    }

    // Replace the CDN sources (i.e. /library/*) with the CDN URL destination
    // (https://cdnjs.cloudflare.com/ajax/libs/*).
    foreach ($cdn as $source => $destination) {
      if (strpos($key, $source) === 0) {
        $uri = str_replace($source, $destination, $key);
        $library[$uri] = $value;
        $library[$uri]['type'] = 'external';
        unset($library[$key]);
        break;
      }
    }

    // Recurse downward to find nested libraries.
    _webform_library_info_alter_recursive($value, $cdn);
  }
}

/**
 * Implements hook_css_alter().
 */
function webform_css_alter(&$css, AttachedAssetsInterface $assets) {
  // Remove the off-canvas CSS reset for webform admin routes.
  // @see https://www.drupal.org/project/drupal/issues/2826722
  if (WebformDialogHelper::useOffCanvas()) {
    $has_webform_css = FALSE;
    foreach (array_keys($css) as $key) {
      if (str_contains($key, '/webform.admin.css')) {
        $has_webform_css = TRUE;
        break;
      }
    }
    if ($has_webform_css) {
      foreach ($css as $key => $item) {
        if (str_contains($key, '/off-canvas')) {
          unset($css[$key]);
        }
      }
    }
  }

  _webform_asset_alter($css, 'css');
}

/**
 * Implements hook_js_alter().
 */
function webform_js_alter(&$javascript, AttachedAssetsInterface $assets) {
  // Ensure codemirror.js always loads first with weight = -1.
  // This prevents "Uncaught ReferenceError: CodeMirror is not defined" errors.
  // If the CKEditor Codemirror module is installed, it will overwrite
  // the Webform module's codemirror.js weight.
  // @see ckeditor_codemirror/ckeditor_codemirror.libraries.yml
  // @see webform/webform.libraries.yml
  if (isset($javascript['libraries/codemirror/lib/codemirror.js'])) {
    $javascript['libraries/codemirror/lib/codemirror.js']['weight'] = -1;
  }

  _webform_asset_alter($javascript, 'javascript');
}

/**
 * Alter Webform CSS or JavaScript assets and make sure they appear last.
 *
 * @param array $items
 *   An array of all CSS or JavaScript being presented on the page.
 * @param string $type
 *   The type of asset being attached.
 *
 * @see hook_library_info_build()
 */
function _webform_asset_alter(array &$items, $type) {
  foreach ($items as $key => &$item) {
    if (strpos($key, "/webform/$type/") === 0) {
      $item['weight'] = 1000;
      $item['group'] = 1000;
    }
  }
}
