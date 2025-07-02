<?php

namespace Drupal\Tests\migmag_process\Unit\Plugin;

use Composer\InstalledVersions;
use Drupal\Tests\migrate\Unit\MigrateTestCase;
use Drupal\migmag_process\Plugin\migrate\process\MigMagCompare;
use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;

/**
 * Tests the migmag_compare process plugin.
 *
 * @coversDefaultClass \Drupal\migmag_process\Plugin\migrate\process\MigMagCompare
 *
 * @group migmag_process
 */
class MigMagCompareTest extends MigrateTestCase {

  /**
   * Tests the transformation of the provided values.
   *
   * @param array $plugin_config
   *   The configuration of the tested plugin instance.
   * @param mixed $value
   *   The incoming value to test the transformation with.
   * @param mixed $expected_result
   *   The expected result of the transformation.
   * @param string|null $expected_exception_message
   *   The expected message of the MigrateException if the test case should end
   *   in a MigrateException. If this is NULL, then the test does not expects a
   *   MigrateException to be thrown. Defaults to NULL.
   *
   * @covers ::transform
   * @covers ::doCompare
   * @covers ::deliverReturnValue
   *
   * @dataProvider providerTestTransform
   */
  public function testTransform(array $plugin_config, $value, $expected_result, ?string $expected_exception_message = NULL): void {
    $migrate_executable = $this->prophesize(MigrateExecutableInterface::class);
    $row = $this->prophesize(Row::class);
    $plugin_config += ['plugin' => 'migmag_compare'];
    $plugin = new MigMagCompare(
      $plugin_config,
      $plugin_config['plugin'],
      []
    );

    if ($expected_exception_message) {
      $this->expectException(MigrateException::class);
      $this->expectExceptionMessage($expected_exception_message);
    }
    $actual_result = $plugin->transform(
      $value,
      $migrate_executable->reveal(),
      $row->reveal(),
      'destination_property'
    );

    $this->assertSame($expected_result, $actual_result);
  }

