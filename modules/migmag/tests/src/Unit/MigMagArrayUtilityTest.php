<?php

namespace Drupal\Tests\migmag\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\migmag\Utility\MigMagArrayUtility;

/**
 * Tests MigMagArrayUtility.
 *
 * @coversDefaultClass \Drupal\migmag\Utility\MigMagArrayUtility
 *
 * @group migmag
 */
class MigMagArrayUtilityTest extends UnitTestCase {

  /**
   * A dummy migration process pipeline array used for testing.
   *
   * @const array[]
   */
  const TEST_MIGRATION_PROCESS = [
    'first' => 'foo',
    'second' => 'bar',
    'third' => 'baz',
  ];

  /**
   * @covers \Drupal\migmag\Utility\MigMagArrayUtility::insertInFrontOfKey
   *
   * @dataProvider providerInsertInFront
   */
  public function testInsertInFrontOfKey(array $test_migration_processes, string $next_destination, $new_process_pipeline, bool $overwrite, array $expected_processes, ?string $expected_exception = NULL) {
    if ($expected_exception) {
      $this->expectException(\LogicException::class);
      $this->expectExceptionMessage($expected_exception);
    }
    MigMagArrayUtility::insertInFrontOfKey(
      $test_migration_processes,
      $next_destination,
      'new',
      $new_process_pipeline,
      $overwrite
    );

    $this->assertSame(
      $expected_processes,
      $test_migration_processes
    );
  }

  /**
   * @covers \Drupal\migmag\Utility\MigMagArrayUtility::insertAfterKey
   *
   * @dataProvider providerInsertAfter
   */
  public function testInsertAfterKey(array $test_migration_processes, string $next_destination, $new_process_pipeline, bool $overwrite, array $expected_processes, ?string $expected_exception = NULL) {
    if ($expected_exception) {
      $this->expectException(\LogicException::class);
      $this->expectExceptionMessage($expected_exception);
    }
    MigMagArrayUtility::insertAfterKey(
      $test_migration_processes,
      $next_destination,
      'new',
      $new_process_pipeline,
      $overwrite
    );

    $this->assertSame(
      $expected_processes,
      $test_migration_processes
    );
  }

  /**
   * @covers \Drupal\migmag\Utility\MigMagArrayUtility::moveInFrontOfKey
   *
   * @dataProvider providerMoveInFrontOf
   */
  public function testMoveInFrontOf(array $test_array, string $reference_key, string $moved_key, array $expected_array, ?string $expected_exception = NULL) {
    if ($expected_exception) {
      $this->expectException(\LogicException::class);
      $this->expectExceptionMessage($expected_exception);
    }
    MigMagArrayUtility::moveInFrontOfKey(
      $test_array,
      $reference_key,
      $moved_key
    );

    $this->assertSame(
      $expected_array,
      $test_array
    );
  }

  /**
   * @covers \Drupal\migmag\Utility\MigMagArrayUtility::moveAfterKey
   *
   * @dataProvider providerMoveAfter
   */
  public function testMoveAfter(array $test_array, string $reference_key, string $moved_key, array $expected_array, ?string $expected_exception = NULL) {
    if ($expected_exception) {
      $this->expectException(\LogicException::class);
      $this->expectExceptionMessage($expected_exception);
    }
    MigMagArrayUtility::moveAfterKey(
      $test_array,
      $reference_key,
      $moved_key
    );

    $this->assertSame(
      $expected_array,
      $test_array
    );
  }

