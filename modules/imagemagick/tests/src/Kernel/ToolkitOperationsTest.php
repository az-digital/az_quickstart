<?php

declare(strict_types=1);

namespace Drupal\Tests\imagemagick\Kernel;

use Drupal\imagemagick\ArgumentMode;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests for ImageMagick toolkit operations.
 *
 * @group imagemagick
 */
class ToolkitOperationsTest extends KernelTestBase {

  use ToolkitSetupTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'imagemagick',
    'system',
    'file_mdm',
    'user',
    'sophron',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['system', 'imagemagick', 'sophron']);
  }

  /**
   * Create a new image and inspect the arguments.
   *
   * @param string $toolkit_id
   *   The id of the toolkit to set up.
   * @param string $toolkit_config
   *   The config object of the toolkit to set up.
   * @param array $toolkit_settings
   *   The settings of the toolkit to set up.
   *
   * @dataProvider providerToolkitConfiguration
   */
  public function testCreateNewImageArguments(string $toolkit_id, string $toolkit_config, array $toolkit_settings): void {
    $this->setUpToolkit($toolkit_id, $toolkit_config, $toolkit_settings);
    $image = $this->imageFactory->get();
    /** @var \Drupal\imagemagick\Plugin\ImageToolkit\ImagemagickToolkit $toolkit */
    $toolkit = $image->getToolkit();
    $image->createNew(100, 200);
    $this->assertSame([0, 1, 2], array_keys($toolkit->arguments()->find('/^./', NULL, ['image_toolkit_operation' => 'create_new'])));
    $this->assertSame([0, 1, 2], array_keys($toolkit->arguments()->find('/^./', NULL, ['image_toolkit_operation_plugin_id' => 'imagemagick_create_new'])));
    $this->assertSame(['-size', '100x200', 'xc:transparent'], $toolkit->arguments()->toArray(ArgumentMode::PostSource));
    $this->assertSame("[-size] [100x200] [xc:transparent]", $toolkit->arguments()->toDebugString(ArgumentMode::PostSource));
  }

  /**
   * Test failures of CreateNew.
   *
   * @param string $toolkit_id
   *   The id of the toolkit to set up.
   * @param string $toolkit_config
   *   The config object of the toolkit to set up.
   * @param array $toolkit_settings
   *   The settings of the toolkit to set up.
   *
   * @dataProvider providerToolkitConfiguration
   */
  public function testCreateNewImageFailures(string $toolkit_id, string $toolkit_config, array $toolkit_settings): void {
    $this->setUpToolkit($toolkit_id, $toolkit_config, $toolkit_settings);
    $image = $this->imageFactory->get();
    $image->createNew(-50, 20);
    $this->assertFalse($image->isValid(), 'CreateNew with negative width fails.');
    $image->createNew(50, 20, 'foo');
    $this->assertFalse($image->isValid(), 'CreateNew with invalid extension fails.');
    $image->createNew(50, 20, 'gif', '#foo');
    $this->assertFalse($image->isValid(), 'CreateNew with invalid color hex string fails.');
    $image->createNew(50, 20, 'gif', '#ff0000');
    $this->assertTrue($image->isValid(), 'CreateNew with valid arguments validates the Image.');
  }

  /**
   * Test operations on image with no dimensions.
   *
   * @param string $toolkit_id
   *   The id of the toolkit to set up.
   * @param string $toolkit_config
   *   The config object of the toolkit to set up.
   * @param array $toolkit_settings
   *   The settings of the toolkit to set up.
   *
   * @dataProvider providerToolkitConfiguration
   */
  public function testOperationsOnImageWithNoDimensions(string $toolkit_id, string $toolkit_config, array $toolkit_settings): void {
    $this->setUpToolkit($toolkit_id, $toolkit_config, $toolkit_settings);
    $image = $this->imageFactory->get();
    /** @var \Drupal\imagemagick\Plugin\ImageToolkit\ImagemagickToolkit $toolkit */
    $toolkit = $image->getToolkit();
    $image->createNew(100, 200);
    $this->assertSame(100, $image->getWidth());
    $this->assertsame(200, $image->getHeight());
    $toolkit->setWidth(NULL);
    $toolkit->setHeight(NULL);
    $this->assertNull($image->getWidth());
    $this->assertNull($image->getHeight());
    $this->assertFalse($image->crop(10, 10, 20, 20));
    $this->assertNull($image->getWidth());
    $this->assertNull($image->getHeight());
    $this->assertFalse($image->scaleAndCrop(10, 10));
    $this->assertNull($image->getWidth());
    $this->assertNull($image->getHeight());
    $this->assertFalse($image->scale(5));
    $this->assertNull($image->getWidth());
    $this->assertNull($image->getHeight());
    // Resize sets explicitly the new dimension, so it should not fail.
    $this->assertTrue($image->resize(50, 100));
    $this->assertSame(50, $image->getWidth());
    $this->assertsame(100, $image->getHeight());
    $this->assertSame("[-size] [100x200] [xc:transparent] [-resize] [50x100!]", $toolkit->arguments()->toDebugString(ArgumentMode::PostSource));
  }

  /**
   * Test 'scale_and_crop' operation.
   *
   * @param string $toolkit_id
   *   The id of the toolkit to set up.
   * @param string $toolkit_config
   *   The config object of the toolkit to set up.
   * @param array $toolkit_settings
   *   The settings of the toolkit to set up.
   *
   * @dataProvider providerToolkitConfiguration
   */
  public function testScaleAndCropOperation(string $toolkit_id, string $toolkit_config, array $toolkit_settings): void {
    $this->setUpToolkit($toolkit_id, $toolkit_config, $toolkit_settings);
    $image = $this->imageFactory->get();
    /** @var \Drupal\imagemagick\Plugin\ImageToolkit\ImagemagickToolkit $toolkit */
    $toolkit = $image->getToolkit();
    $image->createNew(100, 200);
    $image->apply('scale_and_crop', [
      'x' => 1,
      'y' => 1,
      'width' => 5,
      'height' => 10,
    ]);
    $this->assertSame("[-size] [100x200] [xc:transparent] [-resize] [5x10!] [-crop] [5x10+1+1] [+repage]", $toolkit->arguments()->toDebugString(ArgumentMode::PostSource));
  }

  /**
   * Test 'scale_and_crop' operation with no anchor passed in.
   *
   * @param string $toolkit_id
   *   The id of the toolkit to set up.
   * @param string $toolkit_config
   *   The config object of the toolkit to set up.
   * @param array $toolkit_settings
   *   The settings of the toolkit to set up.
   *
   * @dataProvider providerToolkitConfiguration
   */
  public function testScaleAndCropNoAnchorOperation(string $toolkit_id, string $toolkit_config, array $toolkit_settings): void {
    $this->setUpToolkit($toolkit_id, $toolkit_config, $toolkit_settings);
    $image = $this->imageFactory->get();
    /** @var \Drupal\imagemagick\Plugin\ImageToolkit\ImagemagickToolkit $toolkit */
    $toolkit = $image->getToolkit();
    $image->createNew(100, 200);
    $image->apply('scale_and_crop', ['width' => 5, 'height' => 10]);
    $this->assertSame("[-size] [100x200] [xc:transparent] [-resize] [5x10!] [-crop] [5x10+0+0] [+repage]", $toolkit->arguments()->toDebugString(ArgumentMode::PostSource));
  }

}
