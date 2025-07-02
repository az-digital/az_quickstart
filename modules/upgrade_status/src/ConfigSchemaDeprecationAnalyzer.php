<?php

declare(strict_types=1);

namespace Drupal\upgrade_status;

use Drupal\Core\Extension\Extension;

/**
 * Config schema deprecation analyzer.
 */
final class ConfigSchemaDeprecationAnalyzer {

  /**
   * Analyzes usages of deprecated config schema elements in an extension.
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

    $project_dir = DRUPAL_ROOT . '/' . $extension->getPath();
    $config_files = $this->getViewsConfigFiles($project_dir);
    foreach ($config_files as $config_file) {
      $error_path = str_replace(DRUPAL_ROOT . '/', '', $config_file);
      $file_contents = file_get_contents($config_file);
      if (($line = $this->findKeyLine('default_argument_skip_url:', $file_contents)) !== 1) {
        $deprecations[] = new DeprecationMessage("Support from all Views contextual filter settings for the default_argument_skip_url setting is removed from drupal:11.0.0. No replacement is provided. See https://www.drupal.org/node/3382316.", $error_path, $line, 'ConfigSchemaDeprecationAnalyzer');
      }
    }
    return $deprecations;
  }

  /**
   * Finds all views config files for extensions under a path.
   *
   * @param string $path
   *   Base path to find all views config files in.
   *
   * @return array
   *   A list of paths to views config files found under the base path.
   */
  private function getViewsConfigFiles(string $path) {
    $files = [];
    foreach(glob($path . '/views.view.*.yml') as $file) {
      $files[] = $file;
    }
    foreach (glob($path . '/*', GLOB_ONLYDIR|GLOB_NOSORT) as $dir) {
      $files = array_merge($files, $this->getViewsConfigFiles($dir));
    }
    return $files;
  }

  /**
   * Finds the line that contains the substring.
   * 
   * @param string $substring
   *   The string to find.
   * @param string $file_contents
   *   String contents of a file.
   * @return
   *   Line number if found, 1 otherwise.
   */
  private function findKeyLine($substring, $file_contents) {
    $lines = explode("\n", $file_contents);
    foreach ($lines as $num => $line) {
      if (strpos($line, $substring) !== FALSE) {
        return $num + 1;
      }
    }
    return 1;
  }
  
}
