<?php

namespace Drupal\az_media;

/**
 * Generates placeholder SVGs on demand, similar to https://placehold.co.
 *
 * We can't use a remote URL for Single Directory Component `Examples`
 * because a `public://` string is what Canvas's shape-matching recognizes
 * to enable core's media_library. We can use a `json-schema-definitions://`
 * `$ref` scheme but that would make all our SDC's depend on Canvas,
 * which we don't want to do.
 *
 * @see \Drupal\az_media\Controller\AZPlaceholderImageController
 * @see \Drupal\az_media\Routing\AZPlaceholderRoutes
 */
class AZPlaceholderImageGenerator {

  /**
   * Minimum allowed dimension (px).
   */
  const MIN_DIMENSION = 10;

  /**
   * Maximum allowed dimension (px).
   *
   * Matches https://placehold.co. Image generation is string building, which
   * means a 4000x4000 placeholder costs the same as a 10x10 one. So this is
   * just a sanity bound and not for resource control.
   */
  const MAX_DIMENSION = 4000;

  /**
   * A URL path segment where placeholders are served under.
   *
   * Not a real directory.
   */
  const DIRECTORY = 'az-placeholder';

  /**
   * Whether both dimensions are within the allowed range.
   *
   * @param int $width
   *   Width in pixels.
   * @param int $height
   *   Height in pixels.
   *
   * @return bool
   *   TRUE when both dimensions are in range.
   */
  public function isValidSize(int $width, int $height): bool {
    foreach ([$width, $height] as $dimension) {
      if ($dimension < self::MIN_DIMENSION || $dimension > self::MAX_DIMENSION) {
        return FALSE;
      }
    }
    return TRUE;
  }

  /**
   * Builds the SVG markup, given width and height.
   *
   * Using the `int` type here means there's no way for injection to
   * happen. Hence we don't need to escape anything.
   *
   * @param int $width
   *   Width in pixels.
   * @param int $height
   *   Height in pixels.
   *
   * @return string
   *   The SVG document.
   */
  public function generate(int $width, int $height): string {
    // Build the label.
    $label = $width . ' × ' . $height;

    // Figure out the font size. Meet these requirements:
    // 1. The label should fit in the placeholder image.
    // 2. It should span up to 0.625 of the placeholder's width (what
    // placehold.co does), accounting for how many characters are in the
    // label and how wide they average (0.52 em each).
    // 3. The font size should not exceed 0.42 of the placeholder's height,
    // so a short image can't overflow.
    $by_width = (0.625 * $width) / (mb_strlen($label) * 0.52);
    $by_height = $height * 0.42;
    $font_size = max(1, (int) floor(min($by_width, $by_height)));

    // Muted grays. Bold. Label uses whatever sans-serif font the viewer has.
    // The 0.52 em measurement is for sans-serif, so spot check if you need to
    // change font-family.
    return <<<SVG
    <svg xmlns="http://www.w3.org/2000/svg" width="{$width}" height="{$height}" viewBox="0 0 {$width} {$height}" role="img" aria-label="Placeholder image, {$width} by {$height} pixels">
      <rect width="100%" height="100%" fill="#DDDDDD"/>
      <text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle" font-family="sans-serif" font-weight="bold" font-size="{$font_size}" fill="#999999">{$label}</text>
    </svg>
    SVG;
  }

}
