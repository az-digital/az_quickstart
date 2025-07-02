<?php

namespace Drupal\Tests\slick\Kernel;

use Drupal\Tests\blazy\Kernel\BlazyKernelTestBase;
use Drupal\Tests\slick\Traits\SlickKernelTrait;
use Drupal\Tests\slick\Traits\SlickUnitTestTrait;
use Drupal\slick\SlickDefault;

/**
 * Tests the Slick field rendering using the image field type.
 *
 * @coversDefaultClass \Drupal\slick\Plugin\Field\FieldFormatter\SlickImageFormatter
 * @group slick
 */
class SlickFormatterTest extends BlazyKernelTestBase {

  use SlickUnitTestTrait;
  use SlickKernelTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'system',
    'user',
    'help',
    'field',
    'file',
    'image',
    'filter',
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

    $this->installConfig(static::$modules);
    $this->installEntitySchema('slick');

    $this->testFieldName  = 'field_image_multiple';
    $this->testEmptyName  = 'field_image_multiple_empty';
    $this->testPluginId   = 'slick_image';
    $this->maxItems       = 7;
    $this->maxParagraphs  = 2;
    $this->slickAdmin     = $this->container->get('slick.admin');
    $this->slickManager   = $this->container->get('slick.manager');
    $this->slickFormatter = $this->container->get('slick.formatter');

    $data['fields'] = [
      'field_video'                => 'text',
      'field_image'                => 'image',
      'field_image_multiple_empty' => 'image',
    ];

    // Create contents.
    $bundle = $this->bundle;
    $this->setUpContentTypeTest($bundle, $data);

    $settings = [
      'optionset' => 'test',
      'optionset_thumbnail' => 'test_nav',
    ] + $this->getFormatterSettings() + SlickDefault::extendedSettings();

    $data['settings'] = $settings;
    $this->display = $this->setUpFormatterDisplay($bundle, $data);

    $data['plugin_id'] = $this->testPluginId;
    $this->displayEmpty = $this->setUpFormatterDisplay($bundle, $data);

    $this->formatterInstance = $this->getFormatterInstance();
    $this->skins = $this->slickManager->skinManager()->getSkins();

