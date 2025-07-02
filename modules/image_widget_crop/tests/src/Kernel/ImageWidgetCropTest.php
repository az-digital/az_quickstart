<?php

namespace Drupal\Tests\image_widget_crop\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Minimal test case for the image_widget_crop module.
 *
 * @group image_widget_crop
 *
 * @ingroup media
 */
class ImageWidgetCropTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'file',
    'image',
    'crop',
    'image_widget_crop',
  ];

  /**
   * Tests that image_widget_crop_requirements() returns an array.
   */
  public function testRequirements() {
    $this->container->get('module_handler')->loadInclude('image_widget_crop', 'install');
    $requirements = image_widget_crop_requirements('runtime');
    $this->assertIsArray($requirements);
  }

}
