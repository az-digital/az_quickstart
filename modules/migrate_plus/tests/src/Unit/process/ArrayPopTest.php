<?php

declare(strict_types = 1);

namespace Drupal\Tests\migrate_plus\Unit\process;

use Drupal\migrate\MigrateException;
use Drupal\migrate_plus\Plugin\migrate\process\ArrayPop;
use Drupal\Tests\migrate\Unit\process\MigrateProcessTestCase;

/**
 * Tests the array pop process plugin.
 *
 * @group migrate
 * @coversDefaultClass \Drupal\migrate_plus\Plugin\migrate\process\ArrayPop
 */
final class ArrayPopTest extends MigrateProcessTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    $this->plugin = new ArrayPop([], 'array_pop', []);
    parent::setUp();
  }

  /**
   * Data provider for testArrayPop().
   *
   * @return array
   *   An array containing input values and expected output values.
   */
  public static function arrayPopDataProvider(): array {
    return [
      'indexed array' => [
        'input' => ['v1', 'v2', 'v3'],
        'expected_output' => 'v3',
      ],
      'associative array' => [
        'input' => ['i1' => 'v1', 'i2' => 'v2', 'i3' => 'v3'],
        'expected_output' => 'v3',
      ],
      'empty array' => [
        'input' => [],
        'expected_output' => NULL,
      ],
    ];
  }

  /**
   * Test array pop plugin.
   *
   * @param array $input
   *   The input values.
   * @param mixed $expected_output
   *   The expected output.
   *
   * @dataProvider arrayPopDataProvider
   */
  public function testArrayPop(array $input, mixed $expected_output): void {
    $output = $this->plugin->transform($input, $this->migrateExecutable, $this->row, 'destinationproperty');
    $this->assertSame($output, $expected_output);
  }

  /**
   * Test invalid input.
   */
  public function testArrayPopFromString(): void {
    $this->expectException(MigrateException::class);
    $this->expectExceptionMessage('Input should be an array.');
    $this->plugin->transform('foo', $this->migrateExecutable, $this->row, 'destinationproperty');
  }

}
