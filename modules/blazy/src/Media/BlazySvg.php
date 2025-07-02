<?php

namespace Drupal\blazy\Media;

/**
 * Provides SVG utility.
 *
 * @internal
 *   This is an internal part of the Blazy system and should only be used by
 *   blazy-related code in Blazy module.
 */
class BlazySvg {

  /**
   * Provides svg dimensions, if any.
   */
  public static function dimensions(array &$settings, $uri): void {
    $blazies = $settings['blazies'];
    $fluid   = $blazies->is('fluid');
    $valid   = BlazyFile::isValidUri($uri) && $blazies->is('svg');
    $width   = $height = NULL;
    $attrs   = $settings['svg_attributes'] ?? NULL;

    if (!$valid) {
      return;
    }

    // Sets default fluid to NULL.
    $blazies->set('image.fluid', NULL)
      // @todo move it out of here:
      ->set('image.url', BlazyImage::url($uri));
    $applicable = $attrs != 'none' && $blazies->use('svg_dimensions');

    if ($fluid && !$attrs && $blazies->get('image.style')) {
      $applicable = TRUE;
      $attrs = 'image_style';
    }

    // Checks for optional SVG dimensions, if any.
    if ($applicable && $svg = @simplexml_load_file($uri)) {
      [
        'width'  => $width,
        'height' => $height,
      ] = self::extract($blazies, $svg, $attrs);

      if ($width && $height) {
        // Image styles might be left empty, and aspect ratio is used.
        $dims = ['width' => $width, 'height' => $height];

        if ($fluid) {
          $dims['ratios'] = $blazies->get('css.ratio');

          // The result is normally used for non-inline style, via CSS rules.
          $data = Ratio::fluid($dims);
          $blazies->set('image.fluid', $data)
            ->set('svg.fluid', $data);
        }

        $blazies->set('image.ratio', Ratio::compute($dims));
      }

      $blazies->set('svg.width', $width)
        ->set('svg.height', $height);
    }

    $blazies->set('image.width', $width)
      ->set('image.height', $height);
  }

  /**
   * Extracts available dimensions.
   *
   * SO/15335926:
   * The width and height are how big the <svg> is. The viewBox controls how its
   * contents are displayed so the viewBox="0 0 1500 1000" will scale down the
   * contents of <svg> element by a factor of 5
   * (1500 / 300 = 5 and 1000 / 200 = 5) and the contents will be 1/5 the size
   * they would be without the viewBox but the <svg>.
   */
  private static function extract($blazies, \SimpleXMLElement $svg, $attrs): array {
    $width = $height = NULL;

    if ($attrs) {
      $attrs = strip_tags($attrs);
      // Format WIDTHxHEIGHT:
      if (strpos($attrs, 'x') !== FALSE) {
        [$_width, $_height] = array_map('trim', explode('x', $attrs));
      }
      // Format image_style:
      else {
        $_width  = $blazies->get('image.width');
        $_height = $blazies->get('image.height');
      }
    }
    // Fallback when left empty, not none:
    else {
      $_width  = $svg['width'] ?? NULL;
      $_height = $svg['height'] ?? NULL;
    }

    if ($_width && $_height) {
      $width  = (int) $_width;
      $height = (int) $_height;
    }

    // The viewBox can be insanely huge, 42000, depending on width/height units,
    // 42000 for 420mm, irrelevant for web displays in pixels for non-inline aka
    // embedded SVG in IMG. But width/height is more relevant.
    if (!$width && isset($svg['viewBox'])) {
      [,, $_width, $_height] = array_map('trim', explode(' ', $svg['viewBox']));
      $width = ceil($_width);
      /* @phpstan-ignore-next-line */
      $height = ceil($_height);
    }

    return ['width' => $width, 'height' => $height];
  }

}
