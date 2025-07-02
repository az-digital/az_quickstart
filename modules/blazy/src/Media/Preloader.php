<?php

namespace Drupal\blazy\Media;

use Drupal\Component\Utility\UrlHelper;
use Drupal\blazy\Blazy;
use Drupal\blazy\Utility\CheckItem;

/**
 * Provides preload utility.
 *
 * @internal
 *   This is an internal part of the Blazy system and should only be used by
 *   blazy-related code in Blazy module.
 *
 * @todo recap similiraties and make them plugins.
 */
class Preloader {

  /**
   * Preload late-discovered resources for better performance.
   *
   * @see https://web.dev/preload-critical-assets/
   * @see https://caniuse.com/?search=preload
   * @see https://developer.mozilla.org/en-US/docs/Web/HTML/Link_types/preload
   * @see https://developer.chrome.com/blog/new-in-chrome-73/#more
   * @nottodo support multiple hero images like carousels.
   */
  public static function preload(array &$load, array $settings): void {
    $blazies = $settings['blazies'];
    $images  = array_filter($blazies->get('images', []));
    $sources = $blazies->get('resimage.sources', []);

    if (empty($images) || empty($images[0]['uri'])) {
      return;
    }

    $links = self::generate($images, $sources, $blazies);
    foreach ($links as $key => $value) {
      if ($value) {
        $load['html_head'][$key] = $value;
      }
    }
  }

  /**
   * Extracts uris from file/ media entity, relevant for the new option Preload.
   *
   * @requires image styles defined via BlazyImage::styles().
   *
   * Also extract the found image for gallery/ zoom like, ElevateZoomPlus, etc.
   *
   * @todo merge urls here as well once puzzles are solved: URI may be fed by
   * field formatters like this one, blazy_filter, views field, or manual call.
   */
  public static function prepare(array &$settings, $items, array $entities = []): void {
    $blazies = $settings['blazies'];
    if (array_filter($blazies->get('images', []))) {
      return;
    }

    $style = $blazies->get('image.style');
    $func = function ($item, $entity = NULL, $delta = 0) use (&$settings, $blazies, $style) {
      $options  = ['entity' => $entity, 'settings' => $settings];
      $image    = BlazyImage::item($item, $options);
      $uri      = BlazyFile::uri($image);
      $valid    = BlazyFile::isValidUri($uri);
      $unstyled = $uri ? CheckItem::unstyled($settings, $uri) : FALSE;
      $url      = BlazyImage::toUrl($settings, $style, $uri);

      // Only needed the first found image, no problem which with mixed media.
      if ($uri && !$blazies->get('first.uri')) {
        $blazies->set('first.url', $url)
          ->set('first.item', $image)
          ->set('first.unstyled', $unstyled)
          ->set('first.uri', $uri);

        // The first image dimensions to differ from individual item dimensions.
        BlazyImage::dimensions($settings, $image, $uri, TRUE);
      }

      // @todo also pass $style + $image when all sources covered.
      return $uri ? [
        'delta'    => $delta,
        'unstyled' => $unstyled,
        'uri'      => $uri,
        'url'      => $url,
        'valid'    => $valid,
      ] : [];
    };

    $empties = $images = [];
    foreach ($items as $key => $item) {
      // Respects empty URI to keep indices intact for correct mixed media.
      $image = $func($item, $entities[$key] ?? NULL, $key);
      $images[] = $image;

      if (empty($image['uri'])) {
        $empties[] = TRUE;
      }
    }

    $empty = count($empties) == count($images);
    $images = $empty ? array_filter($images) : $images;

    $blazies->set('images', $images);

    // Checks for [Responsive] image dimensions and sources for formatters
    // and filters. Sets dimensions once, if cropped, to reduce costs with ton
    // of images. This is less expensive than re-defining dimensions per image.
    // These also provide data for the Preload option.
    if (!$blazies->was('resimage_dimensions')) {
      $unstyled = $blazies->get('first.unstyled');
      if (!$unstyled && $blazies->get('first.uri')) {
        $resimage = BlazyResponsiveImage::toStyle($settings, $unstyled);
        if ($resimage) {
          BlazyResponsiveImage::dimensions($settings, $resimage, TRUE);
        }
        elseif ($style) {
          BlazyImage::cropDimensions($settings, $style);
        }
      }
      $blazies->set('was.resimage_dimensions', TRUE);
    }
  }

  /**
   * Generates preload urls.
   */
  private static function generate(array $images, array $sources, $blazies): \Generator {
    // Suppress useless warning of likely failing initial image generation.
    // Better than checking file exists.
    $mime = @mime_content_type($images[0]['uri']);
    [$type] = array_map('trim', explode('/', $mime, 2));

    $link = function ($url, $uri, $item = NULL, $valid = FALSE) use ($mime, $type): array {
      // Each field may have different mime types for each image just like URIs.
      $mime = @mime_content_type($uri) ?: $mime;
      if ($item) {
        $item_type = $item['type'] ?? NULL;
        $mime = $item_type ? $item_type->value() : $mime;
      }

      [$type] = array_map('trim', explode('/', $mime, 2));
      $key = hash('md2', $url);

      $attrs = [
        'rel'  => 'preload',
        'as'   => $type,
        'href' => $valid ? $url : UrlHelper::stripDangerousProtocols($url),
        'type' => $mime,
      ];

      $suffix = '';
      if ($srcset = ($item['srcset'] ?? NULL)) {
        $suffix = '_responsive';
        $attrs['imagesrcset'] = $srcset->value();

        if ($sizes = ($item['sizes'] ?? NULL)) {
          $attrs['imagesizes'] = $sizes->value();
        }
      }

      // Checks for external URI.
      if (UrlHelper::isExternal($uri ?: $url)) {
        $attrs['crossorigin'] = TRUE;
      }

      return [
        [
          '#tag' => 'link',
          '#attributes' => $attrs,
        ],
        'blazy' . $suffix . '_' . $type . $key,
      ];
    };

    // Responsive image with multiple sources.
    if ($sources) {
      foreach ($sources as $source) {
        $uri   = $source['uri'];
        $url   = $source['fallback'];
        $valid = $source['valid'];

        // Preloading 1px data URI makes no sense, see if image_url exists.
        $data_uri = Blazy::isDataUri($url);
        if ($data_uri && $url2 = $source['url'] ?? NULL) {
          $url = $url2;
        }

        foreach ($source['items'] as $item) {
          yield empty($item['srcset']) ? NULL : $link($url, $uri, $item, $valid);
        }
      }
    }
    else {
      // Regular plain old images.
      foreach ($images as $image) {
        // Indices might be preserved even empty/ failing URI, etc.
        $uri   = $image['uri'] ?? NULL;
        $url   = $image['url'] ?? NULL;
        $valid = $image['valid'] ?? FALSE;

        // URI might be empty with mixed media, but indices are preserved.
        yield $uri && $url ? $link($url, $uri, NULL, $valid) : NULL;
      }
    }
  }

}
