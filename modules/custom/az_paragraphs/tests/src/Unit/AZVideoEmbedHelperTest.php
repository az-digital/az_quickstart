<?php

namespace Drupal\Tests\az_paragraphs\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\az_paragraphs\AZVideoEmbedHelper;

/**
 * @coversDefaultClass \Drupal\az_paragraphs\AZVideoEmbedHelper
 *
 * @ingroup az_paragraphs
 *
 * @group az_paragraphs
 */
class AZVideoEmbedHelperTest extends UnitTestCase {

  /**
   * @var \Drupal\az_paragraphs\AZVideoEmbedHelper
   */
  protected $videoEmbedHelper;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setup();
    $this->videoEmbedHelper = new AZVideoEmbedHelper();
  }

  /**
   * Tests parsing of YouTube video ID from URLs.
   *
   * @covers ::getYoutubeIdFromUrl
   *
   * @dataProvider providerYouTubeVideoData
   */
  public function testGetYoutubeIdFromUrl($youtube_url, $expected_id) {
    $this->assertEquals($expected_id, $this->videoEmbedHelper->getYoutubeIdFromUrl($youtube_url));
  }

  /**
   * Array of links and the ids they should output.
   */
  public function providerYouTubeVideoData() {
    return [
      [
        'http://youtube.com/v/dQw4w9WgXcQ?feature=youtube_gdata_player',
        'dQw4w9WgXcQ',
      ],
      [
        'http://youtube.com/vi/dQw4w9WgXcQ?feature=youtube_gdata_player',
        'dQw4w9WgXcQ',
      ],
      [
        'http://youtube.com/?v=dQw4w9WgXcQ&feature=youtube_gdata_player',
        'dQw4w9WgXcQ',
      ],
      [
        'http://www.youtube.com/watch?v=dQw4w9WgXcQ&feature=youtube_gdata_player',
        'dQw4w9WgXcQ',
      ],
      [
        'http://youtube.com/?vi=dQw4w9WgXcQ&feature=youtube_gdata_player',
        'dQw4w9WgXcQ',
      ],
      [
        'http://youtube.com/watch?v=dQw4w9WgXcQ&feature=youtube_gdata_player',
        'dQw4w9WgXcQ',
      ],
      [
        'http://youtube.com/watch?vi=dQw4w9WgXcQ&feature=youtube_gdata_player',
        'dQw4w9WgXcQ',
      ],
      [
        'http://youtu.be/dQw4w9WgXcQ?feature=youtube_gdata_player',
        'dQw4w9WgXcQ',
      ],
      [
        'https://youtube.com/v/dQw4w9WgXcQ?feature=youtube_gdata_player',
        'dQw4w9WgXcQ',
      ],
      [
        'https://www.youtube.com/watch?v=dQw4w9WgXcQ&feature=youtube_gdata_player',
        'dQw4w9WgXcQ',
      ],
      [
        'https://youtu.be/dQw4w9WgXcQ?feature=youtube_gdata_player',
        'dQw4w9WgXcQ',
      ],
    ];
  }

}
