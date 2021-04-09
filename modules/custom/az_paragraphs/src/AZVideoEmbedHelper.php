<?php

namespace Drupal\az_paragraphs;

/**
 * Class AZVideoEmbedHelper. Adds service to help get video metadata.
 */
class AZVideoEmbedHelper {

  /**
   * Get YouTube video id from URL.
   *
   * @param string $url
   *   A YouTube url.
   *
   * @return string
   *   String Youtube video ID or boolean FALSE if not found.
   */
  public function getYoutubeIdFromUrl($url) {
    $parts = parse_url($url);
    if (isset($parts['query'])) {
      parse_str($parts['query'], $qs);
      if (isset($qs['v'])) {
        return $qs['v'];
      }
      elseif (isset($qs['vi'])) {
        return $qs['vi'];
      }
    }
    if (isset($parts['path'])) {
      $path = explode('/', trim($parts['path'], '/'));
      return $path[count($path) - 1];
    }
    return FALSE;
  }

}
