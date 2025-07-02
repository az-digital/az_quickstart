<?php

namespace Drupal\blazy\Media\Svg;

/**
 * Raster to SVG converter based on Flaming Shame.
 *
 * @internal
 *   This is an internal part of the Blazy system and should only be used by
 *   blazy-related code in Blazy module.
 *
 * @code
 * $converter = new Vectorizer();
 * $converter->loadImage('/path/to/my/image.gif');
 * // $converter->setThreshold(80);
 * // header('Content-Type: text/xml');
 * $res = $converter->vectorize();
 * @endcode
 *
 * Converting a Image into a SVG and saving the SVG to a file
 *
 * @code
 * $converter = new Vectorizer();
 * $converter->loadImage('/path/to/my/image.gif');
 * // $converter->setThreshold(80);
 * $res = $converter->saveSvg('/path/to/the/save.svg');
 * @endcode
 *
 * If you need to further manipulate the SVG then you better use `Svg::toXml()`.
 * This method will return a PHP `DOMDocument` object.
 *
 * @code
 * $converter = new Vectorizer();
 * $converter->loadImage('/path/to/my/image.gif');
 * // $converter->setThreshold(80);
 * $res = $converter->toXml();
 * // $res is a DOMDocument object.
 * @endcode
 *
 * Author: Eric Meyer, Amelia Bellamy-Royds, Robin Cafolla, Neal Brooks, and
 * adapted/ modified by Gaus Surahman for Drupal CS.
 * Copyright (c) 2015, Eric A. Meyer <http://meyerweb.com/>
 * @link https://github.com/meyerweb/flaming-shame/
 * @link https://github.com/meyerweb/px2svg
 */
interface VectorizerInterface {

  /**
   * Defines constant direction horizontal.
   */
  const DIRECTION_HORIZONTAL = 1;

  /**
   * Defines constant direction horizontal.
   */
  const DIRECTION_VERTICAL = 2;

  /**
   * Getd threshold value.
   *
   * @return int
   *   Current threshold value.
   */
  public function getThreshold(): int;

  /**
   * Sets threshold value.
   *
   * @param int $threshold
   *   The threshold.
   *
   * @return $this
   *   The class instance.
   */
  public function setThreshold(int $threshold): self;

  /**
   * Get an image from a URL or file path.
   *
   * @param string $path
   *   Url or path to a file.
   *
   * @return $this
   *   The class instance.
   */
  public function loadImage(string $path): self;

  /**
   * Returns the current path.
   *
   * @return string
   *   The loaded image path.
   */
  public function getLoadedImagePath(): string;

  /**
   * Generates SVG from raster.
   *
   * @return string
   *   The generated SVG.
   */
  public function vectorize(): string;

  /**
   * Generates svg from raster and save to a given file.
   *
   * @param string $path
   *   Path where to save the generated SVG.
   *
   * @return int
   *   The result of saving.
   */
  public function saveSvg(string $path): int;

  /**
   * Generates svg from raster.
   *
   * @return \DOMDocument
   *   The DPM document object.
   */
  public function toXml(): \DOMDocument;

}
