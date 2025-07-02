<?php

namespace Drupal\Tests\slick\Traits;

/**
 * A Trait common for Slick tests.
 */
trait SlickKernelTrait {

  /**
   * The slick admin service.
   *
   * @var \Drupal\slick\Form\SlickAdminInterface
   */
  protected $slickAdmin;

  /**
   * The slick formatter service.
   *
   * @var \Drupal\slick\SlickFormatterInterface
   */
  protected $slickFormatter;

  /**
   * The slick manager service.
   *
   * @var \Drupal\slick\SlickManagerInterface
   */
  protected $slickManager;

  /**
   * The slick settings form.
   *
   * @var \Drupal\slick_ui\Form\SlickForm
   */
  protected $slickForm;

  /**
   * The slick manager service.
   *
   * @var \Drupal\slick\SlickSkinManagerInterface
   */
  protected $slickSkinManager;

}
