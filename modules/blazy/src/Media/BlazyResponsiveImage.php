<?php

namespace Drupal\blazy\Media;

use Drupal\Core\Cache\Cache;
use Drupal\blazy\Theme\Attributes;
use Drupal\blazy\internals\Internals;

/**
 * Provides responsive image utilities.
 *
 * @internal
 *   This is an internal part of the Blazy system and should only be used by
 *   blazy-related code in Blazy module.
 *
 * @todo recap similiraties and make them plugins.
 */
class BlazyResponsiveImage {

  /**
   * The Responsive image styles.
   *
   * @var array
   */
  private static $styles;

  /**
   * Retrieves the breakpoint manager.
   *
   * @return \Drupal\breakpoint\BreakpointManager
   *   The breakpoint manager.
   */
  public static function breakpointManager() {
    return Internals::service('breakpoint.manager');
  }

  /**
   * Initialize the Responsive image definition.
   *
   * ResponsiveImage is the most temperamental module. Unlike plain old Image,
   * it explodes when the image is missing as much as when fed wrong URI, etc.
   * Do not let SVG alike mess up with ResponsiveImage, else fatal.
   */
  public static function transformed(array &$settings): void {
    $blazies  = $settings['blazies'];
    $unstyled = $blazies->is('unstyled');

    // Only if not transformed.
    if (!$blazies->get('resimage.transformed')
      && $style = self::toStyle($settings, $unstyled)) {
      $blazies->set('resimage.style', $style);

      // Might be set via BlazyFilter, but not enough data passed.
      $multiple = $blazies->is('multistyle');
      if (!$blazies->get('resimage.id') || $multiple) {
        self::define($blazies, $style);
      }

      // We'll bail out internally if already set once at container level.
      self::dimensions($settings, $style, FALSE);
      $blazies->set('resimage.transformed', TRUE);
    }
  }

  /**
   * Makes Responsive image usable as CSS background image sources.
   *
   * This is per item dependent on URI, the self::dimensions() is global.
   *
   * @todo use resimage.dimensions once BlazyFormatter + BlazyFilter synced,
   * and Picture are checked with its multiple dimensions aka art direction.
   */
  public static function background(array &$attributes, array &$settings): void {
    $blazies    = $settings['blazies'];
    $resimage   = $blazies->get('resimage.style');
    $background = $blazies->use('bg');

    if (!$background || !$resimage) {
      return;
    }

    if ($styles = self::styles($resimage)) {
      $srcset = $ratios = [];
      foreach (array_values($styles['styles']) as $style) {
        $dims = BlazyImage::transformDimensions($style, $blazies);
        $width = $dims['width'];

        if (!$width) {
          continue;
        }

        // Sort image URLs based on width.
        $sets = $dims + $settings;
        $data = BlazyImage::background($sets, $style);
        $srcset[$width] = $data;
        $ratios[$width] = $data['ratio'];
      }

      if ($srcset) {
        // Sort the srcset from small to large image width or multiplier.
        ksort($srcset);
        ksort($ratios);

        // Prevents NestedArray from making these indices.
        $blazies->set('bgs', (object) $srcset)
          ->set('ratios', (object) $ratios)
          ->set('image.ratio', end($ratios));

        // To make compatible with old bLazy (not Bio) which expects no 1px
        // for [data-src], else error, provide a real smallest image. Bio will
        // map it to the current breakpoint later.
        $bg      = reset($srcset);
        $unlazy  = $blazies->is('undata');
        $old_url = $blazies->get('image.url');
        $new_url = $unlazy ? $old_url : $bg['src'];

        $blazies->set('is.unlazy', $unlazy)
          ->set('image.url', $new_url);

        Attributes::lazy($attributes, $blazies, TRUE);
      }
    }
  }

