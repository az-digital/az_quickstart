<?php

namespace Drupal\Tests\migmag\Kernel;

use PHPUnit\Framework\ExpectationFailedException;

// cspell:ignore highwater

/**
 * Tests the MigMagNativeMigrateSqlTestBase base test class.
 *
 * @covers \Drupal\migrate_drupal\Plugin\migrate\source\VariableMultiRow
 *
 * @group migmag
 */
class MigMagNativeMigrateSqlTestBaseTest extends MigMagNativeMigrateSqlTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'migrate_drupal',
  ];

  /**
   * {@inheritdoc}
   *
   * @dataProvider providerSource
   */
  public function testSource(array $source_data, array $expected_data, $expected_count = NULL, array $configuration = [], $high_water = NULL, $expected_cache_key = NULL, $expected_failure_message = NULL): void {
    if ($expected_failure_message) {
      $this->expectException(ExpectationFailedException::class);
      $this->expectExceptionMessage($expected_failure_message);
    }
    parent::testSource($source_data, $expected_data, $expected_count, $configuration, $high_water, $expected_cache_key);
  }

  /**
   * {@inheritdoc}
   */
  public static function providerSource(): array {
    $source_sql_data = [
      'variable' => [
        ['name' => 'foo', 'value' => 'i:1;'],
        ['name' => 'bar', 'value' => 'b:0;'],
      ],
    ];

    return [
      'Everything works as expected' => [
        'source_data' => $source_sql_data,
        'expected_data' => [
          [
            'name' => 'foo',
            'value' => 1,
            'variables' => ['foo', 'bar'],
            'cache_counts' => TRUE,
          ],
          [
            'name' => 'bar',
            'value' => FALSE,
            'variables' => ['foo', 'bar'],
            'cache_counts' => TRUE,
          ],
        ],
        'expected_count' => 2,
        'configuration' => [
          'variables' => ['foo', 'bar'],
          'cache_counts' => TRUE,
        ],
        'high_water' => NULL,
        // cspell:disable-next-line
        'expected_cache_key' => 'variable_multirow-3ea288eb4deacf1ac8c36f0c5aa182b93892bdb7cd5c97e32460ee94ff885943',
      ],

      'Count mismatch - less rows' => [
        'source_data' => $source_sql_data,
        'expected_data' => [
          ['name' => 'foo', 'value' => 1, 'variables' => ['foo', 'bar']],
        ],
        'expected_count' => 2,
        'configuration' => [
          'variables' => [
            'foo',
            'bar',
          ],
        ],
        'high_water' => NULL,
        'expected_cache_key' => NULL,
        'expected_failure_message' => 'Failed asserting that two arrays are equal.',
      ],

      'Count mismatch - wrong count' => [
        'source_data' => $source_sql_data,
        'expected_data' => [
          ['name' => 'foo', 'value' => 1, 'variables' => ['foo', 'bar']],
        ],
        'expected_count' => 2,
        'configuration' => [
          'variables' => [
            'foo',
          ],
        ],
        'high_water' => NULL,
        'expected cache key' => NULL,
        'expected_failure_message' => 'Failed asserting that actual size 1 matches expected size 2.',
      ],

      'Cache key mismatch' => [
        'source_data' => $source_sql_data,
        'expected_data' => [
          ['name' => 'foo', 'value' => 1, 'variables' => ['foo']],
        ],
        'expected_count' => 1,
        'configuration' => [
          'variables' => ['foo'],
          'cache_counts' => TRUE,
          'cache_key' => 'actual_cache_key',
        ],
        'high_water' => NULL,
        'expected_cache_key' => 'something_else',
        'expected_failure_message' => 'Failed asserting that two strings are identical.',
      ],
    ];
  }

}
