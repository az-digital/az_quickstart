<?php

namespace Drupal\Tests\az_core\Unit;

use Drupal\az_core\Utility\AZBootstrapMarkupConverter;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the AZ Bootstrap markup converter.
 *
 * @group az_core
 */
class AZBootstrapMarkupConverterTest extends UnitTestCase {

  /**
   * Test fragments are converted correctly without adding document tags.
   *
   * @dataProvider provideFragments
   */
  public function testFragmentConversion($input, $expected) {
    $result = AZBootstrapMarkupConverter::convert($input);
    $this->assertEquals($expected, $result);
  }

  /**
   * Test that compareProcessor returns original text when no changes needed.
   */
  public function testCompareProcessorNoChanges() {
    $input = '<p>Simple text without Bootstrap classes</p>';
    $result = AZBootstrapMarkupConverter::compareProcessor($input);
    $this->assertEquals($input, $result, 'Text without Bootstrap classes should remain unchanged');
  }

  /**
   * Test that compareProcessor returns converted text when changes are needed.
   *
   * @dataProvider provideFragments
   */
  public function testCompareProcessor($input, $expected) {
    $result = AZBootstrapMarkupConverter::compareProcessor($input);
    $this->assertEquals($expected, $result, 'Text with Bootstrap classes should be converted');
  }

  /**
   * Data provider for testFragmentConversion.
   */
  public static function provideFragments() {
    return [
      'single paragraph' => [
        '<p class="text-left mr-3">Hello</p>',
        '<p class="text-start me-3">Hello</p>',
      ],
      'multiple siblings' => [
        '<p class="text-left">First</p><p class="mr-3">Second</p>',
        '<p class="text-start">First</p><p class="me-3">Second</p>',
      ],
      'nested elements' => [
        '<div class="mr-3"><p class="text-left">Nested</p></div>',
        '<div class="me-3"><p class="text-start">Nested</p></div>',
      ],
      'with data attributes' => [
        '<button data-toggle="modal" data-target="#myModal">Click</button>',
        '<button data-bs-toggle="modal" data-bs-target="#myModal">Click</button>',
      ],
      'mixed text and elements' => [
        'Some text <span class="badge-success">Badge</span> more text',
        'Some text <span class="text-bg-success">Badge</span> more text',
      ],
      'multiple classes' => [
        '<div class="ml-3 text-left badge-success">Multiple</div>',
        '<div class="ms-3 text-start text-bg-success">Multiple</div>',
      ],
      'complex nested structure' => [
        '<div class="mr-3">
          <div class="text-left">
            <p class="badge-success">One</p>
            <p class="ml-2">Two</p>
          </div>
          <button data-toggle="tooltip">Info</button>
        </div>',
        '<div class="me-3">
          <div class="text-start">
            <p class="text-bg-success">One</p>
            <p class="ms-2">Two</p>
          </div>
          <button data-bs-toggle="tooltip">Info</button>
        </div>',
      ],
    ];
  }

}