  /**
   * Data provider for ::testTransform.
   *
   * @return array[]
   *   The test cases.
   */
  public static function providerTestTransform(): array {
    $return_if_conf = [
      'true' => 'true',
      'false' => 'false',
      0 => 'equal',
      -1 => '1st less than 2nd',
      1 => '1st greater than 2nd',
    ];
    $lt10PhpUnit = version_compare(InstalledVersions::getVersion('phpunit/phpunit'), '10', 'lt');

    return [
      "No operator ('==='), expected FALSE" => [
        'plugin_config' => [],
        'value' => ['0', 0],
        'expected_result' => FALSE,
      ],
      "No operator ('==='), expected TRUE" => [
        'plugin_config' => [],
        'value' => ['foo', 'foo'],
        'expected_result' => TRUE,
      ],

      "'==' operator, expected FALSE" => [
        'plugin_config' => ['operator' => '=='],
        'value' => ['ab', 1],
        'expected_result' => FALSE,
      ],
      "'==' operator, expected TRUE" => [
        'plugin_config' => ['operator' => '=='],
        'value' => ['0', 0],
        'expected_result' => TRUE,
      ],
      "Why weak comparison ('==') is dangerous (with PHP < 8.0)" => [
        'plugin_config' => ['operator' => '=='],
        'value' => ['ab', 0],
        'expected_result' => PHP_MAJOR_VERSION < 8,
      ],

      "'===' operator, expected FALSE" => [
        'plugin_config' => ['operator' => '==='],
        'value' => ['1', 1],
        'expected_result' => FALSE,
      ],
      "'===' operator, expected TRUE" => [
        'plugin_config' => ['operator' => '==='],
        'value' => [1.234, 1.234],
        'expected_result' => TRUE,
      ],

      "'!=' operator, expected FALSE" => [
        'plugin_config' => ['operator' => '!='],
        'value' => ['0', 0],
        'expected_result' => FALSE,
      ],
      "'!=' operator, expected TRUE" => [
        'plugin_config' => ['operator' => '!='],
        'value' => ['ab', 1],
        'expected_result' => TRUE,
      ],
      "Why weak comparison ('!=') is dangerous (with PHP < 8.0)" => [
        'plugin_config' => ['operator' => '!='],
        'value' => ['ab', 0],
        'expected_result' => PHP_MAJOR_VERSION >= 8,
      ],
      "Mixed values #1, '<>' operator, expected FALSE" => [
        'plugin_config' => ['operator' => '<>'],
        'value' => ['1.2345', 1.2345],
        'expected_result' => FALSE,
      ],
      "Mixed values #2, '<>' operator" => [
        'plugin_config' => ['operator' => '<>'],
        'value' => ['0abc', 0],
        'expected_result' => PHP_MAJOR_VERSION >= 8,
      ],
      "Mixed values #3, '!=' operator, expected FALSE" => [
        'plugin_config' => ['operator' => '<>'],
        'value' => [NULL, ''],
        'expected_result' => FALSE,
      ],

      "'!==' operator, expected FALSE" => [
        'plugin_config' => ['operator' => '!=='],
        'value' => [1.2345, 1.2345],
        'expected_result' => FALSE,
      ],
      "'!==' operator, expected TRUE" => [
        'plugin_config' => ['operator' => '!=='],
        'value' => ['1.2345', 1.2345],
        'expected_result' => TRUE,
      ],

      "'<' operator #1, expected FALSE" => [
        'plugin_config' => ['operator' => '<'],
        'value' => [1.23456, 1.2345],
        'expected_result' => FALSE,
      ],

      "'<' operator #2, expected TRUE" => [
        'plugin_config' => ['operator' => '<'],
        'value' => [1.2345, 1.23456],
        'expected_result' => TRUE,
      ],
      "'<' operator #3, expected TRUE" => [
        'plugin_config' => ['operator' => '<'],
        'value' => ['1.2345', 1.23456],
        'expected_result' => TRUE,
      ],
      "'<' operator #4, expected FALSE" => [
        'plugin_config' => ['operator' => '<'],
        'value' => ['1.23456', 1.2345],
        'expected_result' => FALSE,
      ],

      "'<=' operator #1, expected FALSE" => [
        'plugin_config' => ['operator' => '<='],
        'value' => [1.2345, 1],
        'expected_result' => FALSE,
      ],
      "'<=' operator #2, expected TRUE" => [
        'plugin_config' => ['operator' => '<='],
        'value' => [1, 1.2345],
        'expected_result' => TRUE,
      ],
      "'<=' operator #3, expected TRUE" => [
        'plugin_config' => ['operator' => '<='],
        'value' => ['1.23456', 1.2345678],
        'expected_result' => TRUE,
      ],

      "'>' operator #1, expected FALSE" => [
        'plugin_config' => ['operator' => '>'],
        'value' => [1.23456, 1.2345678],
        'expected_result' => FALSE,
      ],
      "'>' operator #2, expected TRUE" => [
        'plugin_config' => ['operator' => '>'],
        'value' => [1.23456, 1.2345],
        'expected_result' => TRUE,
      ],

      "'>=' operator #1, expected FALSE" => [
        'plugin_config' => ['operator' => '>='],
        'value' => [1, 1.2345],
        'expected_result' => FALSE,
      ],
      "'>=' operator #2, expected TRUE" => [
        'plugin_config' => ['operator' => '>='],
        'value' => [1.2345, 1],
        'expected_result' => TRUE,
      ],
      "'>=' operator #3, expected TRUE" => [
        'plugin_config' => ['operator' => '>='],
        'value' => [1.2345678, '1.23456'],
        'expected_result' => TRUE,
      ],

      "Second object is always bigger" => [
        'plugin_config' => ['operator' => '>'],
        'value' => [(object) ['a' => 1], (object) []],
        'expected_result' => TRUE,
      ],

      "Second array is always bigger" => [
        'plugin_config' => ['operator' => '>'],
        'value' => [[1], []],
        'expected_result' => TRUE,
      ],

      "'<=>' operator #1" => [
        'plugin_config' => ['operator' => '<=>'],
        'value' => [1, 2],
        'expected_result' => -1,
      ],
      "'<=>' operator #2" => [
        'plugin_config' => ['operator' => '<=>'],
        'value' => [2, 2],
        'expected_result' => 0,
      ],
      "'<=>' operator #3" => [
        'plugin_config' => ['operator' => '<=>'],
        'value' => [3, 2],
        'expected_result' => 1,
      ],
      "'<=>' operator #4: object" => [
        'plugin_config' => ['operator' => '<=>'],
        'value' => [(object) [1 => 1], (object) [1 => 0]],
        'expected_result' => 1,
      ],
      "'<=>' operator #5: object" => [
        'plugin_config' => ['operator' => '<=>'],
        'value' => [(object) [1 => 1], (object) [1 => 1]],
        'expected_result' => 0,
      ],
      "'<=>' operator #6: object" => [
        'plugin_config' => ['operator' => '<=>'],
        'value' => [(object) [1 => 1], (object) [1 => 2]],
        'expected_result' => -1,
      ],

      'return_if #1: true' => [
        'plugin_config' => ['return_if' => $return_if_conf],
        'value' => [1, 1],
        'expected_result' => 'true',
      ],
      'return_if #2: false' => [
        'plugin_config' => ['return_if' => $return_if_conf],
        'value' => [0, 1],
        'expected_result' => 'false',
      ],
      'return_if #3: less' => [
        'plugin_config' => [
          'operator' => '<=>',
          'return_if' => $return_if_conf,
        ],
        'value' => [0, 1],
        'expected_result' => '1st less than 2nd',
      ],
      'return_if #4: equal' => [
        'plugin_config' => [
          'operator' => '<=>',
          'return_if' => $return_if_conf,
        ],
        'value' => [1, 1],
        'expected_result' => 'equal',
      ],
      'return_if #5: greater' => [
        'plugin_config' => [
          'operator' => '<=>',
          'return_if' => $return_if_conf,
        ],
        'value' => [2, 1],
        'expected_result' => '1st greater than 2nd',
      ],

      "Exception: comparison fails" => [
        'plugin_config' => ['operator' => '<=>'],
        'value' => [(object) [1 => 1], 1],
        'expected_result' => $lt10PhpUnit ? NULL : 0,
        'expected_exception_message' => $lt10PhpUnit
          ? "Comparison failed in 'migmag_compare' migrate process plugin with message: Object of class stdClass could not be converted to int."
          : NULL,
      ],
      "Exception: object value" => [
        'plugin_config' => [],
        'value' => (object) ['foo' => 'bar'],
        'expected_result' => NULL,
        'expected_exception_message' => "'migmag_compare' migrate process plugin's processed value must be an array, got 'object'.",
      ],
      "Exception: integer value" => [
        'plugin_config' => [],
        'value' => 1,
        'expected_result' => NULL,
        'expected_exception_message' => "'migmag_compare' migrate process plugin's processed value must be an array, got 'integer'.",
      ],
      "Exception: only one array value" => [
        'plugin_config' => [],
        'value' => [1],
        'expected_result' => NULL,
        'expected_exception_message' => "'migmag_compare' migrate process plugin's processed array value must have at least two values.",
      ],
      "Exception: unsupported operator" => [
        'plugin_config' => ['operator' => 'foo'],
        'value' => [1, 2],
        'expected_result' => NULL,
        'expected_exception_message' => "'migmag_compare' migrate process plugin does not support operator 'foo'.",
      ],
      "Exception: non-string operator" => [
        'plugin_config' => ['operator' => (object) ['foo']],
        'value' => [1, 2],
        'expected_result' => NULL,
        'expected_exception_message' => "'migmag_compare' migrate process plugin's operator must be a string, got 'object'.",
      ],
    ];
  }

}
