<?php

namespace Drupal\Tests\blazy\Kernel;

/**
 * Tests the Blazy entity methods.
 *
 * @coversDefaultClass \Drupal\blazy\BlazyEntity
 * @requires module media
 *
 * @group blazy
 */
class BlazyEntityTest extends BlazyKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $bundle = $this->bundle;

    $settings['fields']['field_text_multiple'] = 'text';

    $this->setUpContentTypeTest($bundle, $settings);
    $this->setUpContentWithItems($bundle);
    $this->setUpRealImage();
  }

  /**
   * Tests the entity view builder.
   *
   * @param string $entity
   *   The tested entity.
   * @param string $fallback
   *   The fallback text.
   * @param string $message
   *   The message text.
   * @param bool $expected
   *   The expected output.
   *
   * @covers ::view
   * @dataProvider providerTestGetEntityView
   */
  public function testGetEntityView($entity, $fallback, $message, $expected) {
    if ($entity == 'node') {
      $entity = empty($this->entity) ? $this->setUpContentWithItems($this->bundle) : $this->entity;
    }
    elseif ($entity == 'responsive_image') {
      $entity = $this->blazyManager->load('blazy_responsive_test', 'responsive_image_style');
    }
    elseif ($entity == 'image') {
      $entity = $this->testItem;
    }

    $data = [
      '#entity' => $entity,
      '#settings' => [],
      'fallback' => $fallback,
    ];
    $result = $this->blazyEntity->view($data);
    $this->assertSame($expected, !empty($result), $message);
  }

  /**
   * Provide test cases for ::testGetEntityView().
   *
   * @return array
   *   An array of tested data.
   */
  public static function providerTestGetEntityView() {
    return [
      'Node' => [
        'node',
        '',
        'Node has node_view() taking precedence over view builder.',
        TRUE,
      ],
      'Responsive image' => [
        'responsive_image',
        'This is some fallback text.',
        'Responsive image has no view builder. Fallback to text.',
        TRUE,
      ],
      'Image' => [
        'image',
        '',
        'Image is not an instance of EntityInterface, returns false.',
        FALSE,
      ],
    ];
  }

}
