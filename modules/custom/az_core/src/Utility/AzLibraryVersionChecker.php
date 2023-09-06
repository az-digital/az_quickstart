<?php

namespace Drupal\az_core\Utility;

/**
 * Provides a utility to check the version of a library.
 */
class AzLibraryVersionChecker {

  /**
   * Checks the version of a required library.
   *
   * @param string $machine_name
   *   The module machine name.
   * @param string $name
   *   The module name.
   * @param string $library
   *   The library machine name.
   * @param float $min_version
   *   The min version required.
   * @param float $max_version
   *   The max version required.
   *
   * @return array
   *   Requirements messages.
   */
  public static function checkLibrary($machine_name, $name, $library, $min_version, $max_version) {
    $library_found = FALSE;
    $requirements = [];

    $json = self::readPackages();
    if ($json) {
      // Loops through installed packages and check that the SDK version is OK.
      foreach ($json as $package) {
        if ($package['name'] == $library) {
          $library_found = TRUE;
          if ($package['version_normalized'] < $min_version || $package['version_normalized'] > $max_version) {
            $requirements[$machine_name] = [
              'description' => t("@name could not be installed because an incompatible version of @library was detected. Please read the installation instructions.",
                ['@name' => $name, '@library' => $library]),
              'severity' => REQUIREMENT_ERROR,
            ];
          }
        }
      }
      // Library was not found in installed.json.
      if (!$library_found) {
        $requirements[$machine_name] = [
          'description' => t("@name could not be installed because @library was not found. @name must be installed using Composer. Please read the installation instructions.",
            ['@name' => $name, '@library' => $library]),
          'severity' => REQUIREMENT_ERROR,
        ];
      }
    }
    // installed.json could not be read or parsed.
    else {
      $requirements[$machine_name] = [
        'description' => t('@name could not be installed: installed.json could not be read.',
          ['@name' => $name]),
        'severity' => REQUIREMENT_ERROR,
      ];
    }

    return $requirements;
  }

  /**
   * Checks the version of a required library.
   *
   * @param string $library
   *   The library machine name.
   *
   * @return bool|float
   *   The semantic version of the library if found.
   *   False otherwise.
   */
  public static function getSemanticInstalledVersion($library) {
    $json = self::readPackages();
    if ($json) {
      // Loops through installed packages and check that the library version is OK.
      foreach ($json['packages'] as $package) {
        if ($package['name'] == $library) {
          $parts = explode('.', $package['version_normalized']);
          $major = $parts[0];
          $minor = $parts[1];
          $patch = $parts[2];
          $version = $major . '.' . $minor . '.' . $patch;
          return $version;
        }
      }
    }
    return FALSE;
  }

  /**
   * Reads and parses Drupal installed.json.
   *
   * @return array|bool
   *   Parsed installed.json if the file could be read and parsed.
   *   False otherwise.
   */
  protected static function readPackages() {
    // Make this test compatible with either regular installs.
    // Check whether DRUPAL_ROOT is the actual website filesystem root or /web/
    // subdirectory in case of composer drupal-scaffold.
    $base_dir = is_dir(DRUPAL_ROOT . '/vendor') ? DRUPAL_ROOT : dirname(DRUPAL_ROOT);
    $file_uri = $base_dir . '/vendor/composer/installed.json';
    if (file_exists($file_uri)) {
      if ($filedata = file_get_contents($file_uri)) {
        $json = json_decode($filedata, TRUE);
        if ($json !== NULL) {
          return $json;
        }
      }
    }

    return FALSE;
  }

}
