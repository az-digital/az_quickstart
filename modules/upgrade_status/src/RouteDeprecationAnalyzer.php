<?php

declare(strict_types=1);

namespace Drupal\upgrade_status;

use Drupal\Core\Extension\Extension;

/**
 * The route deprecation analyzer.
 */
final class RouteDeprecationAnalyzer {

  /**
   * Analyzes usages of deprecated route elements in an extension.
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
    $routing_files = $this->getAllRoutingFiles(DRUPAL_ROOT . '/' . $extension->getPath());
    foreach ($routing_files as $routing_file) {
      $content = file_get_contents($routing_file);
      if (strpos($content, '_access_node_revision')) {
        $deprecations[] = new DeprecationMessage('The _access_node_revision routing requirement is deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. Use _entity_access instead. See https://www.drupal.org/node/3161210.', $routing_file, 0, 'RouteDeprecationAnalyzer');
      }
      if (strpos($content, '_access_media_revision')) {
        $deprecations[] = new DeprecationMessage('The _access_media_revision routing requirement is deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. Use _entity_access instead. See https://www.drupal.org/node/3161210.', $routing_file, 0, 'RouteDeprecationAnalyzer');
      }
    }
    return $deprecations;
  }

  /**
   * Finds all .routing.yml files for non-test extensions under a path.
   *
   * @param string $path
   *   Base path to find all .routing.yml files in.
   *
   * @return array
   *   A list of paths to .routing.yml files found under the base path.
   */
  private function getAllRoutingFiles(string $path) {
    $files = [];
    foreach(glob($path . '/*.routing.yml') as $file) {
      // Make sure the filename matches rules for an extension. There may be
      // routing.yml files in shipped configuration which would have more parts.
      $parts = explode('.', basename($file));
      if (count($parts) == 3) {
        $files[] = $file;
      }
    }
    foreach (glob($path . '/*', GLOB_ONLYDIR|GLOB_NOSORT) as $dir) {
      $files = array_merge($files, $this->getAllRoutingFiles($dir));
    }
    return $files;
  }

}
