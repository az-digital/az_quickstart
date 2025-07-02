<?php

declare(strict_types=1);

namespace Drupal\sophron;

use FileEye\MimeMap\Extension;
use FileEye\MimeMap\Type;

/**
 * Provides an interface for FileMapManager.
 */
interface MimeMapManagerInterface {

  /**
   * Option to use Sophron's Drupal-compatible map.
   */
  const DRUPAL_MAP = 0;

  /**
   * Option to use MimeMap's default map.
   */
  const DEFAULT_MAP = 1;

  /**
   * Option to use a custom defined map.
   */
  const CUSTOM_MAP = 99;

  /**
   * Determines if a FQCN is a valid map class.
   *
   * Map classes muste extend from FileEye\MimeMap\Map\AbstractMap.
   *
   * @param string $map_class
   *   A FQCN.
   *
   * @return bool
   *   TRUE if valid, FALSE otherwise.
   */
  public function isMapClassValid(string $map_class): bool;

  /**
   * Gets the FQCN of map currently in use by the manager.
   *
   * @return string
   *   A FQCN.
   */
  public function getMapClass(): string;

  /**
   * Sets the map class to use by the manager.
   *
   * @param string $map_class
   *   A FQCN.
   *
   * @return $this
   */
  public function setMapClass(string $map_class): MimeMapManagerInterface;

  /**
   * Gets the initialization errors of a map class.
   *
   * @param string $map_class
   *   A FQCN.
   *
   * @return array
   *   The array of mapping errors.
   */
  public function getMappingErrors(string $map_class): array;

  /**
   * Gets the list of MIME types.
   *
   * @return string[]
   *   A simple array of MIME type strings.
   */
  public function listTypes(): array;

  /**
   * Gets a MIME type.
   *
   * @param string $type
   *   A MIME type string.
   *
   * @return \FileEye\MimeMap\Type
   *   A Type object.
   *
   * @throws \FileEye\MimeMap\MalformedTypeException
   *   If the type string is malformed.
   * @throws \FileEye\MimeMap\MappingException
   *   If the type is not found.
   *
   * @see \FileEye\MimeMap\Type
   */
  public function getType(string $type): Type;

  /**
   * Gets the list of file extensions.
   *
   * @return string[]
   *   A simple array of file extension strings.
   */
  public function listExtensions(): array;

  /**
   * Gets a file extension.
   *
   * @param string $extension
   *   A file extension string.
   *
   * @return \FileEye\MimeMap\Extension
   *   An Extension object.
   *
   * @throws \FileEye\MimeMap\MappingException
   *   If the extension is not found.
   *
   * @see \FileEye\MimeMap\Extension
   */
  public function getExtension(string $extension): Extension;

  /**
   * Check installation requirements and do status reporting.
   *
   * @param string $phase
   *   The phase in which requirements are checked.
   *
   * @return array
   *   An associative array of requirements.
   */
  public function requirements(string $phase): array;

  // phpcs:disable
  /**
   * Returns an array of gaps of a map vs Drupal's core mapping.
   *
   * @param class-string<\FileEye\MimeMap\Map\MimeMapInterface> $mapClass
   *   A FQCN.
   *
   * @return array
   *   An array of simple arrays, each having a file extension, its Drupal MIME
   *   type guess, and a gap information.
   *
   * @todo add to interface in sophron:3.0.0
   */
  // public function determineMapGaps(string $mapClass): array;
  // phpcs:enable

}
