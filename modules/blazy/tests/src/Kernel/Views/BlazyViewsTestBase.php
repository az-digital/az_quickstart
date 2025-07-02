<?php

namespace Drupal\Tests\blazy\Kernel\Views;

use Drupal\Tests\blazy\Traits\BlazyKernelTestTrait;
use Drupal\Tests\views\Kernel\ViewsKernelTestBase;

/**
 * Defines base class for Blazy Views integration.
 */
abstract class BlazyViewsTestBase extends ViewsKernelTestBase {

  use BlazyKernelTestTrait;

  /**
   * Set to TRUE to strict check all configuration saved.
   *
   * Let's keep it enabled here for just in case core breaks it again related to
   * views.view.test_blazy_entity.
   *
   * @var bool
   * @see \Drupal\Core\Config\Development\ConfigSchemaChecker
   */
  protected $strictConfigSchema = TRUE;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'file',
    'image',
    'media',
    'breakpoint',
    'responsive_image',
    'filter',
    'link',
    'node',
    'text',
    'options',
    // @todo 'entity_test',
    'views',
    'blazy',
    'blazy_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE): void {
    parent::setUp($import_test_views);

    $this->setUpVariables();
    $this->setUpKernelInstall();
    $this->setUpKernelManager();
    $this->setUpRealImage();
  }

}
