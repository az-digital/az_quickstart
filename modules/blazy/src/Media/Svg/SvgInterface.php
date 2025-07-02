<?php

namespace Drupal\blazy\Media\Svg;

use Drupal\blazy\Media\BlazyFileInterface;

/**
 * Provides SVG utilities.
 */
interface SvgInterface extends BlazyFileInterface {

  /**
   * Sanitizes the SVG contents.
   *
   * @param string|\Drupal\file\Entity\File $uri
   *   The SVG file URI or File instance.
   * @param array $options
   *   Availables options: sanitize, sanitize_remote.
   *
   * @return string
   *   File content, or empty string if not applicable.
   */
  public function sanitize($uri, array $options = []): ?string;

  /**
   * Returns the Sanitizer instance.
   *
   * @return object|null
   *   The Sanitizer instance if installed, else NULL.
   */
  public function sanitizer(): ?object;

  /**
   * Makes transparent background via shell command, or GD library.
   *
   * This is only useful to remove white or black backgrounds so to have
   * transparent SVG for blur, or thumbnails. This might be removed once we
   * found existing solutions. Imagick sounds cool.
   *
   * Steps:
   *   - Convert to PNG, or WEBP, might be internally.
   *   - Run this method.
   *
   * @param string $uri
   *   The public URI.
   * @param string $destination
   *   The destination URI to store the newly created transparent PNG file.
   * @param string $color
   *   The colors code normally white, or (255, 255, 255) or black (0, 0, 0).
   * @param int $fuzz
   *   The fuzz level relevant to ImageMagick `convert` command option, where
   *   the smaller the fuzz %, the closer to true white or conversely, the
   *   larger the %, the more variation from white is allowed to become
   *   transparent.
   *
   * @return string
   *   The string to the newly created transparent PNG image.
   *
   * @todo make it an ImageEffect, and ignore the rest of @todos.
   * @todo use ImagemagickExecManagerInterface::execute|runOsShell for cross-os.
   * @todo check for modules Imagick, Imagemagick and ImageEffects, etc.
   *   - ImageEffects only support GIF.
   *   - Imagick looks more versatile, at least no validations.
   * @todo use Symfony Process with Timer.
   * @see https://imagemagick.org/Usage/color_basics/#fuzz_distance
   * @see https://imagemagick.org/script/formats.php
   * @see https://stackoverflow.com/questions/11285397
   */
  public function transparentize(
    $uri,
    $destination,
    $color = '#ffffff',
    $fuzz = 20,
  ): ?string;

  /**
   * Returns SVG for markup element.
   *
   * @param string|\Drupal\file\Entity\File $uri
   *   The SVG content, file URI or File instance.
   * @param array $options
   *   Availables options: sanitize, sanitize_remote.
   *
   * @return string
   *   The SVG markup, or empty string if not applicable.
   */
  public function view($uri, array $options = []): ?string;

  /**
   * Generates SVG from raster.
   *
   * Warning! This is not for large images, only thumbnails or blur images.
   * It choked 12GB machine given just 1MB file size. Not implemented, yet.
   *
   * @param string $url
   *   The image URL to be converted into an SVG file.
   * @param array $options
   *   The options for conversion.
   *
   * @return string
   *   The SVG markup.
   */
  public function vectorize($url, array $options = []): string;

}