  /**
   * Data provider for ::testInsertInFrontOfKey.
   *
   * @return array
   *   The test cases.
   */
  public static function providerInsertInFront(): array {
    return [
      'Insert in front of first' => [
        'test_migration_processes' => self::TEST_MIGRATION_PROCESS,
        'next_destination' => 'first',
        'new_process_pipeline' => 'new',
        'overwrite' => FALSE,
        'expected_processes' => [
          'new' => 'new',
          'first' => 'foo',
          'second' => 'bar',
          'third' => 'baz',
        ],
      ],

      'Insert in front of second' => [
        'test_migration_processes' => self::TEST_MIGRATION_PROCESS,
        'next_destination' => 'second',
        'new_process_pipeline' => 'new',
        'overwrite' => FALSE,
        'expected_processes' => [
          'first' => 'foo',
          'new' => 'new',
          'second' => 'bar',
          'third' => 'baz',
        ],
      ],

      'Insert in front of third' => [
        'test_migration_processes' => self::TEST_MIGRATION_PROCESS,
        'next_destination' => 'third',
        'new_process_pipeline' => 'new',
        'overwrite' => FALSE,
        'expected_processes' => [
          'first' => 'foo',
          'second' => 'bar',
          'new' => 'new',
          'third' => 'baz',
        ],
      ],

      'Preexisting process in the right pos, no overwrite' => [
        'test_migration_processes' => ['new' => 'new'] + self::TEST_MIGRATION_PROCESS,
        'next_destination' => 'third',
        'new_process_pipeline' => [
          'plugin' => 'get',
          'source' => 'foo',
        ],
        'overwrite' => FALSE,
        'expected_processes' => [
          'new' => 'new',
          'first' => 'foo',
          'second' => 'bar',
          'third' => 'baz',
        ],
      ],

      'Preexisting process in the right pos, with overwrite' => [
        'test_migration_processes' => ['new' => 'new'] + self::TEST_MIGRATION_PROCESS,
        'next_destination' => 'third',
        'new_process_pipeline' => [
          'plugin' => 'get',
          'source' => 'foo',
        ],
        'overwrite' => TRUE,
        'expected_processes' => [
          'new' => [
            'plugin' => 'get',
            'source' => 'foo',
          ],
          'first' => 'foo',
          'second' => 'bar',
          'third' => 'baz',
        ],
      ],

      'Missing reference point' => [
        'test_migration_processes' => self::TEST_MIGRATION_PROCESS,
        'next_destination' => 'missing',
        'new_process_pipeline' => 'baz',
        'overwrite' => FALSE,
        'expected_processes' => [],
        'expected_exception' => "The reference key 'missing' cannot be found in the array.",
      ],
    ];
  }

  /**
   * Data provider for ::testInsertAfterKey.
   *
   * @return array
   *   The test cases.
   */
  public static function providerInsertAfter(): array {
    return [
      'Insert after first' => [
        'test_migration_processes' => self::TEST_MIGRATION_PROCESS,
        'Reference' => 'first',
        'new_process_pipeline' => 'new',
        'overwrite' => FALSE,
        'expected_processes' => [
          'first' => 'foo',
          'new' => 'new',
          'second' => 'bar',
          'third' => 'baz',
        ],
      ],

      'Insert after second' => [
        'test_migration_processes' => self::TEST_MIGRATION_PROCESS,
        'Reference' => 'second',
        'new_process_pipeline' => 'new',
        'overwrite' => FALSE,
        'expected_processes' => [
          'first' => 'foo',
          'second' => 'bar',
          'new' => 'new',
          'third' => 'baz',
        ],
      ],

      'Insert after third' => [
        'test_migration_processes' => self::TEST_MIGRATION_PROCESS,
        'Reference' => 'third',
        'new_process_pipeline' => 'new',
        'overwrite' => FALSE,
        'expected_processes' => [
          'first' => 'foo',
          'second' => 'bar',
          'third' => 'baz',
          'new' => 'new',
        ],
      ],

      'Preexisting process in the right pos, no overwrite' => [
        'test_migration_processes' => self::TEST_MIGRATION_PROCESS + ['new' => 'new'],
        'Reference' => 'second',
        'new_process_pipeline' => [
          'plugin' => 'get',
          'source' => 'foo',
        ],
        'overwrite' => FALSE,
        'expected_processes' => [
          'first' => 'foo',
          'second' => 'bar',
          'third' => 'baz',
          'new' => 'new',
        ],
      ],

      'Preexisting process in the right pos, with overwrite' => [
        'test_migration_processes' => self::TEST_MIGRATION_PROCESS + ['new' => 'new'],
        'Reference' => 'third',
        'new_process_pipeline' => [
          'plugin' => 'get',
          'source' => 'foo',
        ],
        'overwrite' => TRUE,
        'expected_processes' => [
          'first' => 'foo',
          'second' => 'bar',
          'third' => 'baz',
          'new' => [
            'plugin' => 'get',
            'source' => 'foo',
          ],
        ],
      ],

      'Missing reference point' => [
        'test_migration_processes' => self::TEST_MIGRATION_PROCESS,
        'Reference' => 'missing',
        'new_process_pipeline' => 'baz',
        'overwrite' => FALSE,
        'expected_processes' => [],
        'expected_exception' => "The reference key 'missing' cannot be found in the array.",
      ],
    ];
  }

