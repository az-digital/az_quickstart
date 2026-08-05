<?php

namespace Drupal\az_media;

/**
 * Generates placeholder SVGs on demand, in the spirit of placehold.co.
 *
 * SDC `examples` values must resolve to something renderable, and a remote
 * URL cannot be used: ImageStyle::buildUri() parses any `scheme://` prefix
 * syntactically and, finding no registered stream wrapper, mangles
 * `https://example.com/x.png` into a bogus `public://styles/.../https/...`
 * path. Shipping a committed image per dimension does not scale either, so
 * placeholders are generated per request from the dimensions in the URL.
 *
 * Nothing is written to disk. The response carries a one-year immutable
 * Cache-Control header, so browsers and any edge cache in front of the site
 * serve repeat requests without touching Drupal, and there is no need to
 * persist a file to get the same effect. Generating rather than storing also
 * means an anonymous, necessarily-public route cannot be walked to fill the
 * filesystem with millions of tiny files - there is no write to abuse, so no
 * allow-list or size registry is needed to bound one. A request costs less
 * to serve than the site's own 404 page.
 *
 * SVG rather than a raster format because there is no font file in the
 * codebase to draw with - Proxima Nova is delivered as a Typekit webfont, and
 * GD's imagettftext() needs a real file on disk. An SVG names a font stack
 * and lets the renderer resolve it, embedding nothing. Note that an SVG
 * loaded through <img src> gets no access to the host document's webfonts,
 * so the generic `sans-serif` here is what actually renders.
 */
class AZPlaceholderImageGenerator {

  /**
   * Smallest permitted dimension, in pixels.
   */
  const MIN_DIMENSION = 10;

  /**
   * Largest permitted dimension, in pixels.
   *
   * Matches placehold.co. Generation is string building rather than
   * rasterization, so this is a sanity bound and not a resource control - a
   * 4000x4000 placeholder costs the same to produce as a 10x10 one.
   */
  const MAX_DIMENSION = 4000;

  /**
   * Directory beneath the public files path that placeholders are served at.
   *
   * No such directory exists on disk; this is a URL path segment only.
   */
  const DIRECTORY = 'az-placeholder';

  /**
   * Whether both dimensions are within the permitted range.
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
   * Builds the SVG markup for a given size.
   *
   * No caller-supplied text is ever interpolated - the only inputs are two
   * integers, already range-checked - so the markup carries no injection
   * surface.
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
    $label = $width . ' × ' . $height;

    // Scale the label to span roughly half the image width, which is what
    // placehold.co does: measuring its output, the text occupies 48-57% of
    // the width at every size and aspect ratio sampled (10x10 through
    // 4000x4000). Driving off width rather than the shorter side is what
    // keeps a wide, short image from getting a comically small label.
    //
    // 0.58 approximates the average advance width, in ems, of the digits and
    // spaces this label is made of; dividing by it converts a target text
    // width into a font size.
    $by_width = (0.52 * $width) / (mb_strlen($label) * 0.58);
    // ...but never so tall that it overflows a short image.
    $by_height = $height * 0.42;
    $font_size = max(1, (int) floor(min($by_width, $by_height)));

    // Muted grays, following placehold.co: a placeholder should read as
    // scaffolding, not compete with the design around it.
    //
    // NOTE: placehold.co converts its label to <path> outlines, so its text
    // renders identically everywhere with no font dependency at all. We have
    // no font file to trace glyphs from, so a <text> element it is - which
    // means the label is drawn in whatever generic sans-serif the viewer has.
    return <<<SVG
    <svg xmlns="http://www.w3.org/2000/svg" width="{$width}" height="{$height}" viewBox="0 0 {$width} {$height}" role="img" aria-label="Placeholder image, {$width} by {$height} pixels">
      <rect width="100%" height="100%" fill="#DDDDDD"/>
      <text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle" font-family="sans-serif" font-size="{$font_size}" fill="#999999">{$label}</text>
    </svg>
    SVG;
  }

}
