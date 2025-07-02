<?php

namespace Drupal\blazy\Plugin\Filter;

use Drupal\Component\Utility\Crypt;
use Drupal\blazy\Blazy;
use Drupal\blazy\internals\Internals;

/**
 * Provides filter attribute utilities.
 *
 * @internal
 *   This is an internal part of the Blazy system and should only be used by
 *   blazy-related code in Blazy module, or its sub-modules.
 */
class AttributeParser {

  /**
   * Returns a randomized id.
   */
  public static function getId($id = 'blazy-filter'): string {
    return Internals::getHtmlId(str_replace('_', '-', $id) . '-' . Crypt::randomBytesBase64(8));
  }

  /**
   * Returns a image/ iframe src.
   *
   * Checks if we have a valid file entity, not hard-coded image URL.
   */
  public static function getValidSrc($node, $use_data_uri = FALSE): ?string {
    $url = '';

    // Prevents data URI from screwing up, unless consciously required.
    $func = function ($input, $key) use ($use_data_uri) {
      $check = trim($input ?: '');
      if ($check) {
        $data_uri = Blazy::isDataUri($check);
        // @todo recheck against sub-modules priority order in Filter admin.
        // The SRC might be 1px, but DATA-SRC is the real data URI.
        // @todo phpstan bug doesn't catch multiple conditions:
        /* @phpstan-ignore-next-line */
        if (!$data_uri || ($data_uri && $use_data_uri)) {
          return $check;
        }
      }
      return '';
    };

    // Prioritize data-src for sub-module filters after Blazy.
    foreach (['data-src', 'src'] as $key) {
      $src = $node->getAttribute($key);
      $check = $func($src, $key);

      if ($check) {
        $url = $check;
        break;
      }
    }

    // If starts with 2 slashes, it is always external.
    if ($url && mb_substr($url, 0, 2) === '//') {
      // We need to query stored SRC for image dimensions, https is enforced.
      $url = 'https:' . $url;
    }

    return $url;
  }

  /**
   * Returns attributes extracted from a DOMElement if any.
   */
  public static function getAttribute(\DOMElement $node, array $excludes = []): array {
    $attributes = [];
    if (property_exists($node->attributes, 'length')
      && $node->attributes->length > 0) {
      foreach ($node->attributes as $attribute) {
        $name  = $attribute->nodeName;
        $value = $attribute->nodeValue;

        if ($excludes && in_array($name, $excludes)) {
          continue;
        }

        if ($name == 'class') {
          $value = array_map('trim', explode(' ', $value));
        }

        $attributes[$name] = $value;
      }
    }

    // Sanitization is done downstream, not here.
    return $attributes;
  }

  /**
   * Extract grids from the node attribute.
   */
  public static function toGrid(\DOMElement $node, array &$settings): void {
    if ($check = $node->getAttribute('grid')) {
      $blazies = $settings['blazies'];
      [$settings['style'], $grid, $settings['visible_items']] = array_pad(array_map('trim', explode(":", $check, 3)), 3, NULL);

      if ($grid) {
        $grid = strip_tags($grid);
        [
          $settings['grid_small'],
          $settings['grid_medium'],
          $settings['grid'],
        ] = array_pad(array_map('trim', explode("-", $grid, 3)), 3, NULL);

        $is_grid = !empty($settings['style']) && !empty($settings['grid']);
        $blazies->set('is.grid', $is_grid);

        if (!empty($settings['style'])) {
          Internals::toNativeGrid($settings);
        }
      }
    }
  }

}
