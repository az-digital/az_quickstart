<?php

namespace Drupal\Tests\blazy\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\Tests\blazy\Traits\BlazyManagerUnitTestTrait;
use Drupal\Tests\blazy\Traits\BlazyUnitTestTrait;

/**
 * @coversDefaultClass \Drupal\blazy\BlazyManager
 *
 * @group blazy
 */
class BlazyManagerUnitTest extends UnitTestCase {

  use BlazyUnitTestTrait;
  use BlazyManagerUnitTestTrait;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->setUpUnitServices();
    $this->setUpUnitContainer();
    $this->setUpUnitImages();
  }

  /**
   * Tests cases for various methods.
   *
   * @covers ::entityTypeManager
   * @covers ::moduleHandler
   * @covers ::renderer
   * @covers ::cache
   * @covers ::configFactory
   */
  public function testBlazyManagerServiceInstances() {
    $this->assertInstanceOf('\Drupal\blazy\Asset\LibrariesInterface', $this->blazyManager->libraries());
    $this->assertInstanceOf('\Drupal\Core\Entity\EntityTypeManagerInterface', $this->blazyManager->entityTypeManager());
    $this->assertInstanceOf('\Drupal\Core\Extension\ModuleHandlerInterface', $this->blazyManager->moduleHandler());
    $this->assertInstanceOf('\Drupal\Core\Render\RendererInterface', $this->blazyManager->renderer());
    $this->assertInstanceOf('\Drupal\Core\Config\ConfigFactoryInterface', $this->blazyManager->configFactory());
    $this->assertInstanceOf('\Drupal\Core\Cache\CacheBackendInterface', $this->blazyManager->cache());
    $this->assertInstanceOf('\Drupal\Core\Language\LanguageManager', $this->blazyManager->languageManager());
  }

  /**
   * Tests cases for config.
   *
   * @covers ::config
   */
  public function testConfigLoad() {
    // @phpstan-ignore-next-line
    $this->blazyManager->expects($this->any())
      ->method('config')
      ->with('blazy')
      ->willReturn(['loadInvisible' => FALSE]);

    $blazy = $this->blazyManager->config('blazy');
    $this->assertArrayHasKey('loadInvisible', $blazy);
    // @phpstan-ignore-next-line
    $this->blazyManager->expects($this->any())
      ->method('config')
      ->with('admin_css')
      ->willReturn(TRUE);
  }

  /**
   * Tests cases for config.
   *
   * @covers ::load
   * @covers ::loadMultiple
   */
  public function testEntityLoadImageStyle() {
    $styles = $this->setUpImageStyle();
    $ids = array_keys($styles);
    // @phpstan-ignore-next-line
    $this->blazyManager->expects($this->any())
      ->method('loadMultiple')
      ->with('image_style')
      ->willReturn($styles);

    $multiple = $this->blazyManager->loadMultiple('image_style', $ids);
    $this->assertArrayHasKey('large', $multiple);
    // @phpstan-ignore-next-line
    $this->blazyManager->expects($this->any())
      ->method('load')
      ->with('large')
      ->willReturn($multiple['large']);

    $expected = $this->blazyManager->load('large', 'image_style');
    $this->assertEquals($expected, $multiple['large']);
  }

  /**
   * Tests for \Drupal\blazy\BlazyManager::getBlazy().
   *
   * @covers ::getBlazy
   * @dataProvider providerTestGetBlazy
   */
  public function testGetBlazy($uri, $content, $expected_image, $expected_render) {
    $build = [];
    $build['#item'] = NULL;
    $build['content'] = $content;
    $build['#settings']['uri'] = $uri;

    $theme = ['#theme' => 'blazy', '#build' => []];
    // @phpstan-ignore-next-line
    $this->blazyManager->expects($this->any())
      ->method('getBlazy')
      ->willReturn($expected_image ? $theme : []);

    $image = $this->blazyManager->getBlazy($build);
    $check_image = !$expected_image ? empty($image) : !empty($image);
    $this->assertTrue($check_image);
  }

  /**
   * Provide test cases for ::testPreRenderImage().
   *
   * @return array
   *   An array of tested data.
   */
  public static function providerTestGetBlazy() {
    $data[] = [
      '',
      '',
      FALSE,
      FALSE,
    ];
    $data[] = [
      'core/misc/druplicon.png',
      '',
      TRUE,
      TRUE,
    ];
    $data[] = [
      'core/misc/druplicon.png',
      '<iframe src="//www.youtube.com/watch?v=E03HFA923kw" class="b-lazy"></iframe>',
      FALSE,
      TRUE,
    ];

    return $data;
  }

  /**
   * Tests cases for attachments.
   *
   * @covers ::attach
   * @depends testConfigLoad
   */
  public function testAttach() {
    $attach = [
      'blazy'        => TRUE,
      'grid'         => 0,
      'media'        => TRUE,
      'media_switch' => 'media',
      'ratio'        => 'fluid',
      'style'        => 'column',
    ];
    // @phpstan-ignore-next-line
    $this->blazyManager->expects($this->any())
      ->method('attach')
      ->with($attach)
      ->willReturn(['drupalSettings' => ['blazy' => []]]);

    $attachments = $this->blazyManager->attach($attach);
    // @phpstan-ignore-next-line
    $this->blazyManager->expects($this->any())
      ->method('attach')
      ->with($attach)
      ->willReturn(['drupalSettings' => ['blazy' => []]]);
    $this->assertArrayHasKey('blazy', $attachments['drupalSettings']);
  }

  /**
   * Tests cases for lightboxes.
   *
   * @covers ::getLightboxes
   */
  public function testGetLightboxes() {
    // @phpstan-ignore-next-line
    $this->blazyManager->expects($this->any())
      ->method('getLightboxes')
      ->willReturn([]);

    $lightboxes = $this->blazyManager->getLightboxes();

    $this->assertNotContains('nixbox', $lightboxes);
  }

}

namespace Drupal\blazy;

if (!function_exists('blazy_test_theme')) {

  /**
   * Dummy function.
   */
  function blazy_test_theme() {
    // Empty block to satisfy coder.
  }

}
