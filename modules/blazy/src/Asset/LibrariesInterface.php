<?php

namespace Drupal\blazy\Asset;

use Drupal\Core\Asset\LibraryDiscoveryInterface;
use Drupal\blazy\Config\ConfigInterface;

/**
 * Provides libraries utilities.
 */
interface LibrariesInterface extends ConfigInterface {

  /**
   * Retrieves the library descovery service.
   *
   * @return \Drupal\Core\Asset\LibraryDiscoveryInterface
   *   The library discovery.
   */
  public function discovery(): LibraryDiscoveryInterface;

  /**
   * Returns library attachments suitable for #attached property.
   *
   * @param array $attach
   *   The settings which determine what library to attach, empty to defaults.
   *
   * @return array
   *   The supported libraries.
   */
  public function attach(array &$attach): array;

  /**
   * Gets a single library defined by an extension by name.
   *
   * @param string $extension
   *   The name of the extension that registered a library.
   * @param string $name
   *   The name of a registered library to retrieve.
   *
   * @return array
   *   The definition of the requested library, if $name was passed and it
   *   exists, otherwise empty array.
   */
  public function byName($extension, $name): array;

  /**
   * Retrieves the libraries.
   *
   * @param array $names
   *   The library names, e.g.: ['colorbox', 'slick', 'dompurify'].
   * @param bool $base_path
   *   Whether to prefix it with an a base path.
   *
   * @return array
   *   The found libraries keyed by its name, or empty array.
   */
  public function getLibraries(array $names, $base_path = FALSE): array;

  /**
   * Return the available lightboxes, to be cached to avoid disk lookups.
   */
  public function getLightboxes(): array;

  /**
   * Retrieves a library path.
   *
   * A few libraries have inconsistent namings, given different packagers:
   *   - splide x splidejs--splide
   *   - slick x slick-carousel
   *   - DOMPurify x dompurify, etc.
   *
   * @param array|string $name
   *   The library name(s), e.g.: 'colorbox', or ['DOMPurify', 'dompurify'].
   * @param bool $base_path
   *   Whether to prefix it with a base path.
   *
   * @return string|null
   *   The first found path to the library, or NULL if not found.
   */
  public function getPath($name, $base_path = FALSE): ?string;

}
