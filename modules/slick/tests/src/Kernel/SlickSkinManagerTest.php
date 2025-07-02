<?php

namespace Drupal\Tests\slick\Kernel;

use Drupal\Tests\blazy\Kernel\BlazyKernelTestBase;
use Drupal\Tests\slick\Traits\SlickKernelTrait;
use Drupal\Tests\slick\Traits\SlickUnitTestTrait;

/**
 * Tests the Slick skin manager methods.
 *
 * @coversDefaultClass \Drupal\slick\SlickSkinManager
 *
 * @group slick
 */
class SlickSkinManagerTest extends BlazyKernelTestBase {

  use SlickUnitTestTrait;
  use SlickKernelTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'file',
    'filter',
    'image',
    'node',
    'text',
    'blazy',
    'slick',
    'slick_ui',
    'slick_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installConfig([
      'field',
      'image',
      'media',
      'responsive_image',
      'node',
      'views',
      'blazy',
      'slick',
      'slick_ui',
    ]);

    $this->slickSkinManager = $this->container->get('slick.skin_manager');
  }

  /**
   * Tests cases for various methods.
   *
   * @covers ::getSkins
   * @covers ::getSkinsByGroup
   * @covers ::libraryInfoBuild
   */
  public function testSlickManagerMethods() {
    $skins = $this->slickSkinManager->getSkins();
    $this->assertArrayHasKey('skins', $skins);
    $this->assertArrayHasKey('arrows', $skins);
    $this->assertArrayHasKey('dots', $skins);

    // Verify we have cached skins.
    $cid = 'slick_skins_data';
    $cached_skins = $this->slickSkinManager->getCache()->get($cid);
    $this->assertEquals($cid, $cached_skins->cid);
    $this->assertEquals($skins, $cached_skins->data);

    // Verify skins has thumbnail constant.
    $defined_skins = $this->slickSkinManager->getConstantSkins();
    $this->assertTrue(in_array('thumbnail', $defined_skins));

    // Verify libraries.
    $libraries = $this->slickSkinManager->libraryInfoBuild();
    $this->assertArrayHasKey('slick.main.default', $libraries);

    // Tests for Drupal\slick_test\Plugin\slick\SlickSkin as a plugin.
    $skins = $this->slickSkinManager->getSkinsByGroup('dots');
    $this->assertArrayHasKey('dots', $skins);

    $skins = $this->slickSkinManager->getSkinsByGroup('arrows');
    $this->assertArrayHasKey('arrows', $skins);
  }

}
