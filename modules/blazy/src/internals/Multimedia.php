<?php

namespace Drupal\blazy\internals;

use Drupal\blazy\BlazySettings;
use Drupal\blazy\Media\Provider\Youtube;
use Drupal\blazy\Utility\Sanitize;

/**
 * Provides internal non-reusable blazy utilities.
 *
 * @internal
 *   This is an internal part of the Blazy system and should only be used by
 *   blazy-related code in Blazy module.
 */
class Multimedia extends Settings {

  /**
   * Returns the expected/ corrected input URL.
   *
   * @param string $input
   *   The given url.
   *
   * @return string
   *   The input url.
   */
  public static function correct($input): ?string {
    // If you bang your head around why suddenly Instagram failed, this is it.
    // Only relevant for VEF, not core, in case ::toEmbedUrl() is by-passed:
    if ($input && strpos($input, '//instagram') !== FALSE) {
      $input = str_replace('//instagram', '//www.instagram', $input);
    }
    return $input;
  }

  /**
   * Checks if a provider can not use aspect ratio due to anti-mainstream sizes.
   */
  public static function irrational($provider): bool {
    return in_array($provider ?: 'x', [
      'd500px',
      'flickr',
      'instagram',
      'oembed:instagram',
      'pinterest',
      'twitter',
    ]);
  }

  /**
   * Disables linkable Pinterest, Twitter, etc.
   *
   * @todo refine or excludes other providers that should not be linked.
   */
  public static function linkable($blazies): bool {
    if ($provider = $blazies->get('media.provider')) {
      if (self::irrational($provider) || in_array($provider, ['facebook'])) {
        return FALSE;
      }
    }
    return TRUE;
  }

  /**
   * Provider sometimes NULL when called by sub-modules, not Blazy.
   *
   * @fixme somewhere else.
   */
  public static function provider($blazies, $provider = NULL): ?string {
    if (!$provider && $input = $blazies->get('media.input_url')) {
      $provider = str_ireplace(['www.', '.com'], '', parse_url($input, PHP_URL_HOST));
    }
    return $provider;
  }

  /**
   * Alias for Youtube::fromEmbed().
   */
  public static function youtube($input): ?string {
    return Youtube::fromEmbed($input);
  }

  /**
   * Checks if it is a video.
   */
  public static function isVideo($blazies): bool {
    if ($blazies->get('media.input_url')) {
      $type = $blazies->get('media.resource.type') ?: $blazies->get('media.type');
      return $type == 'video';
    }
    return FALSE;
  }

  /**
   * Modifies settings to support iframes.
   */
  public static function toPlayable($blazies, $src = NULL, $sanitized = FALSE): BlazySettings {
    if ($src) {
      if (!$sanitized) {
        $src = Sanitize::url($src);
        $sanitized = TRUE;
      }

      $blazies->set('media.embed_url', $src)
        ->set('media.escaped', $sanitized);
    }

    return $blazies->set('is.iframeable', TRUE)
      ->set('is.playable', TRUE)
      ->set('is.multimedia', TRUE)
      ->set('use.content', FALSE)
      ->set('libs.media', TRUE);
  }

}