  /**
   * Sets dimensions once to reduce method calls for Responsive image.
   *
   * Do not limit to preload or fluid, to re-use this for background, etc.
   *
   * @requires Drupal\blazy\Media\Preloader::prepare()
   */
  public static function dimensions(
    array &$settings,
    $resimage = NULL,
    $initial = FALSE,
  ): void {
    $blazies    = $settings['blazies'];
    $dimensions = $blazies->get('resimage.dimensions', []);
    $resimage   = $resimage ?: $blazies->get('resimage.style');

    if ($dimensions || !$resimage) {
      return;
    }

    $styles = self::styles($resimage);
    $names = $ratios = [];
    foreach (array_values($styles['styles']) as $style) {
      // In order to avoid layout reflow, we get dimensions beforehand.
      // @fixme $initial.
      $data = BlazyImage::transformDimensions($style, $blazies);
      $width = $data['width'];

      if (!$width) {
        continue;
      }

      // Collect data.
      $names[$width] = $style->id();
      $ratios[$width] = $data['ratio'];
      $dimensions[$width] = $data;
    }

    // Sort the srcset from small to large image width or multiplier.
    ksort($dimensions);
    ksort($names);
    ksort($ratios);

    // Informs individual images that dimensions are already set once.
    // Dynamic aspect ratio is useless without JS.
    $blazies->set('resimage.dimensions', $dimensions)
      ->set('is.dimensions', TRUE)
      ->set('image.ratio', end($ratios))
      ->set('ratios', (object) $ratios)
      ->set('resimage.ids', array_values($names));

    // Only needed the last one.
    // Overrides plain old image dimensions.
    $blazies->set('image', end($dimensions), TRUE);

    // Currently only needed by Preload.
    // @todo phpstan bug, misleading with multiple conditions.
    /* @phpstan-ignore-next-line */
    if ($initial && ($resimage && !empty($settings['preload']))) {
      self::sources($settings, $resimage);
    }
  }

  /**
   * Returns the Responsive image styles and caches tags.
   *
   * @param object $resimage
   *   The responsive image style entity.
   *
   * @return array
   *   The responsive image styles and cache tags.
   */
  public static function styles($resimage): array {
    $id = $resimage->id();

    if (!isset(self::$styles[$id])) {
      $cache_tags = $resimage->getCacheTags();
      $image_styles = [];
      if ($manager = Internals::service('blazy.manager')) {
        $image_styles = $manager->loadMultiple('image_style', $resimage->getImageStyleIds());
      }

      foreach ($image_styles as $image_style) {
        $cache_tags = Cache::mergeTags($cache_tags, $image_style->getCacheTags());
      }

      self::$styles[$id] = [
        'caches' => $cache_tags,
        'names' => array_keys($image_styles),
        'styles' => $image_styles,
      ];
    }
    return self::$styles[$id];
  }

  /**
   * Modifies fallback image style.
   *
   * Tasks:
   * - Replace core `data:image` GIF with SVG or custom placeholder due to known
   *   issues with GIF, see #2795415. And Views rewrite results, see #2908861.
   * - Provide URL, URI, style from a non-empty fallback, also for Blur, etc.
   *
   * @todo deprecate this when `Image style` has similar `_empty image_` option
   * to reduce complication at Blazy UI, and here.
   */
  public static function fallback(array &$settings, $placeholder): void {
    $blazies  = $settings['blazies'];
    $id       = '_empty image_';
    $width    = $height = 1;
    $ratio    = NULL;
    $data_src = $placeholder;

    // If not enabled via UI, by default, always 1px, or the custom Placeholder.
    // Image style will be prioritized as fallback to have different fallbacks
    // per field relevant for various aspect ratios rather than the one and only
    // fallback for the entire site via Responsive image UI.
    if ($blazies->ui('one_pixel') || !empty($settings['image_style'])) {
      return;
    }

    // Mimicks private _responsive_image_image_style_url, #3119527.
    if ($resimage = $blazies->get('resimage.style')) {
      $fallback = $resimage->getFallbackImageStyle();

      if ($fallback == $id) {
        $data_src = $placeholder;
      }
      else {
        $id = $fallback;
        if ($blazy = Internals::service('blazy.manager')) {
          $uri = $blazies->get('image.uri');

          // @todo use dimensions based on the chosen fallback.
          if ($uri && $style = $blazy->load($id, 'image_style')) {
            $data_src = BlazyImage::toUrl($settings, $style, $uri);
            $tn_uri = $style->buildUri($uri);

            [
              'width'  => $width,
              'height' => $height,
              'ratio'  => $ratio,
            ] = BlazyImage::transformDimensions($style, $blazies, $tn_uri);

            $blazies->set('resimage.fallback.style', $style);
            $blazies->set('resimage.fallback.uri', $tn_uri);

            // Prevents double downloadings.
            $placeholder = Placeholder::generate($width, $height);
            if (empty($settings['thumbnail_style'])) {
              $settings['thumbnail_style'] = $id;
            }
          }
        }
      }

      $blazies->set('resimage.fallback.url', $data_src);
    }

    if ($data_src) {
      // The controller `data-src` attribute, might be valid image thumbnail.
      // The controller `src` attribute, the placeholder: 1px or thumbnail.
      // @todo recheck image.url, too risky override for various usages.
      $blazies->set('image.url', $data_src)
        ->set('placeholder.id', $id)
        ->set('placeholder.url', $placeholder)
        ->set('placeholder.width', $width)
        ->set('placeholder.height', $height)
        ->set('placeholder.ratio', $ratio);
    }
  }

