<?php

namespace Drupal\az_paragraphs;

/**
 * Class AZVideoEmbedHelper. Adds service to help get video metadata.
 */
class AZVideoEmbedHelper {

  /**
   * Constructs a new AZVideoEmbedHelper object.
   */
  public function __construct() {

  }

  /**
   * Get YouTube video id from URL.
   *
   * Should work with these patterns:
   * 'http://youtube.com/v/dQw4w9WgXcQ?feature=youtube_gdata_player',
   * 'http://youtube.com/vi/dQw4w9WgXcQ?feature=youtube_gdata_player',
   * 'http://youtube.com/?v=dQw4w9WgXcQ&feature=youtube_gdata_player',
   * 'http://www.youtube.com/watch?v=dQw4w9WgXcQ&feature=youtube_gdata_player',
   * 'http://youtube.com/?vi=dQw4w9WgXcQ&feature=youtube_gdata_player',
   * 'http://youtube.com/watch?v=dQw4w9WgXcQ&feature=youtube_gdata_player',
   * 'http://youtube.com/watch?vi=dQw4w9WgXcQ&feature=youtube_gdata_player',
   * 'http://youtu.be/dQw4w9WgXcQ?feature=youtube_gdata_player'
   *
   * @param string $url
   *   A YouTube url.
   *
   * @return string
   *   Mixed Youtube video ID or FALSE if not found.
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
