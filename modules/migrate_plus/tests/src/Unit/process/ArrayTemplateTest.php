<?php

declare(strict_types=1);

namespace Drupal\Tests\migrate_plus\Unit\process;

use Drupal\migrate\MigrateException;
use Drupal\migrate\Row;
use Drupal\migrate_plus\Plugin\migrate\process\ArrayTemplate;
use Drupal\Tests\migrate\Unit\process\MigrateProcessTestCase;

/**
 * Tests the array_template process plugin.
 *
 * @group migrate
 * @coversDefaultClass \Drupal\migrate_plus\Plugin\migrate\process\ArrayTemplate
 */
final class ArrayTemplateTest extends MigrateProcessTestCase {

  /**
   * Test array_template plugin.
   *
   * @param mixed $input
   *   The input values.
   * @param mixed $expected
   *   The expected output.
   * @param array $configuration
   *   The configuration.
   *
   * @dataProvider providerTestArrayTemplate
   */
  public function testArrayTemplate($input, $expected, array $configuration): void {
    $plugin = new ArrayTemplate($configuration, 'array_template', []);
    $output = $plugin->transform($input, $this->migrateExecutable, $this->row, 'destinationproperty');
    $this->assertSame($expected, $output);
  }

  /**
   * Data provider for testArrayTemplate().
   *
   * @return array
   *   An array containing input values and expected output values.
   */
  public static function providerTestArrayTemplate(): array {
    return [
      'string wrap' => [
        'input' => 'my_string',
        'expected' => ['my_string'],
        'configuration' => [
          'template' => ['pipeline:'],
        ],
      ],
      'array wrap' => [
        'input' => ['v1', 'v2', 'v3'],
        'expected' => [['v1', 'v2', 'v3']],
        'configuration' => [
          'template' => ['pipeline:'],
        ],
      ],
      'string wrap with key' => [
        'input' => 'my_string',
        'expected' => ['string' => 'my_string'],
        'configuration' => [
          'template' => ['string' => 'pipeline:'],
        ],
      ],
      'unrecognized prefix' => [
        'input' => 'my_string',
        'expected' => ['prefix:', 'another:prefix'],
        'configuration' => [
          'template' => ['prefix:', 'another:prefix'],
        ],
      ],
    ];
  }

  /**
   * Test source and destination properties with the array_template plugin.
   *
   * Also test that complex template structure is preserved and that
   * sub-properties can be accessed.
   */
  public function testArrayTemplateSourceAndDestination(): void {
    // Use a real Row object, not a mock.
    $row = new Row();
    $pipeline = [
      'zero',
      'one',
      'two' => ['nested value'],
    ];
    $row->setSourceProperty('some_field', [
      'source value',
      ['value' => 'nested source value'],
    ]);
    $row->setDestinationProperty('some_field', [
      'destination value',
      ['value' => 'nested destination value'],
    ]);
    $configuration = [
      'template' => [
        'literals' => [
          'pipeline' => 'pipeline',
          'source' => 'source',
          'dest' => 'dest',
        ],
        'full values' => [
          'pipeline' => 'pipeline:',
          'source' => 'source:some_field',
          'dest' => 'dest:some_field',
        ],
        'scalar values' => [
          'zero' => 'pipeline:0',
          'one' => 'pipeline:1',
          'nested' => 'pipeline:two/0',
          'source' => 'source:some_field/0',
          'nested_source' => 'source:some_field/1/value',
          'destination' => 'dest:some_field/0',
          'nested_destination' => 'dest:some_field/1/value',
        ],
      ],
    ];
    $expected = [
      'literals' => [
        'pipeline' => 'pipeline',
        'source' => 'source',
        'dest' => 'dest',
      ],
      'full values' => [
        'pipeline' => [
          'zero',
          'one',
          'two' => ['nested value'],
        ],
        'source' => [
          'source value',
          ['value' => 'nested source value'],
        ],
        'dest' => [
          'destination value',
          ['value' => 'nested destination value'],
        ],
      ],
      'scalar values' => [
        'zero' => 'zero',
        'one' => 'one',
        'nested' => 'nested value',
        'source' => 'source value',
        'nested_source' => 'nested source value',
        'destination' => 'destination value',
        'nested_destination' => 'nested destination value',
      ],
    ];
    $plugin = new ArrayTemplate($configuration, 'array_template', []);
    $output = $plugin->transform($pipeline, $this->migrateExecutable, $row, 'destinationproperty');
    $this->assertSame($expected, $output);
  }

  /**
   * Test invalid configuration.
   *
   * @param array $configuration
   *   The configuration.
   * @param string $message
   *   The exception message we expect.
   *
   * @dataProvider providerArrayTemplateConstructorExceptions
   */
  public function testArrayTemplateConstructorExceptions(array $configuration, string $message): void {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage($message);
    $plugin = new ArrayTemplate($configuration, 'array_template', []);
    $plugin->transform(123, $this->migrateExecutable, $this->row, 'destinationproperty');
  }

  /**
   * Data provider for ::testArrayTemplateConstructorExceptions().
   */
  public static function providerArrayTemplateConstructorExceptions(): array {
    return [
      [
        'configuration' => [
          'template' => 'not an array',
        ],
        'message' => 'The "template" must be set to an array.',
      ],
    ];
  }

}
