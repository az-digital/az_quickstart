<?php

namespace Drupal\Tests\migmag\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\migmag\Utility\MigMagMigrationUtility;

/**
 * Tests MigMagMigrationUtility.
 *
 * @coversDefaultClass \Drupal\migmag\Utility\MigMagMigrationUtility
 *
 * @group migmag
 */
class MigMagMigrationUtilityTest extends UnitTestCase {

  /**
   * @covers ::getAssociativeMigrationProcess
   *
   * @dataProvider providerGetAssociativeMigrationProcess
   */
  public function testGetAssociativeMigrationProcess($test_process, array $expected_process_pipeline): void {
    $this->assertSame(
      $expected_process_pipeline,
      MigMagMigrationUtility::getAssociativeMigrationProcess($test_process)
    );
  }

  /**
   * Data provider for ::testGetAssociativeMigrationProcess.
   *
   * @return array
   *   The test cases.
   */
  public function providerGetAssociativeMigrationProcess(): array {
    return [
      'String' => [
        'process' => 'foo',
        'expected' => [
          [
            'plugin' => 'get',
            'source' => 'foo',
          ],
        ],
      ],
      'Single config' => [
        'process' => [
          'plugin' => 'foo',
          'source' => 'bar',
        ],
        'expected' => [
          [
            'plugin' => 'foo',
            'source' => 'bar',
          ],
        ],
      ],
      'Indexed array of configs' => [
        'process' => [
          [
            'plugin' => 'foo',
            'source' => 'bar',
            'other_config' => 'baz',
          ],
          ['plugin' => 'foo_bar_baz'],
        ],
        'expected' => [
          [
            'plugin' => 'foo',
            'source' => 'bar',
            'other_config' => 'baz',
          ],
          ['plugin' => 'foo_bar_baz'],
        ],
      ],
      'Keyed array of configs' => [
        'process' => [
          3 => [
            'plugin' => 'foo',
            'source' => 'bar',
            'other_config' => 'baz',
          ],
          'a key here' => ['plugin' => 'foo_bar_baz'],
        ],
        'expected' => [
          3 => [
            'plugin' => 'foo',
            'source' => 'bar',
            'other_config' => 'baz',
          ],
          'a key here' => ['plugin' => 'foo_bar_baz'],
        ],
      ],
    ];
  }

  /**
   * @covers ::updateMigrationLookups
   * @covers ::processMigrationLookupPluginDefinition
   * @covers ::pluginHasSubProcess
   * @covers ::isValidMigrationLookupConfiguration
   * @covers ::pluginIdIsMigrationLookup
   * @covers ::lookupContainsValidMigrationConfig
   *
   * @dataProvider providerUpdateMigrationLookups
   */
  public function testUpdateMigrationLookups(array $test_processes, $migrations_to_update, $migrations_to_remove, array $expected_processes): void {
    $test_array = ['process' => $test_processes];
    MigMagMigrationUtility::updateMigrationLookups($test_array, $migrations_to_update, $migrations_to_remove);
    $this->assertSame(
      $expected_processes,
      $test_array['process']
    );
  }

