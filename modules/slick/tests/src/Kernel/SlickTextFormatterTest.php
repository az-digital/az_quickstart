<?php

namespace Drupal\Tests\slick\Kernel;

use Drupal\Core\Form\FormState;
use Drupal\Tests\blazy\Kernel\BlazyKernelTestBase;
use Drupal\Tests\slick\Traits\SlickKernelTrait;
use Drupal\Tests\slick\Traits\SlickUnitTestTrait;

/**
 * Tests the Slick field rendering using the text field type.
 *
 * @coversDefaultClass \Drupal\slick\Plugin\Field\FieldFormatter\SlickTextFormatter
 * @group slick
 */
class SlickTextFormatterTest extends BlazyKernelTestBase {

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
    'image',
    'filter',
    'node',
    'text',
    'blazy',
    'slick',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installConfig(static::$modules);
    $this->installEntitySchema('slick');

    $this->testFieldName  = 'field_text_multiple';
    $this->testEmptyName  = 'field_text_multiple_empty';
    $this->testFieldType  = 'text';
    $this->testPluginId   = 'slick_text';
    $this->maxItems       = 7;
    $this->maxParagraphs  = 2;
    $this->slickAdmin     = $this->container->get('slick.admin');
    $this->slickManager   = $this->container->get('slick.manager');
    $this->slickFormatter = $this->container->get('slick.formatter');

    // Create contents.
    $bundle = $this->bundle;

    $data = [
      'field_name' => $this->testEmptyName,
      'field_type' => 'text',
    ];

    $this->setUpContentTypeTest($bundle, $data);
    $this->setUpContentWithItems($bundle);

    $this->display = $this->setUpFormatterDisplay($bundle);

    $data['plugin_id'] = $this->testPluginId;
    $this->displayEmpty = $this->setUpFormatterDisplay($bundle, $data);

    $this->formatterInstance = $this->getFormatterInstance();
  }

  /**
   * Tests the Slick formatters.
   */
  public function testSlickFormatter() {
    $entity = $this->entity;

    // Generate the render array to verify if the cache tags are as expected.
    $build = $this->display->build($entity);
    $build_empty = $this->displayEmpty->build($entity);

    $component = $this->display->getComponent($this->testFieldName);
    $this->assertEquals($this->testPluginId, $component['type']);

    $render = $this->slickManager->renderer()->renderRoot($build);
    $this->assertNotEmpty($render);

    $render_empty = $this->slickManager->renderer()->renderRoot($build_empty[$this->testEmptyName]);
    $this->assertEmpty($render_empty);

    $scopes = $this->formatterInstance->buildSettings();
    $this->assertEquals($this->testPluginId, $scopes['blazies']->get('field.plugin_id'));

    $form = [];
    $form_state = new FormState();
    $element = $this->formatterInstance->settingsForm($form, $form_state);
    $this->assertArrayHasKey('optionset', $element);
  }

}
