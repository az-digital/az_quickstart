<?php

declare(strict_types=1);

namespace Drupal\upgrade_status;

use Drupal\Component\Serialization\Yaml;
use Drupal\Component\Serialization\Exception\InvalidDataTypeException;
use Drupal\Core\Extension\Extension;

/**
 * The info.yml and composer deprecation analyzer.
 */
final class ExtensionMetadataDeprecationAnalyzer {

  /**
   * Analyzes usages of deprecated extension metadata in an extension.
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
    $info_files = $this->getSubExtensionInfoFiles($project_dir);
    foreach ($info_files as $info_file) {
      try {

        // Manually add on info file incompatibility to results. Reading
        // .info.yml files directly, not from extension discovery because that
        // is cached.
        $file_contents = file_get_contents($info_file);
        $info = Yaml::decode($file_contents) ?: [];
        if (!empty($info['package']) && $info['package'] == 'Testing' && !strpos($info_file, '/upgrade_status_test')) {
          // If this info file was for a testing project other than our own
          // testing projects, ignore it.
          continue;
        }
        $error_path = str_replace(DRUPAL_ROOT . '/', '', $info_file);

        // Check for missing base theme key.
        if ($info['type'] === 'theme') {
          if (!isset($info['base theme'])) {
            $deprecations[] = new DeprecationMessage("The now required 'base theme' key is missing. See https://www.drupal.org/node/3066038.", $error_path, 1, 'ExtensionMetadataDeprecationAnalyzer');
          }
        }

        if (!isset($info['core_version_requirement'])) {
          $deprecations[] = new DeprecationMessage("Add core_version_requirement to designate which Drupal versions is the extension compatible with. See https://drupal.org/node/3070687.", $error_path, 1, 'ExtensionMetadataDeprecationAnalyzer');
        }
        elseif (!ProjectCollector::isCompatibleWithNextMajorDrupal($info['core_version_requirement'])) {
          $line = $this->findKeyLine('core_version_requirement:', $file_contents);
          $deprecations[] = new DeprecationMessage("Value of core_version_requirement: {$info['core_version_requirement']} is not compatible with the next major version of Drupal core. See https://drupal.org/node/3070687.", $error_path, $line, 'ExtensionMetadataDeprecationAnalyzer');
        }

        // @todo
        //   Change values to ExtensionLifecycle class constants once at least
        //   Drupal 9.3 is required.
        if (!empty($info['lifecycle'])) {
          $line = $this->findKeyLine('lifecycle:', $file_contents);
          $link = !empty($info['lifecycle_link']) ? $info['lifecycle_link'] : 'https://www.drupal.org/node/3215042';
          if ($info['lifecycle'] == 'deprecated') {
            $deprecations[] = new DeprecationMessage("This extension is deprecated. Don't use it. See $link.", $error_path, $line, 'ExtensionMetadataDeprecationAnalyzer');
          }
          elseif ($info['lifecycle'] == 'obsolete') {
            $deprecations[] = new DeprecationMessage("This extension is obsolete. Obsolete extensions are usually uninstalled automatically when not needed anymore. You only need to do something about this if the uninstallation was unsuccessful. See $link.", $error_path, $line, 'ExtensionMetadataDeprecationAnalyzer');
          }
        }

      } catch (InvalidDataTypeException $e) {
        $deprecations[] = new DeprecationMessage('Parse error. ' . $e->getMessage(), $error_path, 1, 'ExtensionMetadataDeprecationAnalyzer');
      }

      // No need to check info files for PHP 8 compatibility information because
      // they can only define minimal PHP versions not maximum or excluded PHP
      // versions.
    }

    // Manually add on composer.json file incompatibility to results.
    if (file_exists($project_dir . '/composer.json')) {
      $error_path = $extension->getPath() . '/composer.json';
      $composer_json = json_decode(file_get_contents($project_dir . '/composer.json'));
      if (empty($composer_json) || !is_object($composer_json)) {
        $deprecations[] = new DeprecationMessage("Parse error in composer.json. Having a composer.json is not a requirement in general, but if there is one, it should be valid. See https://drupal.org/node/2514612.", $error_path, 1, 'ExtensionMetadataDeprecationAnalyzer');
      }
      elseif (!empty($composer_json->require->{'drupal/core'}) && !projectCollector::isCompatibleWithNextMajorDrupal($composer_json->require->{'drupal/core'})) {
        $deprecations[] = new DeprecationMessage("The drupal/core requirement is not compatible with the next major version of Drupal. Either remove it or update it to be compatible. See https://www.drupal.org/docs/develop/using-composer/add-a-composerjson-file#core-compatibility.", $error_path, 1, 'ExtensionMetadataDeprecationAnalyzer');
      }
      elseif (!empty($composer_json->require->{'php'}) && !projectCollector::isCompatibleWithPHP($composer_json->require->{'php'}, '8.1.0')) {
        $deprecations[] = new DeprecationMessage("The PHP requirement is not compatible with PHP 8.1. Once the codebase is actually compatible, either remove this limitation or update it to be compatible.", $error_path, 1, 'ExtensionMetadataDeprecationAnalyzer');
      }
    }
    return $deprecations;
  }

  /**
   * Finds all .info.yml files for extensions under a path.
   *
   * @param string $path
   *   Base path to find all info.yml files in.
   *
   * @return array
   *   A list of paths to .info.yml files found under the base path.
   */
  private function getSubExtensionInfoFiles(string $path) {
    $files = [];
    foreach(glob($path . '/*.info.yml') as $file) {
      // Make sure the filename matches rules for an extension. There may be
      // info.yml files in shipped configuration which would have more parts.
      $parts = explode('.', basename($file));
      if (count($parts) == 3) {
        $files[] = $file;
      }
    }
    foreach (glob($path . '/*', GLOB_ONLYDIR|GLOB_NOSORT) as $dir) {
      $files = array_merge($files, $this->getSubExtensionInfoFiles($dir));
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