  /**
   * Data provider for ::testUpdateMigrationLookups.
   *
   * @return array
   *   The test cases.
   */
  public function providerUpdateMigrationLookups(): array {
    return [
      'No lookups' => [
        'processes' => [
          'foo' => 'foo_source',
          'baz' => 'baz_source',
        ],
        'to update' => [],
        'to remove' => [],
        'expected' => [
          'foo' => 'foo_source',
          'baz' => 'baz_source',
        ],
      ],

      'With string lookup' => [
        'processes' => [
          'foo' => 'bar',
          'bar' => [
            'plugin' => 'migration_lookup',
            'source' => '@foo',
            'migration' => 'lookup_test',
          ],
          'baz' => 'foo',
        ],
        'to update' => [
          'lookup_test' => 'lookup_test:derived',
        ],
        'to remove' => [],
        'expected' => [
          'foo' => 'bar',
          'bar' => [
            'plugin' => 'migration_lookup',
            'source' => '@foo',
            'migration' => 'lookup_test:derived',
          ],
          'baz' => 'foo',
        ],
      ],

      'With lookups' => [
        'processes' => [
          'foo' => 'foo_source',
          'bar' => [
            'plugin' => 'migration_lookup',
            'source' => '@foo',
            'migration' => [
              'should_be:removed',
              'lookup_test',
            ],
          ],
          'baz' => 'baz_source',
        ],
        'to update' => [
          'lookup_test' => [
            'lookup_test:derived1',
            'lookup_test:derived2',
          ],
        ],
        'to remove' => [
          'should_be:removed',
        ],
        'expected' => [
          'foo' => 'foo_source',
          'bar' => [
            'plugin' => 'migration_lookup',
            'source' => '@foo',
            'migration' => [
              'lookup_test:derived1',
              'lookup_test:derived2',
            ],
          ],
          'baz' => 'baz_source',
        ],
      ],

      'Lookup order should be kept' => [
        'processes' => [
          'foo' => 'foo_source',
          'bar' => [
            'plugin' => 'migration_lookup',
            'source' => '@foo',
            'migration' => [
              'do_not_touch',
              'lookup_test',
              'do_not_touch_1',
              'lookup_test_2',
            ],
          ],
          'baz' => 'baz_source',
        ],
        'to update' => [
          'lookup_test' => [
            'lookup_test:derived1',
            'lookup_test:derived2',
          ],
          'lookup_test_2' => 'lookup_test_2:derived1',
        ],
        'to remove' => [],
        'expected' => [
          'foo' => 'foo_source',
          'bar' => [
            'plugin' => 'migration_lookup',
            'source' => '@foo',
            'migration' => [
              'do_not_touch',
              'lookup_test:derived1',
              'lookup_test:derived2',
              'do_not_touch_1',
              'lookup_test_2:derived1',
            ],
          ],
          'baz' => 'baz_source',
        ],
      ],

      'Lookup inside sub_process' => [
        'processes' => [
          'foo' => [
            'plugin' => 'sub_process',
            'source' => 'foo',
            'process' => [
              'target_id' => [
                'plugin' => 'migration_lookup',
                'source' => 'value',
                'migration' => [
                  'do_not_touch',
                  'lookup_test',
                  'do_not_touch_1',
                ],
              ],
            ],
          ],
        ],
        'to update' => [
          'lookup_test' => [
            'lookup_test:derived1',
            'lookup_test:derived2',
          ],
        ],
        'to remove' => [],
        'expected' => [
          'foo' => [
            'plugin' => 'sub_process',
            'source' => 'foo',
            'process' => [
              'target_id' => [
                'plugin' => 'migration_lookup',
                'source' => 'value',
                'migration' => [
                  'do_not_touch',
                  'lookup_test:derived1',
                  'lookup_test:derived2',
                  'do_not_touch_1',
                ],
              ],
            ],
          ],
        ],
      ],

      'Lookup inside migmag_try between other plugins' => [
        'processes' => [
          'foo' => [
            'plugin' => 'migmag_try',
            'process' => [
              [
                'plugin' => 'skip_on_empty',
                'method' => 'process',
                'source' => 'foo_source',
              ],
              [
                'plugin' => 'migmag_lookup',
                'migration' => [
                  'do_not_touch',
                  'lookup_test',
                  'should_be_removed',
                  'do_not_touch_1',
                ],
              ],
              [
                'plugin' => 'extract',
                'index' => [0],
              ],
            ],
          ],
        ],
        'to update' => [
          'lookup_test' => [
            'lookup_test:derived1',
            'lookup_test:derived2',
          ],
        ],
        'to remove' => ['should_be_removed'],
        'expected' => [
          'foo' => [
            'plugin' => 'migmag_try',
            'process' => [
              [
                'plugin' => 'skip_on_empty',
                'method' => 'process',
                'source' => 'foo_source',
              ],
              [
                'plugin' => 'migmag_lookup',
                'migration' => [
                  'do_not_touch',
                  'lookup_test:derived1',
                  'lookup_test:derived2',
                  'do_not_touch_1',
                ],
              ],
              [
                'plugin' => 'extract',
                'index' => [0],
              ],
            ],
          ],
        ],
      ],
    ];
  }

  /**
   * @covers ::removeMissingMigrationDependencies
   *
   * @dataProvider providerMissingMigrationDependencies
   */
  public function testRemoveMissingMigrationDependencies(array $original_dependencies, array $available_migrations, $base_id, array $expected_dependencies) {
    $test_definition = [
      'migration_dependencies' => [
        'required' => $original_dependencies,
      ],
    ];

    MigMagMigrationUtility::removeMissingMigrationDependencies(
      $test_definition,
      $available_migrations,
      $base_id
    );

    $this->assertEquals(
      ['required' => $expected_dependencies],
      $test_definition['migration_dependencies']
    );
  }

  /**
   * Data provider for ::testRemoveMissingMigrationDependencies.
   *
   * @return array
   *   The test cases.
   */
  public function providerMissingMigrationDependencies(): array {
    return [
      'No missing dependencies' => [
        'Original required dependencies' => ['foo', 'bar'],
        'Available migrations' => ['foo', 'bar'],
        'Base ID' => NULL,
        'expected' => ['foo', 'bar'],
      ],

      'Some missing dependencies' => [
        'Original required dependencies' => [
          'foo',
          'bar',
          'bar_missing',
          'baz',
          'baz_missing',
        ],
        'Available migrations' => ['foo', 'bar', 'baz'],
        'Base ID' => NULL,
        'expected' => [
          0 => 'foo',
          1 => 'bar',
          3 => 'baz',
        ],
      ],

      'Every dependency is missing' => [
        'Original required dependencies' => [
          'foo_missing',
          'bar_missing',
          'baz_missing',
        ],
        'Available migrations' => ['foo', 'bar', 'baz'],
        'Base ID' => NULL,
        'expected' => [],
      ],

      'Remove missing deps with ID "foo_missing"' => [
        'Original required dependencies' => [
          'foo_missing',
          'bar_missing',
          'baz_missing',
        ],
        'Available migrations' => ['foo', 'bar', 'baz'],
        'Base ID' => 'foo_missing',
        'expected' => [
          1 => 'bar_missing',
          2 => 'baz_missing',
        ],
      ],

      'Remove missing deps with ID "foo_missing:sub:*"' => [
        'Original required dependencies' => [
          'foo_missing:sub:one',
          'foo_missing:sub:two',
          'foo_missing:kept:one',
          'foo_missing:kept:two',
          'bar_missing',
          'baz_missing',
        ],
        'Available migrations' => ['foo', 'bar', 'baz'],
        'Base ID' => 'foo_missing:sub',
        'expected' => [
          2 => 'foo_missing:kept:one',
          3 => 'foo_missing:kept:two',
          4 => 'bar_missing',
          5 => 'baz_missing',
        ],
      ],
    ];
  }

}
