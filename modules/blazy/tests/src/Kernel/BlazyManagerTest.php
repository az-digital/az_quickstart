<?php

namespace Drupal\Tests\blazy\Kernel;

use Drupal\blazy\Blazy;
use Drupal\blazy\BlazyDefault;
use Drupal\blazy\Theme\BlazyTheme;

/**
 * Tests the Blazy manager methods.
 *
 * @coversDefaultClass \Drupal\blazy\BlazyManager
 * @requires module media
 *
 * @group blazy
 */
class BlazyManagerTest extends BlazyKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $bundle = $this->bundle;

    $settings['fields']['field_text_multiple'] = 'text';

    $this->setUpContentTypeTest($bundle, $settings);
    $this->setUpContentWithItems($bundle);
    $this->setUpRealImage();
  }

  /**
   * Tests BlazyManager image.
   *
   * @param array $settings
   *   The settings being tested.
   * @param bool $expected_has_responsive_image
   *   Has the responsive image style ID.
   *
   * @covers ::preRenderBlazy
   * @covers ::postSettings
   * @covers \Drupal\blazy\Theme\Lightbox::build
   * @covers \Drupal\blazy\Theme\Lightbox::buildCaptions
   * @covers \Drupal\blazy\BlazyManager::postSettings
   * @dataProvider providerTestPreRenderImage
   */
  public function testPreRenderImage(array $settings, $expected_has_responsive_image = FALSE) {
    $build = $this->data;
    $url = $settings['content_url'] ?? '';
    $this->blazyManager->postSettings($settings);

    $blazies = $settings['blazies'];
    $blazies->set('count', $this->maxItems)
      ->set('entity.url', $url)
      ->set('media.embed_url', $settings['embed_url'] ?? '')
    // $blazies->set('is.lightbox', ($settings['lightbox'] ?? FALSE));
      ->set('media.type', $settings['type'] ?? '')
      ->set('image.uri', $this->uri);

    $settings['count'] = $this->maxItems;

    $build['#settings'] = array_merge($build['#settings'], $settings);
    $switch_css = str_replace('_', '-', $settings['media_switch']);

    $element = $this->doPreRenderImage($build);

    $blazies = $build['#settings']['blazies'];
    if ($url && $blazies->get('switch') == 'content') {
      $this->assertEquals($blazies->get('entity.url'), $element['#url']);
      $this->assertArrayHasKey('#url', $element);
    }
    elseif ($blazies->get('lightbox.name')) {
      $this->assertArrayHasKey('data-' . $switch_css . '-trigger', $element['#url_attributes']);
      $this->assertArrayHasKey('#url', $element);
    }

    /*
    // @todo re-check why failed since 2.9-DEV.
    // $blazies = $element['#settings']['blazies'];
    // $this->assertEquals($expected_has_responsive_image,
    // !empty($blazies->get('resimage.id')));
     */
  }

  /**
   * Provide test cases for ::testPreRenderImage().
   *
   * @return array
   *   An array of tested data.
   */
  public static function providerTestPreRenderImage() {
    $data[] = [
      [
        'content_url'  => 'node/1',
        'media_switch' => 'content',
      ],
      FALSE,
    ];
    $data[] = [
      [
        // 'lightbox'               => TRUE,
        'media_switch'           => 'blazy_test',
        'responsive_image_style' => 'blazy_responsive_test',
      ],
      TRUE,
    ];
    $data[] = [
      [
        'box_style'          => 'blazy_crop',
        'box_media_style'    => 'large',
        'box_caption'        => 'custom',
        'box_caption_custom' => '[node:field_text_multiple]',
        'embed_url'          => '//www.youtube.com/watch?v=E03HFA923kw',
        // 'lightbox'           => TRUE,
        'media_switch'       => 'blazy_test',
        'type'               => 'video',
      ],
      FALSE,
    ];

    return $data;
  }

  /**
   * Tests building Blazy attributes.
   *
   * @param array $settings
   *   The settings being tested.
   * @param bool $use_uri
   *   Whether to provide image URI, or not.
   * @param bool $use_item
   *   Whether to provide image item, or not.
   * @param bool $iframe
   *   Whether to expect an iframe, or not.
   * @param bool $expected
   *   Whether the expected output is an image.
   *
   * @covers \Drupal\blazy\Blazy::init
   * @covers \Drupal\blazy\Theme\BlazyTheme::blazy
   * @covers \Drupal\blazy\Media\BlazyImage::prepare
   * @covers \Drupal\blazy\BlazyDefault::entitySettings
   * @covers \Drupal\blazy\BlazyManager::postSettings
   * @covers \Drupal\blazy\Media\BlazyOEmbed::build
   * @covers \Drupal\blazy\Media\BlazyOEmbed::checkInputUrl
   * @dataProvider providerPreprocessBlazy
   */
  public function testPreprocessBlazy(array $settings, $use_uri, $use_item, $iframe, $expected) {
    $variables = ['attributes' => []];
    $input_url = $settings['input_url'] ?? NULL;
    $settings  = array_merge($this->getFormatterSettings(), $settings);
    $settings += Blazy::init();
    $blazies   = $settings['blazies'];
    $id        = 'blazy';

    $blazies->set('item.id', $id)
      ->set('is.blazy', TRUE)
      ->set('lazy.id', $id)
      ->set('image.uri', $use_uri ? $this->uri : '');

    $settings['image_style']     = 'blazy_crop';
    $settings['thumbnail_style'] = 'thumbnail';

    if ($input_url) {
      $settings = array_merge(BlazyDefault::entitySettings(), $settings);
    }

    $this->blazyManager->postSettings($settings);

    $blazies = $settings['blazies']->reset($settings);
    $item    = $use_item ? $this->testItem : NULL;

    if ($input_url) {
      $blazies->set('media.input_url', $input_url)
        ->set('media.source', 'oembed:video')
        ->set('media.bundle', 'remote_video')
        ->set('type', 'video');

      $data = [
        '#entity'   => $this->entity,
        '#settings' => $settings,
        '#item'     => $item,
      ];

      $this->blazyOembed->build($data);
      $settings = $data['#settings'];
    }

    $variables['element']['#item'] = $item;
    $variables['element']['#settings'] = $settings;

    BlazyTheme::blazy($variables);

    $image  = $expected == TRUE ? !empty($variables['image']) : empty($variables['image']);
    $iframe = $iframe == TRUE ? !empty($variables['iframe']) : empty($variables['iframe']);

    $this->assertTrue($image);
    $this->assertTrue($iframe);
  }

  /**
   * Provider for ::testPreprocessBlazy.
   */
  public static function providerPreprocessBlazy() {
    // $use_uri, $use_item, $iframe, $expected.
    $data[] = [
      [
        'background' => FALSE,
      ],
      FALSE,
      FALSE,
      FALSE,
      FALSE,
    ];
    $data[] = [
      [
        'background' => FALSE,
      ],
      TRUE,
      FALSE,
      FALSE,
      TRUE,
    ];
    $data[] = [
      [
        'background' => TRUE,
      ],
      FALSE,
      TRUE,
      FALSE,
      FALSE,
    ];
    $data[] = [
      [
        'background' => FALSE,
        'input_url' => 'https://www.youtube.com/watch?v=uny9kbh4iOEd',
        'media_switch' => 'media',
        'ratio' => 'fluid',
        // 'width' => 640,
        // 'height' => 360,
        // 'bundle' => 'remote_video',
        // 'type' => 'video',
      ],
      FALSE,
      TRUE,
      FALSE,
      TRUE,
    ];

    return $data;
  }

  /**
   * Tests responsive image integration.
   *
   * @param string $responsive_image_style_id
   *   The responsive_image_style_id.
   * @param bool $expected
   *   The expected output_image_tag.
   *
   * @dataProvider providerResponsiveImage
   */
  public function testPreprocessResponsiveImage($responsive_image_style_id, $expected) {
    $variables = [
      'item' => $this->testItem,
      'uri' => $this->uri,
      'responsive_image_style_id' => $responsive_image_style_id,
      'width' => 600,
      'height' => 480,
    ];

    template_preprocess_responsive_image($variables);

    $variables['img_element']['#uri'] = $this->uri;

    BlazyTheme::responsiveImage($variables);

    $this->assertEquals($expected, $variables['output_image_tag']);
  }

  /**
   * Provider for ::testPreprocessResponsiveImage.
   */
  public static function providerResponsiveImage() {
    return [
      'Responsive image with picture 8.x-3' => [
        'blazy_picture_test',
        FALSE,
      ],
      'Responsive image without picture 8.x-3' => [
        'blazy_responsive_test',
        TRUE,
      ],
    ];
  }

  /**
   * Tests cases for various methods.
   *
   * @covers ::attach
   */
  public function testBlazyManagerMethods() {
    // Tests Blazy attachments.
    $attach = ['blazy' => TRUE, 'media_switch' => 'blazy_test'];

    $attachments = $this->blazyManager->attach($attach);
    $this->assertArrayHasKey('blazy', $attachments['drupalSettings']);
  }

}
