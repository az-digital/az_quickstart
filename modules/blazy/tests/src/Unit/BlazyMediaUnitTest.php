<?php

namespace Drupal\Tests\blazy\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\Tests\blazy\Traits\BlazyUnitTestTrait;
use Drupal\blazy\Blazy;

/**
 * @coversDefaultClass \Drupal\blazy\BlazyMedia
 *
 * @group blazy
 */
class BlazyMediaUnitTest extends UnitTestCase {

  use BlazyUnitTestTrait;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->setUpVariables();
    $this->setUpUnitImages();
  }

  /**
   * Tests \Drupal\blazy\Media\BlazyMedia::view().
   *
   * @covers ::view
   * @dataProvider providerTestBlazyMediaBuild
   */
  public function testBlazyMediaBuild($markup) {
    $source_field = $this->randomMachineName();
    $view_mode = 'default';
    $settings = [
      'image_style'  => 'blazy_crop',
      'ratio'        => 'fluid',
      'view_mode'    => 'default',
      'media_switch' => 'media',
      // @todo 'bundle' => 'entity_test',
    ] + Blazy::init();

    $blazies = $settings['blazies'];
    $info = [
      // 'input_url'    => $input_url,
      'source_field' => $source_field,
      'source'       => 'remote_video',
      'view_mode'    => $view_mode,
    ];

    $blazies->set('media', $info);

    $markup['#settings'] = $settings;
    $markup['#attached'] = [];
    $markup['#cache']    = [];

    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $this->createMock('\Drupal\Core\Entity\ContentEntityInterface');
    $field_definition = $this->createMock('\Drupal\Core\Field\FieldDefinitionInterface');

    $items = $this->createMock('\Drupal\Core\Field\FieldItemListInterface');

    // Since 2.17.
    $this->blazyMedia = $this->createMock('\Drupal\blazy\Media\BlazyMediaInterface');
    // @phpstan-ignore-next-line
    $items->expects($this->any())
      ->method('getFieldDefinition')
      ->willReturn($field_definition);
    // @phpstan-ignore-next-line
    $items->expects($this->any())
      ->method('view')
      ->with($view_mode)
      ->willReturn($markup);
    // @phpstan-ignore-next-line
    $items->expects($this->any())
      ->method('getEntity')
      ->willReturn($entity);
    // @phpstan-ignore-next-line
    $entity->expects($this->any())
      ->method('get')
      ->with($source_field)
      ->will($this->returnValue($items));

    $data = [
      '#entity' => $entity,
      '#settings' => $settings,
    ];

    $this->blazyMedia->expects($this->any())
      ->method('view')
      ->with($data)
      ->willReturn($markup);

    $render = $this->blazyMedia->view($data);
    $this->assertArrayHasKey('#settings', $render);
  }

  /**
   * Provider for ::testBlazyMediaBuild.
   */
  public static function providerTestBlazyMediaBuild() {
    $iframe = [
      '#type' => 'html_tag',
      '#tag' => 'iframe',
      '#attributes' => [
        'allowfullscreen' => 'true',
        'frameborder' => 0,
        'scrolling' => 'no',
        'src' => '//www.youtube.com/watch?v=E03HFA923kw',
        'width' => 640,
        'height' => 360,
      ],
    ];

    $markup['#markup'] = '<iframe src="//www.youtube.com/watch?v=E03HFA923kw" class="b-lazy"></iframe>';

    return [
      'With children, has iframe tag' => [
        [$iframe],
      ],
      'Without children, has iframe tag' => [
        $iframe,
      ],
      'With children, has no iframe tag' => [
        [$markup],
      ],
    ];
  }

}
