<?php

namespace Drupal\webform;

/**
 * Defines an interface for libraries classes.
 */
interface WebformLibrariesManagerInterface {

  /**
   * Get third party libraries status for hook_requirements and drush.
   *
   * @return array
   *   An associative array of third party libraries keyed by library name.
   */
  public function requirements();

  /**
   * Determine if a library's directory exist.
   *
   * @param string $name
   *   The library's directory name.
   *
   * @return bool
   *   TRUE if the library's directory exist.
   */
  public function exists($name);

  /**
   * Finds files that are located in the supported 'libraries' directories.
   *
   * @param string $name
   *   The library's directory name.
   *
   * @return string|false
   *   The real path to the library file relative to the root directory. If the
   *   library cannot be found then FALSE.
   */
  public function find($name);

  /**
   * Get library information.
   *
   * @param string $name
   *   The name of the library.
   *
   * @return array
   *   An associative array containing an library.
   */
  public function getLibrary($name);

  /**
   * Get libraries.
   *
   * @param bool|null $included
   *   Optionally filter by include (TRUE) or excluded (FALSE)
   *
   * @return array
   *   An associative array of libraries.
   */
  public function getLibraries($included = NULL);

  /**
   * Get excluded libraries.
   *
   * @return array
   *   A keyed array of excluded libraries.
   */
  public function getExcludedLibraries();

  /**
   * Determine if library is excluded.
   *
   * @param string $name
   *   The name of the library.
   *
   * @return bool
   *   TRUE if library is excluded.
   */
  public function isExcluded($name);

  /**
   * Determine if library is included.
   *
   * @param string $name
   *   The name of the library.
   *
   * @return bool
   *   TRUE if library is included.
   */
  public function isIncluded($name);

}
