<?php

namespace Drupal\Tests\az_canvas_migrate\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\az_canvas_migrate\Plugin\migrate\destination\AzCanvasComponent;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests removing empty component inputs.
 */
#[CoversClass(AzCanvasComponent::class)]
#[Group('az_canvas_migrate')]
class AzCanvasComponentTest extends UnitTestCase {

  /**
   * Tests that empty inputs are removed and other values are kept.
   */
  #[DataProvider('providerInputs')]
  public function testRemoveEmptyValues(array $inputs, array $expected): void {
    $destination = (new \ReflectionClass(AzCanvasComponent::class))->newInstanceWithoutConstructor();
    $method = new \ReflectionMethod(AzCanvasComponent::class, 'removeEmptyValues');
    $this->assertSame($expected, $method->invoke($destination, $inputs));
  }

  /**
   * Provides inputs and the inputs expected to remain.
   */
  public static function providerInputs(): array {
    return [
      'null and empty string' => [
        ['heading' => NULL, 'source' => '', 'description' => 'Text'],
        ['description' => 'Text'],
      ],
      'falsy values' => [
        ['clickable' => FALSE, 'image_focal_x' => 0, 'columns_phone' => '0'],
        ['clickable' => FALSE, 'image_focal_x' => 0, 'columns_phone' => '0'],
      ],
      'emptied nested array' => [
        ['image' => ['target_id' => NULL], 'heading' => 'Heading'],
        ['heading' => 'Heading'],
      ],
      'originally empty array' => [
        ['link_url' => ['uri' => 'internal:/', 'options' => []]],
        ['link_url' => ['uri' => 'internal:/', 'options' => []]],
      ],
      'list re-indexed' => [
        ['utility_classes' => ['', 'pb-4', NULL, 'mb-0']],
        ['utility_classes' => ['pb-4', 'mb-0']],
      ],
      'map keys kept' => [
        [2 => '', 5 => 'five'],
        [5 => 'five'],
      ],
    ];
  }

}
