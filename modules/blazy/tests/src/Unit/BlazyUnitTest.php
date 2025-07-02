<?php

namespace Drupal\Tests\blazy\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\Tests\blazy\Traits\BlazyManagerUnitTestTrait;
use Drupal\Tests\blazy\Traits\BlazyUnitTestTrait;
use Drupal\blazy\Blazy;
use Drupal\blazy\BlazyDefault;
use Drupal\blazy\Theme\Attributes;
use Drupal\blazy\Theme\BlazyTheme;

/**
 * @coversDefaultClass \Drupal\blazy\Blazy
 *
 * @group blazy
 */
class BlazyUnitTest extends UnitTestCase {

  use BlazyUnitTestTrait;
  use BlazyManagerUnitTestTrait;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->setUpVariables();
    $this->setUpUnitServices();
    $this->setUpUnitContainer();
    $this->setUpMockImage();
  }

  /**
   * Tests \Drupal\blazy\Theme\Attributes::buildIframe.
   *
   * @param array $data
   *   The input data which can be string, or integer.
   * @param mixed|bool|int $expected
   *   The expected output.
   *
   * @covers ::buildIframe
   * @covers \Drupal\blazy\Blazy::init
   * @covers \Drupal\blazy\BlazyDefault::entitySettings
   * @dataProvider providerTestBuildIframe
   */
  public function testBuildIframe(array $data, $expected) {
    $variables = ['attributes' => [], 'image' => []];
    $settings  = Blazy::init();
    $uri       = 'public://example.jpg';
    $embed_url = '//www.youtube.com/watch?v=E03HFA923kw';

    $blazies = $settings['blazies'];
    $blazies->set('media.embed_url', $embed_url)
      ->set('media.bundle', 'remote_video')
      ->set('media.type', 'video')
      ->set('image.uri', $uri);

    $variables['settings'] = array_merge($settings, $data);
    $variables['image'] = 'x';
    Attributes::buildIframe($variables);

    $this->assertNotEmpty($variables[$expected]);
  }

  /**
   * Provide test cases for ::testBuildIframe().
   */
  public static function providerTestBuildIframe() {
    return [
      [
        [
          'media_switch' => 'media',
          'ratio' => 'fluid',
        ],
        'image',
      ],
      [
        [
          'media_switch' => '',
          'ratio' => '',
          'width' => 640,
          'height' => 360,
        ],
        'iframe',
      ],
    ];
  }

  /**
   * Tests \Drupal\blazy\Theme\BlazyTheme::blazy.
   *
   * @param array $settings
   *   The settings being tested.
   * @param object $item
   *   Whether to provide image item, or not.
   * @param bool $expected_image
   *   Whether to expect an image, or not.
   * @param bool $expected_iframe
   *   Whether to expect an iframe, or not.
   *
   * @covers \Drupal\blazy\Blazy::init
   * @covers \Drupal\blazy\Theme\BlazyTheme::blazy
   * @covers \Drupal\blazy\Media\BlazyImage::prepare
   * @covers \Drupal\blazy\BlazyDefault::entitySettings
   * @dataProvider providerPreprocessBlazy
   */
  public function testPreprocessBlazy(array $settings, $item, $expected_image, $expected_iframe) {
    $variables = ['attributes' => []];
    $build     = $this->data;
    $settings  = array_merge($build['#settings'], $settings);
    $settings += Blazy::init();
    $blazies   = $settings['blazies'];
    $embed_url = $settings['embed_url'] ?? '';

    $settings['image_style']     = '';
    $settings['thumbnail_style'] = '';

    $blazies->set('is.blazy', TRUE)
      ->set('lazy.id', 'blazy')
      ->set('media.embed_url', $embed_url)
      ->set('media.type', $settings['type'] ?? '')
      ->set('image.uri', $settings['uri'] ?? '');

    if ($embed_url) {
      $settings = array_merge(BlazyDefault::entitySettings(), $settings);
    }

    $variables['element']['#item'] = $item == TRUE ? $this->testItem : NULL;
    $variables['element']['#settings'] = $settings;

    BlazyTheme::blazy($variables);

    $image = $expected_image == TRUE ? !empty($variables['image']) : empty($variables['image']);
    $iframe = $expected_iframe == TRUE ? !empty($variables['iframe']) : empty($variables['iframe']);

    $this->assertTrue($image);
    $this->assertTrue($iframe);

    $processed = $variables['settings']['blazies'];
    $this->assertEquals($blazies->get('lazy.id'), $processed->get('lazy.id'));
  }

  /**
   * Provider for ::testPreprocessBlazy.
   */
  public static function providerPreprocessBlazy() {
    $uri = 'public://example.jpg';

    $data[] = [
      [
        'background' => FALSE,
        'uri' => '',
      ],
      TRUE,
      FALSE,
      FALSE,
    ];
    $data[] = [
      [
        'background' => TRUE,
        'uri' => $uri,
      ],
      TRUE,
      FALSE,
      FALSE,
    ];
    $data[] = [
      [
        'background' => FALSE,
        'embed_url' => '//www.youtube.com/watch?v=E03HFA923kw',
        'media_switch' => '',
        'ratio' => 'fluid',
        'width' => 640,
        'height' => 360,
        'uri' => $uri,
        'type' => 'video',
      ],
      TRUE,
      FALSE,
      TRUE,
    ];
    $data[] = [
      [
        'background' => FALSE,
        'embed_url' => '//www.youtube.com/watch?v=E03HFA923kw',
        'media_switch' => 'media',
        'ratio' => 'fluid',
        'type' => 'video',
        'width' => 640,
        'height' => 360,
        'uri' => $uri,
      ],
      TRUE,
      TRUE,
      FALSE,
    ];

    return $data;
  }

  /**
   * Tests BlazyManager image with lightbox support.
   *
   * This is here as we need BlazyFile::transformRelative() for
   * both Blazy and its lightbox.
   *
   * @param array $settings
   *   The settings being tested.
   *
   * @covers \Drupal\blazy\BlazyManager::preRenderBlazy
   * @covers \Drupal\blazy\Theme\Lightbox::build
   * @covers \Drupal\blazy\Theme\Lightbox::buildCaptions
   * @dataProvider providerTestPreRenderImageLightbox
   */
  public function todoTestPreRenderImageLightbox(array $settings = []) {
    $build                       = $this->data;
    $settings                   += Blazy::init();
    $blazies                     = $settings['blazies'];
    $settings['box_style']       = '';
    $settings['box_media_style'] = '';

    $blazies->set('entity.url', $settings['content_url'] ?? '')
      ->set('media.embed_url', $settings['embed_url'] ?? '')
      ->set('media.type', $settings['type'] ?? '')
      ->set('image.uri', $this->uri)
      ->set('count', $this->maxItems);

    $build['#settings'] = array_merge($build['#settings'], $settings);
    $switch_css = str_replace('_', '-', $settings['media_switch']);

    foreach (['caption', 'media', 'wrapper'] as $key) {
      $build[$key . '_attributes']['class'][] = $key . '-test';
    }

    $element = $this->doPreRenderImage($build);

    $blazies = $build['#settings']['blazies'];
    if ($settings['media_switch'] == 'content') {
      $this->assertEquals($blazies->get('entity.url'), $element['#url']);
      $this->assertArrayHasKey('#url', $element);
    }
    else {
      $this->assertArrayHasKey('data-' . $switch_css . '-trigger', $element['#url_attributes']);
      $this->assertArrayHasKey('#url', $element);
    }
  }

  /**
   * Provide test cases for ::testPreRenderImageLightbox().
   *
   * @return array
   *   An array of tested data.
   */
  public static function providerTestPreRenderImageLightbox() {
    $data[] = [
      [
        'box_caption' => '',
        'content_url' => 'node/1',
        'dimension' => '',
        'lightbox' => FALSE,
        'media_switch' => 'content',
        'type' => 'image',
      ],
    ];
    $data[] = [
      [
        'box_caption' => 'auto',
        'lightbox' => TRUE,
        'media_switch' => 'blazy_test',
        'type' => 'image',
      ],
    ];
    $data[] = [
      [
        'box_caption' => 'alt',
        'lightbox' => TRUE,
        'media_switch' => 'blazy_test',
        'type' => 'image',
      ],
    ];
    $data[] = [
      [
        'box_caption' => 'title',
        'lightbox' => TRUE,
        'media_switch' => 'blazy_test',
        'type' => 'image',
      ],
    ];
    $data[] = [
      [
        'box_caption' => 'alt_title',
        'lightbox' => TRUE,
        'media_switch' => 'blazy_test',
        'type' => 'image',
      ],
    ];
    $data[] = [
      [
        'box_caption' => 'title_alt',
        'lightbox' => TRUE,
        'media_switch' => 'blazy_test',
        'type' => 'image',
      ],
    ];
    $data[] = [
      [
        'box_caption' => 'entity_title',
        'lightbox' => TRUE,
        'media_switch' => 'blazy_test',
        'type' => 'image',
      ],
    ];
    $data[] = [
      [
        'box_caption' => 'custom',
        'box_caption_custom' => '[node:field_text_multiple]',
        'dimension' => '640x360',
        'embed_url' => '//www.youtube.com/watch?v=E03HFA923kw',
        'lightbox' => TRUE,
        'media_switch' => 'blazy_test',
        'type' => 'video',
      ],
    ];

    return $data;
  }

}
