<?php

declare(strict_types=1);

namespace Drupal\file_mdm_exif;

/**
 * Provides an interface for EXIF metadata ifds and tags mapper.
 */
interface ExifTagMapperInterface {

  /**
   * Resolves a metadata 'key' to the default EXIF IFD and TAG.
   *
   * @param string|array $key
   *   A metadata 'key' as passed in by the file metadata manager. It can be a
   *   string, in which case the default IFD and TAG are returned. If it is an
   *   array, then the first and the second array elements define respectively
   *   the IFD and the TAG requested. IFD and TAG can be strings, in which case
   *   they are converted to EXIF integer identifiers, or integers, in which
   *   case they are returned as such.
   *
   * @return array
   *   An associative array with the following keys:
   *     'ifd' - the IFD EXIF integer identifier.
   *     'tag' - the TAG EXIF integer identifier.
   *
   * @throws \Drupal\file_mdm\FileMetadataException
   *   When wrong argument is passed, or if the IFD/TAG could not be found.
   */
  public function resolveKeyToIfdAndTag(string|array $key): array;

  /**
   * Returns a list of default metadata 'keys' supported.
   *
   * @param array $options
   *   (optional) If specified, restricts the results returned. By default, all
   *   the available EXIF IFD/TAG combinations for any IFD are returned.
   *   If $options contains ['ifds' => TRUE], the supported IFDs are returned.
   *   If $options contains ['ifd' => $value], the IFD/TAG combinations
   *   supported by the IFD specified by $value are returned.
   *
   * @return array
   *   A simple array.
   *   When returning a list of supported IFDs, each array element is a simple
   *   array with:
   *     0 => the default string identifier of the IFD.
   *     1 => the integer identifier of the IFD.
   *   When returning a list of supported IFD/TAGs, each array element is a
   *   simple array with:
   *     0 => the string identifier of the IFD.
   *     1 => the string identifier of the TAG.
   */
  public function getSupportedKeys(array $options = NULL): array;

}
