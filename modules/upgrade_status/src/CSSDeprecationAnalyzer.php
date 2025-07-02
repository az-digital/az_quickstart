<?php

declare(strict_types=1);

namespace Drupal\upgrade_status;

use Drupal\Core\Extension\Extension;
use Drupal\Core\Site\Settings;

/**
 * The route deprecation analyzer.
 */
final class CSSDeprecationAnalyzer {

  /**
    * Analyzes usages of deprecated CSS selectors in an extension.
   *
   * @param \Drupal\Core\Extension\Extension $extension
   *  The extension to be analyzed.
   *
   * @return \Drupal\upgrade_status\DeprecationMessage[]
   *   A list of deprecation messages.
   *
   * @throws \Exception
   */
  public function analyze(Extension $extension): array {
    $deprecations = [];
    $css_files = $this->getAllCSSFiles(DRUPAL_ROOT . '/' . $extension->getPath());
    foreach ($css_files as $css_file) {
      $content = file_get_contents($css_file);
      // Remove valid selectors for this check.
      $content = str_replace('#drupal-off-canvas:not(.drupal-off-canvas-reset)', 'removed', $content);
      $content = str_replace('#drupal-off-canvas-wrapper', 'removed', $content);
      if (strpos($content, '#drupal-off-canvas')) {
        $deprecations[] = new DeprecationMessage('The #drupal-off-canvas selector is deprecated in drupal:9.5.0 and is removed from drupal:10.0.0. See https://www.drupal.org/node/3305664.', $css_file, 0, 'CSSDeprecationAnalyzer');
      }
    }
    return $deprecations;
  }

  /**
   * Finds all .css files for non-test extensions under a path.
   *
   * @param string $path
   *   Base path to find all .css files in.
   *
   * @return array
   *   A list of paths to .css files found under the base path.
   */
  private function getAllCSSFiles(string $path) {
    $files = [];
    $ignore_directories = Settings::get('file_scan_ignore_directories', ['bower_components', 'node_modules']);
    foreach(array_filter(glob($path . '/*.css'), 'is_file') as $file) {
      foreach ($ignore_directories as $ignore_directory) {
        if (strpos($file, '/' . $ignore_directory . '/')) {
          continue 2;
        }
      }
      $files[] = $file;
    }
    foreach (glob($path . '/*', GLOB_ONLYDIR|GLOB_NOSORT) as $dir) {
      foreach ($ignore_directories as $ignore_directory) {
        if (strpos($dir, '/' . $ignore_directory . '/')) {
          continue 2;
        }
      }
      $files = array_merge($files, $this->getAllCSSFiles($dir));
    }
    return $files;
  }

}