  /**
   * Converts settings.responsive_image_style to its entity.
   *
   * Unlike Image style, Responsive image style requires URI detection per item
   * to determine extension which should not use image style, else BOOM:
   * "This image style can not be used for a responsive image style mapping
   * using the 'sizes' attribute. in
   * responsive_image_build_source_attributes() (line 386...".
   *
   * @requires `unstyled` defined
   */
  public static function toStyle(array $settings, $unstyled = FALSE): ?object {
    $blazies  = $settings['blazies'];
    $exist    = $blazies->is('resimage');
    $_style   = $settings['responsive_image_style'] ?? NULL;
    $multiple = $blazies->is('multistyle');
    $valid    = $exist && $_style;
    $style    = $blazies->get('resimage.style');

    // Multiple is a flag for various styles: Blazy Filter, GridStack, etc.
    // While fields can only have one image style per field.
    if ($valid && $manager = Internals::service('blazy.manager')) {
      if (!$unstyled && (!$style || $multiple)) {
        $style = $manager->load($_style, 'responsive_image_style');
      }
    }

    return $style;
  }

  /**
   * Defines the Responsive image id, styles and caches tags.
   */
  private static function define(&$blazies, $resimage) {
    $id = $resimage->id();
    $styles = self::styles($resimage);

    $blazies->set('resimage.id', $id)
      ->set('cache.metadata.tags', $styles['caches'] ?? [], TRUE);
  }

  /**
   * Provides Responsive image sources relevant for link preload.
   *
   * @see self::dimensions()
   */
  private static function sources(array &$settings, $style = NULL): array {
    if (!($manager = self::breakpointManager())) {
      return [];
    }

    $blazies = $settings['blazies'];
    if ($sources = $blazies->get('resimage.sources', [])) {
      return $sources;
    }

    $style = $style ?: $blazies->get('resimage.style');
    if (!$style) {
      return [];
    }

    $func = function ($image) use ($manager, $blazies, $style) {
      $uri        = $image['uri'];
      $fallback   = NULL;
      $sources    = $variables = [];
      $dimensions = $blazies->get('resimage.dimensions', []);
      $end        = end($dimensions);

      $variables['uri'] = $uri;
      foreach (['width', 'height'] as $key) {
        $variables[$key] = $end[$key] ?? $blazies->get('image.' . $key);
      }

      $id = $style->getFallbackImageStyle();
      $breakpoints = array_reverse($manager
        ->getBreakpointsByGroup($style->getBreakpointGroup()));

      // @todo recheck if any converted to services, bad if also private.
      $func1 = '_responsive_image_build_source_attributes';
      $func2 = '_responsive_image_image_style_url';

      if (is_callable($func1)) {
        if (is_callable($func2)) {
          $fallback = $func2($id, $uri);
        }

        foreach ($style->getKeyedImageStyleMappings() as $bid => $multipliers) {
          if (isset($breakpoints[$bid])) {
            $sources[] = $func1($variables, $breakpoints[$bid], $multipliers);
          }
        }
      }

      $blazies->set('resimage.fallback.id', $id)
        ->set('resimage.fallback.url', $fallback);

      return empty($sources) ? [] : [
        'fallback' => $fallback,
        'items'    => $sources,
      ] + $image;
    };

    $output = [];
    // The URIs are extracted by Preloader::prepare().
    if ($images = $blazies->get('images', [])) {
      // Preserves indices even if empty to have correct mixed media elsewhere.
      foreach ($images as $image) {
        $uri      = $image['uri'] ?? NULL;
        $url      = $image['url'] ?? NULL;
        $output[] = $uri && $url ? $func($image) : [];
      }
    }

    $blazies->set('resimage.sources', $output);

    return $output;
  }

}
