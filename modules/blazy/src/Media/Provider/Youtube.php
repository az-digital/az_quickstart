<?php

namespace Drupal\blazy\Media\Provider;

/**
 * Provides Youtube utility.
 *
 * @internal
 *   This is an internal part of the Blazy system and should only be used by
 *   blazy-related code in Blazy module.
 */
class Youtube {

  /**
   * Returns the expected input URL, specific for Youtube.
   *
   * OEmbed Resource doesn't accept `/embed`, provides a conversion helper,
   * normally seen at BlazyFilter with youtube embed copy/paste, without
   * creating media entities. Or when given an embed code by VEF, etc.
   *
   * @param string $input
   *   The given url.
   *
   * @return string
   *   The input url.
   */
  public static function fromEmbed($input): ?string {
    if ($input && strpos($input, 'youtube.com/embed') !== FALSE) {
      $search  = '/youtube\.com\/embed\/([a-zA-Z0-9]+)/smi';
      $replace = "youtube.com/watch?v=$1";
      $input   = preg_replace($search, $replace, $input);
    }
    return $input;
  }

}
