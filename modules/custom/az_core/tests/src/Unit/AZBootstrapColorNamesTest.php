<?php

namespace Drupal\Tests\az_core\Unit;

use Drupal\az_core\Utility\AZBootstrapColorNames;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests renaming of deprecated Arizona Bootstrap brand color classes.
 */
#[Group('az_core')]
class AZBootstrapColorNamesTest extends UnitTestCase {

  /**
   * Tests that deprecated classes are replaced and others are left alone.
   */
  #[DataProvider('provideClasses')]
  public function testUpdateClasses($input, $expected) {
    $this->assertSame($expected, AZBootstrapColorNames::updateClasses($input));
  }

  /**
   * Provides class lists and their expected updated values.
   */
  public static function provideClasses() {
    return [
      'text background' => ['text-bg-midnight', 'text-bg-tinta'],
      'background' => ['bg-cool-gray', 'bg-cloud'],
      'text color' => ['text-oasis', 'text-arroyo-blue'],
      'button in a class list' => ['btn w-100 btn-sky', 'btn w-100 btn-rain'],
      'outline button in a class list' => ['w-100 btn btn-outline-sky mt-2', 'w-100 btn btn-outline-rain mt-2'],
      'multiple deprecated classes' => ['text-bg-chili border-bloom', 'text-bg-sonoran-red border-bougainvillea'],
      'monsoon pattern' => ['bg-monsoon-sky-start', 'bg-monsoon-rain-start'],
      'ranking font color' => ['ranking-text-midnight', 'ranking-text-tinta'],
      'new class' => ['text-bg-tinta', 'text-bg-tinta'],
      'unrelated class' => ['text-bg-blue', 'text-bg-blue'],
      'color name without a known prefix' => ['sky', 'sky'],
      'unknown prefix' => ['bg-trans-sky', 'bg-trans-sky'],
      'longer class containing an old name' => ['text-bg-sky-100', 'text-bg-sky-100'],
      'whitespace is kept' => [' btn  btn-mesa ', ' btn  btn-brick '],
      'empty string' => ['', ''],
    ];
  }

  /**
   * Tests that every deprecated color has a class for every prefix.
   */
  public function testGetClassMap() {
    $map = AZBootstrapColorNames::getClassMap();
    $expected_count = count(AZBootstrapColorNames::DEPRECATED_COLOR_NAMES) * count(AZBootstrapColorNames::DEPRECATED_COLOR_CLASS_PREFIXES) + count(AZBootstrapColorNames::DEPRECATED_CLASSES);
    $this->assertCount($expected_count, $map);
    $this->assertSame('text-bg-transparent-caliche', $map['text-bg-transparent-warm-gray']);
    $this->assertSame('focus-ring-saguaro', $map['focus-ring-leaf']);
  }

}
