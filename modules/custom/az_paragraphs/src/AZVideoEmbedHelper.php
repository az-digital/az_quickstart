<?php

namespace Drupal\az_paragraphs;

/**
 * Class AZVideoEmbedHelper. Adds service to help get video metadata.
 */
class AZVideoEmbedHelper {

  /**
   * Get YouTube/Vimeo video id from URL.
   *
   * @param string $url
   *   A YouTube/Vimeo url.
   *
   * @return mixed
   *   String Youtube/Vimeo video ID or boolean FALSE if not found.
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

    // Extract the last segment from the path for both YouTube (shortened) and Vimeo.
    if (isset($parts['path'])) {
      $path = explode('/', trim($parts['path'], '/'));
      return $path[count($path) - 1];
    }
    return FALSE;
  }

}