  /**
   * Data provider for ::testMoveAfter.
   *
   * @return array
   *   The test cases.
   */
  public function providerMoveAfter(): array {
    return [
      'Move first after second' => [
        'Test array' => self::TEST_MIGRATION_PROCESS,
        'Ref key' => 'second',
        'Moved key' => 'first',
        'expected_processes array' => [
          'second' => 'bar',
          'first' => 'foo',
          'third' => 'baz',
        ],
      ],

      'Move first after third' => [
        'Test array' => self::TEST_MIGRATION_PROCESS,
        'Ref key' => 'third',
        'Moved key' => 'first',
        'expected_processes array' => [
          'second' => 'bar',
          'third' => 'baz',
          'first' => 'foo',
        ],
      ],

      'Third after first' => [
        'Test array' => self::TEST_MIGRATION_PROCESS,
        'Ref key' => 'first',
        'Moved key' => 'third',
        'expected_processes array' => [
          'first' => 'foo',
          'second' => 'bar',
          'third' => 'baz',
        ],
      ],
    ];
  }

  /**
   * Data provider for ::testMoveInFrontOf.
   *
   * @return array
   *   The test cases.
   */
  public function providerMoveInFrontOf(): array {
    return [
      'Move third in front of first' => [
        'Test array' => self::TEST_MIGRATION_PROCESS,
        'Ref key' => 'first',
        'Moved key' => 'third',
        'expected_processes array' => [
          'third' => 'baz',
          'first' => 'foo',
          'second' => 'bar',
        ],
      ],

      'Move third in front of second' => [
        'Test array' => self::TEST_MIGRATION_PROCESS,
        'Ref key' => 'second',
        'Moved key' => 'third',
        'expected_processes array' => [
          'first' => 'foo',
          'third' => 'baz',
          'second' => 'bar',
        ],
      ],

      'First in front of third' => [
        'Test array' => self::TEST_MIGRATION_PROCESS,
        'Ref key' => 'third',
        'Moved key' => 'first',
        'expected_processes array' => [
          'first' => 'foo',
          'second' => 'bar',
          'third' => 'baz',
        ],
      ],
    ];
  }

  /**
   * @covers ::addSuffixToArrayValues
   *
   * @dataProvider providerAddSuffixToArrayValues
   */
  public function testAddSuffixToArrayValues(array $dependencies, array $dependency_ids_to_process, string $derivative_suffix, array $expected_dependencies): void {
    MigMagArrayUtility::addSuffixToArrayValues($dependencies, $dependency_ids_to_process, $derivative_suffix);
    $this->assertSame($expected_dependencies, $dependencies);
  }

  /**
   * Data provider for ::testAddSuffixToMigrationDependencies.
   *
   * @return array
   *   The test cases.
   */
  public function providerAddSuffixToArrayValues(): array {
    return [
      'Single matching array key' => [
        'Original' => [
          'foo',
          'bar',
          'baz',
        ],
        'Deps to update' => ['bar'],
        'Suffix' => ':sub:bar',
        'expected_processes' => [
          'foo',
          'bar:sub:bar',
          'baz',
        ],
      ],

      'No matching array key' => [
        'Original' => [
          'foo',
          'bar',
          'baz',
        ],
        'Deps to update' => ['missing'],
        'Suffix' => ':sub:bar',
        'expected_processes' => [
          'foo',
          'bar',
          'baz',
        ],
      ],

      'Multiple matching array key' => [
        'Original' => [
          'foo',
          'bar',
          'baz',
        ],
        'Deps to update' => ['foo', 'bar', 'missing'],
        'Suffix' => ':sub:bar',
        'expected_processes' => [
          'foo:sub:bar',
          'bar:sub:bar',
          'baz',
        ],
      ],

      'Tricky' => [
        'Original' => [
          'foo',
          'foo_bar',
          'baz',
        ],
        'Deps to update' => ['foo_bar'],
        'Suffix' => '_baz',
        'expected_processes' => [
          'foo',
          'foo_bar_baz',
          'baz',
        ],
      ],
    ];
  }

}
