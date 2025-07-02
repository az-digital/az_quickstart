<?php

namespace Drupal\blazy\Utility;

use Drupal\Component\Utility\Color as BaseColor;

/**
 * Performs color conversions.
 */
class Color extends BaseColor {

  /**
   * Parses a hexadecimal color string like '#abc' or '#aabbcc'.
   *
   * @param string $hex
   *   The hexadecimal color string to parse.
   * @param bool|float|int $opacity
   *   The color opacity or alpha channel.
   * @param bool $use_hex
   *   Whether to keep hex, else RGB.
   *
   * @return string
   *   The RGBA if opacity is provided, else RGB or just hex.
   */
  public static function hexToRgba($hex, $opacity = FALSE, $use_hex = TRUE): string {
    $rgb = array_values(self::hexToRgb($hex));

    // @todo respect 0 for transparent color.
    if ($opacity) {
      $rgb[] = (abs($opacity) > 1) ? 1 : $opacity;

      return 'rgba(' . implode(",", $rgb) . ')';
    }
    return $use_hex ? $hex : 'rgb(' . implode(",", $rgb) . ')';
  }

}
