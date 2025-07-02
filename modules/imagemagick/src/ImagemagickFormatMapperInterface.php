<?php

declare(strict_types=1);

namespace Drupal\imagemagick;

/**
 * Provides an interface for ImageMagick format mappers.
 */
interface ImagemagickFormatMapperInterface {

  /**
   * Validates the format map.
   *
   * The map is an associative array with ImageMagick image formats (e.g.
   * JPEG, GIF87, etc.) as keys and an associative array of format variables
   * as value. Each array element is structured like the following:
   * @code
   *   'TIFF' => [
   *     'mime_type' => 'image/tiff',
   *     'enabled' => true,
   *     'weight' => 10,
   *     'exclude_extensions' => 'tif, tifx',
   *   ],
   * @endcode
   *
   * The format variables are as follows:
   * - 'mime_type': the MIME type of the image format. This is used to resolve
   *    the supported file extensions, e.g. ImageMagick 'JPEG' format is mapped
   *    to MIME type 'image/jpeg' which in turn will be mapped to 'jpeg jpg
   *    jpe' image file extensions.
   * - 'enabled': (optional) defines if the fomat needs to be enabled within
   *   the toolkit. Defaults to TRUE.
   * - 'weight': (optional) is used in cases where an image file extension is
   *   mapped to more than one ImageMagick format. It is needed in file format
   *   conversions, e.g. convert from 'png' to 'gif': shall 'GIF' or 'GIF87'
   *   internal Imagemagick format be used? The format will lower weight will
   *   be used. Defaults to 0.
   * - 'exclude_extensions': (optional) is used to limit the file extensions
   *   to be supported by the toolkit, if the mapping MIME type <-> file
   *   extension returns more extensions than needed, and we do not want to
   *   alter the MIME type mapping.
   *
   * @param array[] $map
   *   An associative array with formats as keys and an associative array
   *   of format variables as value.
   *
   * @return array[][]
   *   An array of arrays of error strings.
   */
  public function validateMap(array $map): array;

  /**
   * Gets the list of currently enabled image formats.
   *
   * @return array
   *   A simple array of image formats.
   */
  public function getEnabledFormats(): array;

  /**
   * Gets the list of currently enabled image file extensions.
   *
   * @return array
   *   A simple array of image file extensions.
   */
  public function getEnabledExtensions(): array;

  /**
   * Checks if an image format is enabled in the toolkit.
   *
   * @param string $format
   *   An image format in ImageMagick's internal representation (e.g. JPEG,
   *   GIF87, etc.).
   *
   * @return bool
   *   TRUE if the specified format is enabled within the toolkit, FALSE
   *   otherwise.
   */
  public function isFormatEnabled(string $format): bool;

  /**
   * Gets the MIME type of an image format.
   *
   * @param string $format
   *   An image format in ImageMagick's internal representation (e.g. JPEG,
   *   GIF87, etc.).
   *
   * @return string|null
   *   The MIME type of the specified format if the format is enabled in the
   *   toolkit, NULL otherwise.
   */
  public function getMimeTypeFromFormat(string $format): ?string;

  /**
   * Gets the image format, given the image file extension.
   *
   * @param string $extension
   *   An image file extension (e.g. jpeg, jpg, png, etc.), without leading
   *   dot.
   *
   * @return string|null
   *   The ImageMagick internal format (e.g. JPEG, GIF87, etc.) of the
   *   specified extension, if the format is enabled in the toolkit. NULL
   *   otherwise.
   */
  public function getFormatFromExtension(string $extension): ?string;

}
