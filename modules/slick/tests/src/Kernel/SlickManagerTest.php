<?php

namespace Drupal\Tests\slick\Kernel;

use Drupal\Tests\blazy\Kernel\BlazyKernelTestBase;
use Drupal\Tests\slick\Traits\SlickKernelTrait;
use Drupal\Tests\slick\Traits\SlickUnitTestTrait;
use Drupal\slick\Entity\Slick;
use Drupal\slick\SlickDefault;
use Drupal\slick_ui\Form\SlickForm;

/**
 * Tests the Slick manager methods.
 *
 * @coversDefaultClass \Drupal\slick\SlickManager
 *
 * @group slick
 */
class SlickManagerTest extends BlazyKernelTestBase {

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

    $bundle = $this->bundle;

    $this->messenger = $this->container->get('messenger');
    $this->slickAdmin = $this->container->get('slick.admin');
    $this->blazyAdminFormatter = $this->slickAdmin;
    $this->slickFormatter = $this->container->get('slick.formatter');
    $this->slickManager = $this->container->get('slick.manager');

    $this->slickForm = SlickForm::create($this->container);

    $this->testPluginId  = 'slick_image';
    $this->testFieldName = 'field_slick_image';
    $this->maxItems      = 7;
    $this->maxParagraphs = 2;

    $settings['fields']['field_text_multiple'] = 'text';
    $this->setUpContentTypeTest($bundle, $settings);
    $this->setUpContentWithItems($bundle);
    $this->setUpRealImage();

    $this->display = $this->setUpFormatterDisplay($bundle);
    $this->formatterInstance = $this->getFormatterInstance();
  }

  /**
   * Tests cases for various methods.
   *
   * @covers ::attach
   */
  public function testSlickManagerMethods() {
    $manager = $this->slickManager;
    $settings = [
      'media_switch'     => 'media',
      'lazy'             => 'ondemand',
      'mousewheel'       => TRUE,
      'skin'             => 'classic',
      'down_arrow'       => TRUE,
      'thumbnail_effect' => 'hover',
      'slick_css'        => TRUE,
      'module_css'       => TRUE,
    ] + $this->getFormatterSettings() + SlickDefault::extendedSettings();

    $attachments = $manager->attach($settings);
    $this->assertArrayHasKey('slick', $attachments['drupalSettings']);
  }

  /**
   * Tests for Slick build.
   *
   * @param bool $items
   *   Whether to provide items, or not.
   * @param array $settings
   *   The settings being tested.
   * @param array $options
   *   The options being tested.
   * @param mixed|bool|string $expected
   *   The expected output.
   *
   * @covers ::slick
   * @covers ::preRenderSlick
   * @covers ::buildGrid
   * @covers ::build
   * @covers ::preRenderSlickWrapper
   * @dataProvider providerTestSlickBuild
   */
  public function testBuild($items, array $settings, array $options, $expected) {
    $manager  = $this->slickManager;
    $defaults = $this->getFormatterSettings() + SlickDefault::htmlSettings();
    $settings = array_merge($defaults, $settings);

    $settings['optionset'] = 'test';

    $build = $this->display->build($this->entity);

    $items = !$items ? [] : $build[$this->testFieldName]['#build']['items'];
    $optionset = Slick::loadSafely($settings['optionset']);
    $build = [
      'items'     => $items,
      '#settings'  => $settings,
      '#options'   => $options,
      '#optionset' => $optionset,
    ];

    $slick['#build']['items'] = $items;
    $slick['#build']['#settings'] = $settings;
    $slick['#build']['#options'] = [];
    $slick['#build']['#optionset'] = $optionset;

    $element = $manager->preRenderSlick($slick);
    $this->assertEquals($expected, !empty($element));

    if (!empty($settings['optionset_thumbnail'])) {
      $build['thumb'] = [
        'items'    => $items,
        '#settings' => $settings,
        '#options'  => $options,
      ];
    }

    $slicks = $manager->build($build);
    $this->assertEquals($expected, !empty($slicks));

    $slicks['#build']['items'] = $items;
    $slicks['#build']['#settings'] = $settings;

    if (!empty($settings['optionset_thumbnail'])) {
      $slicks['#build']['thumb']['items'] = $build['thumb']['items'];
      $slicks['#build']['thumb']['#settings'] = $build['thumb']['#settings'];
    }

    $elements = $manager->preRenderSlickWrapper($slicks);
    $this->assertEquals($expected, !empty($elements));
  }

  /**
   * Provide test cases for ::testBuild().
   *
   * @return array
   *   An array of tested data.
   */
  public function providerTestSlickBuild() {
    $data[] = [
      TRUE,
      [
        'grid' => 3,
        'visible_items' => 6,
        'override' => TRUE,
        'overridables' => ['arrows' => FALSE, 'dots' => TRUE],
        'skin_dots' => 'dots',
        'cache' => -1,
        'cache_tags' => ['url.site'],
      ],
      ['dots' => TRUE],
      TRUE,
    ];
    $data[] = [
      TRUE,
      [
        'grid' => 3,
        'visible_items' => 6,
        'unslick' => TRUE,
      ],
      [],
      TRUE,
    ];
    $data[] = [
      TRUE,
      [
        'skin' => 'test',
        'nav' => TRUE,
        'optionset_thumbnail' => 'test_nav',
        'thumbnail_position' => 'top',
        'thumbnail_style' => 'thumbnail',
        'thumbnail_effect' => 'hover',

      ],
      [],
      TRUE,
    ];

    return $data;
  }

  /**
   * Tests for \Drupal\slick_ui\Form\SlickForm.
   *
   * @covers \Drupal\slick_ui\Form\SlickForm::typecastOptionset
   */
  public function testSlickForm() {
    $settings = [];
    $this->slickForm->typecastOptionset($settings);
    $this->assertEmpty($settings);

    $settings['mobileFirst'] = 1;
    $settings['edgeFriction'] = 0.27;
    $this->slickForm->typecastOptionset($settings);
    $this->assertEquals(TRUE, $settings['mobileFirst']);
  }

}
