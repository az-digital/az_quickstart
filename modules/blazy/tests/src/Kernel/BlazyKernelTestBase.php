<?php

namespace Drupal\Tests\blazy\Kernel;

use Drupal\Tests\blazy\Traits\BlazyKernelTestTrait;
use Drupal\Tests\field\Kernel\FieldKernelTestBase;

/**
 * Defines base class for the Blazy formatter tests.
 */
abstract class BlazyKernelTestBase extends FieldKernelTestBase {

  use BlazyKernelTestTrait;

  /**
   * Set to TRUE to strict check all configuration saved.
   *
   * Let's keep it enabled here for just in case core breaks it again related to
   * resimage.styles.blazy_picture_test.
   *
   * @var bool
   * @see \Drupal\Core\Config\Development\ConfigSchemaChecker
   */
  protected $strictConfigSchema = TRUE;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'system',
    'user',
    // @todo 'entity_test',
    'field',
    'field_ui',
    'file',
    'filter',
    'image',
    'media',
    'breakpoint',
    'responsive_image',
    'node',
    'text',
    'views',
    'blazy',
    'blazy_ui',
    'blazy_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->setUpVariables();
    $this->setUpKernelInstall();
    $this->setUpKernelManager();
  }

}