    $this->setUpContentWithItems($bundle);
    $this->setUpRealImage();
  }

  /**
   * Tests the Slick formatters.
   */
  public function testSlickFormatter() {
    $entity = $this->entity;

    // Generate the render array to verify if the cache tags are as expected.
    $build = $this->display->build($entity);
    $build_empty = $this->displayEmpty->build($entity);

    $render = $this->slickManager->renderer()->renderRoot($build);
    $this->assertNotEmpty($render);

    $render_empty = $this->slickManager->renderer()->renderRoot($build_empty[$this->testEmptyName]);
    $this->assertEmpty($render_empty);

    $this->assertInstanceOf('\Drupal\Core\Field\FieldItemListInterface', $this->testItems);
    $this->assertInstanceOf('\Drupal\slick\Form\SlickAdminInterface', $this->formatterInstance->admin());
    $this->assertInstanceOf('\Drupal\slick\SlickFormatterInterface', $this->formatterInstance->formatter());
    $this->assertInstanceOf('\Drupal\slick\SlickManagerInterface', $this->formatterInstance->manager());

    $component = $this->display->getComponent($this->testFieldName);
    $this->assertEquals($this->testPluginId, $component['type']);
    $this->assertEquals($this->testPluginId, $build[$this->testFieldName]['#formatter']);

    $scopes = $this->formatterInstance->getScopedFormElements();
    $this->assertEquals('slick', $scopes['blazies']->get('namespace'));
    $this->assertArrayHasKey('optionset', $scopes['settings']);

    $summary = $this->formatterInstance->settingsSummary();
    $this->assertNotEmpty($summary);
  }

  /**
   * Tests for \Drupal\slick\SlickFormatter::testGetThumbnail().
   *
   * @param string $uri
   *   The uri being tested.
   * @param bool $expected
   *   The expected output.
   *
   * @covers \Drupal\slick\SlickFormatter::getThumbnail
   * @dataProvider providerTestGetThumbnail
   */
  public function testGetThumbnail($uri, $expected) {
    $settings = $this->getFormatterSettings() + SlickDefault::extendedSettings();
    $blazies = $settings['blazies'];

    $blazies->set('image.uri', empty($uri) ? '' : $this->uri)
      ->set('thumbnail.id', 'thumbnail');

    // $item = $use_item ? $this->testItem : NULL;
    $thumbnail = $this->slickFormatter->getThumbnail($settings);
    $this->assertEquals($expected, !empty($thumbnail));
  }

  /**
   * Provide test cases for ::testGetThumbnail().
   *
   * @return array
   *   An array of tested data.
   */
  public function providerTestGetThumbnail() {
    $data[] = [
      '',
      FALSE,
    ];
    $data[] = [
      'public://example.jpg',
      TRUE,
    ];

    return $data;
  }

  /**
   * Tests for \Drupal\slick\SlickFormatter.
   *
   * @param array $settings
   *   The settings being tested.
   * @param mixed|bool|string $expected
   *   The expected output.
   *
   * @covers \Drupal\slick\SlickFormatter::buildSettings
   * @dataProvider providerTestBuildSettings
   */
  public function testBuildSettings(array $settings, $expected) {
    $format['#settings'] = array_merge($this->getFormatterSettings(), $settings) + SlickDefault::extendedSettings();

    $this->slickFormatter->preBuildElements($format, $this->testItems);
    $this->assertArrayHasKey('blazies', $format['#settings']);
  }

  /**
   * Provide test cases for ::testBuildSettings().
   *
   * @return array
   *   An array of tested data.
   */
  public function providerTestBuildSettings() {
    $data[] = [
      [
        'vanilla' => TRUE,
      ],
      FALSE,
    ];
    $data[] = [
      [
        'vanilla' => FALSE,
        'blazy' => FALSE,
        'ratio' => 'fluid',
      ],
      TRUE,
    ];
    $data[] = [
      [
        'vanilla' => FALSE,
        'blazy' => TRUE,
      ],
      TRUE,
    ];

    return $data;
  }

  /**
   * Tests for \Drupal\slick\Form\SlickAdmin.
   *
   * @covers \Drupal\slick\Form\SlickAdmin::buildSettingsForm
   * @covers \Drupal\slick\Form\SlickAdmin::openingForm
   * @covers \Drupal\slick\Form\SlickAdmin::imageStyleForm
   * @covers \Drupal\slick\Form\SlickAdmin::fieldableForm
   * @covers \Drupal\slick\Form\SlickAdmin::mediaSwitchForm
   * @covers \Drupal\slick\Form\SlickAdmin::gridForm
   * @covers \Drupal\slick\Form\SlickAdmin::closingForm
   * @covers \Drupal\slick\Form\SlickAdmin::finalizeForm
   * @covers \Drupal\slick\Form\SlickAdmin::getOverridableOptions
   * @covers \Drupal\slick\Form\SlickAdmin::getLayoutOptions
   * @covers \Drupal\slick\Form\SlickAdmin::getOptionsetsByGroupOptions
   * @covers \Drupal\slick\Form\SlickAdmin::getSkinsByGroupOptions
   * @covers \Drupal\slick\Form\SlickAdmin::getSettingsSummary
   * @covers \Drupal\slick\Form\SlickAdmin::getFieldOptions
   */
  public function testAdminOptions() {
    $definition = $this->getSlickFormatterDefinition();
    $form['test'] = ['#type' => 'hidden'];

    $this->slickAdmin->buildSettingsForm($form, $definition);
    $this->assertArrayHasKey('optionset', $form);

    $options = $this->slickAdmin->getOverridableOptions();
    $this->assertArrayHasKey('arrows', $options);

    $options = $this->slickAdmin->getLayoutOptions();
    $this->assertArrayHasKey('bottom', $options);

    $options = $this->slickAdmin->getOptionsetsByGroupOptions();
    $this->assertArrayHasKey('default', $options);

    $options = $this->slickAdmin->getOptionsetsByGroupOptions('main');
    $this->assertArrayHasKey('test', $options);

    $options = $this->slickAdmin->getSkinsByGroupOptions('main');
    $this->assertArrayHasKey('classic', $options);

    $summary = $this->slickAdmin->getSettingsSummary($definition);
    $this->assertNotEmpty($summary);

    $options = $this->slickAdmin->getFieldOptions([], [], 'node');
    $this->assertArrayHasKey($this->testFieldName, $options);
  }

}
