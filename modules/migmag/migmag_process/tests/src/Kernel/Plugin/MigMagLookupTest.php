<?php

namespace Drupal\Tests\migmag_process\Kernel\Plugin;

use Drupal\Component\Utility\Variable;
use Drupal\Core\Database\Database;
use Drupal\Core\Extension\ModuleInstallerInterface;
use Drupal\Tests\migrate\Kernel\MigrateTestBase;
use Drupal\migmag_process\Plugin\migrate\process\MigMagLookup;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Plugin\MigrationPluginManagerInterface;
use Drupal\migrate\Plugin\migrate\id_map\Sql;
use Drupal\migrate\Row;

// cspell:ignore destid sourceid

/**
 * Tests the MigMagLookup migrate process plugin with real migrations.
 *
 * @coversDefaultClass \Drupal\migmag_process\Plugin\migrate\process\MigMagLookup
 *
 * @group migmag_process
 */
class MigMagLookupTest extends MigrateTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'migmag',
    'migmag_lookup_test_migrations',
    'migmag_process',
    'migrate_events_test',
    'tecla',
  ];

  /**
   * Tests the MigMagLookup migrate process plugin with non-sql migrations.
   *
   * @param int|string|array $value
   *   The value to pass to the lookup plugin instance.
   * @param string|array $expected_transformed_value
   *   The expected transformed value.
   * @param array $plugin_configuration
   *   The configuration the plugin should be tested with.
   * @param array $previous_migrate_map_entries
   *   An array of migration row source IDs and destination IDs to be saved into
   *   the ID map before the plugin gets tested.
   * @param array $expected_migrate_map_entries
   *   An array of the expected migration map entries after the plugin's
   *   transform method was called.
   * @param array|null $row_data
   *   The actual migration row's data.
   *
   * @dataProvider providerTestPluginWithNonSqlSources
   */
  public function testPluginWithNonSqlSources($value, $expected_transformed_value, array $plugin_configuration, array $previous_migrate_map_entries, array $expected_migrate_map_entries, $row_data = NULL) {
    $migration = $this->prophesize(MigrationInterface::class);
    $executable = $this->prophesize(MigrateExecutable::class);
    $row_data = $row_data ?? ['dummy_source_property' => 'dummy_source_data'];
    $row = new Row(
      $row_data,
      array_combine(array_keys($row_data), array_keys($row_data))
    );

    // Pre-fill migration map if needed.
    $this->populateMigrationMaps($previous_migrate_map_entries);

    $plugin = MigMagLookup::create(
      $this->container,
      $plugin_configuration,
      'migmag_lookup',
      [],
      $migration->reveal()
    );
    $actual_transformed_value = $plugin->transform($value, $executable->reveal(), $row, 'destination_property');
    $this->assertEquals(
      $expected_transformed_value,
      $actual_transformed_value
    );

    $this->assertMigrationMaps($expected_migrate_map_entries);
  }

  /**
   * Tests the MigMagLookup migrate process plugin with sql sources.
   *
   * @param int|string|array $value
   *   The value to pass to the lookup plugin instance.
   * @param string|array $expected_transformed_value
   *   The expected transformed value.
   * @param array $plugin_configuration
   *   The configuration the plugin should be tested with.
   * @param array $previous_migrate_map_entries
   *   An array of migration row source IDs and destination IDs to be saved into
   *   the ID map before the plugin gets tested.
   * @param array $expected_migrate_map_entries
   *   An array of the expected migration map entries after the plugin's
   *   transform method was called.
   * @param array|null $row_data
   *   The actual migration row's data.
   *
   * @dataProvider providerTestPluginWithSqlSources
   */
  public function testPluginWithSqlSources($value, $expected_transformed_value, array $plugin_configuration, array $previous_migrate_map_entries, array $expected_migrate_map_entries, $row_data = NULL) {
    $module_installer = $this->container->get('module_installer');
    assert($module_installer instanceof ModuleInstallerInterface);
    $module_installer->install([
      'migrate_drupal',
      'node',
      'language',
      'content_translation',
    ]);
    $this->loadFixture(
      implode(DIRECTORY_SEPARATOR, [
        DRUPAL_ROOT,
        'core',
        'modules',
        'migrate_drupal',
        'tests',
        'fixtures',
        'drupal7.php',
      ])
    );

    $this->startCollectingMessages();
    $this->executeMigrations([
      'language',
      'default_language',
    ]);
    $this->assertEmpty($this->migrateMessages);

    $this->testPluginWithNonSqlSources($value, $expected_transformed_value, $plugin_configuration, $previous_migrate_map_entries, $expected_migrate_map_entries, $row_data);
  }

  /**
   * Tests the MigMagLookup migrate process plugin with 'source_ids' config.
   *
   * @param int|string|array $value
   *   The value to pass to the lookup plugin instance.
   * @param array $row_data
   *   The actual migration row's data.
   * @param array $plugin_configuration
   *   The configuration the plugin should be tested with.
   * @param array $previous_migrate_map_entries
   *   An array of migration row source IDs and destination IDs to be saved into
   *   the ID map before the plugin gets tested.
   * @param string|array $expected_transformed_value
   *   The expected transformed value.
   * @param array $expected_migrate_map_entries
   *   An array of the expected migration map entries after the plugin's
   *   transform method was called.
   *
   * @dataProvider providerTestPluginWithSourceIds
   */
  public function testPluginWithSourceIds($value, array $row_data, array $plugin_configuration, array $previous_migrate_map_entries, $expected_transformed_value, array $expected_migrate_map_entries) {
    $this->testPluginWithNonSqlSources($value, $expected_transformed_value, $plugin_configuration, $previous_migrate_map_entries, $expected_migrate_map_entries, $row_data);
  }

  /**
   * Data provider for ::testPluginWithNonSqlSources.
   *
   * @return array[]
   *   The test cases.
   */
  public static function providerTestPluginWithNonSqlSources() {
    return [
      'Lookup for already migrated source "2" in the right migration, no stubbing' => [
        'value' => 2,
        'expected_transformed_value' => '10',
        'plugin_configuration' => [
          'migration' => [
            'migmag_lookup_test_with_source_4',
            'migmag_lookup_test_with_source_2',
          ],
          'no_stub' => TRUE,
        ],
        'previous_migrate_map_entries' => [
          'migmag_lookup_test_with_source_2' => [
            [
              'source_ids' => ['id' => 2],
              'destination_ids' => '10',
            ],
          ],
        ],
        'expected_migrate_map_entries' => [
          'migmag_lookup_test_with_source_2' => [
            [
              'sourceid1' => '2',
              'destid1' => '10',
              'source_row_status' => '0',
              'rollback_action' => '0',
            ],
          ],
          'migmag_lookup_test_with_source_4' => [],
        ],
      ],

      'Lookup for already migrated source "4" in the right migration, no stubbing' => [
        'value' => 4,
        'expected_transformed_value' => '100',
        'plugin_configuration' => [
          'migration' => [
            'migmag_lookup_test_with_source_4',
            'migmag_lookup_test_with_source_2',
          ],
          'no_stub' => TRUE,
        ],
        'previous_migrate_map_entries' => [
          'migmag_lookup_test_with_source_4' => [
            [
              'source_ids' => ['id' => 4],
              'destination_ids' => '100',
            ],
          ],
        ],
        'expected_migrate_map_entries' => [
          'migmag_lookup_test_with_source_2' => NULL,
          'migmag_lookup_test_with_source_4' => [
            [
              'sourceid1' => '4',
              'destid1' => '100',
              'source_row_status' => '0',
              'rollback_action' => '0',
            ],
          ],
        ],
      ],

      'Stubbing "2" in the right migration, no lookup' => [
        'value' => 2,
        'expected_transformed_value' => '10',
        'plugin_configuration' => [
          'migration' => [
            'migmag_lookup_test_with_source_4',
            'migmag_lookup_test_with_source_2',
          ],
          'no_stub' => FALSE,
        ],
        'previous_migrate_map_entries' => [],
        'expected_migrate_map_entries' => [
          'migmag_lookup_test_with_source_2' => [
            [
              'sourceid1' => '2',
              'destid1' => '10',
              'source_row_status' => '1',
              'rollback_action' => '0',
            ],
          ],
          'migmag_lookup_test_with_source_4' => [],
        ],
      ],

      'Stubbing "4" in the right migration, no lookup' => [
        'value' => 4,
        'expected_transformed_value' => '100',
        'plugin_configuration' => [
          'migration' => [
            'migmag_lookup_test_with_source_4',
            'migmag_lookup_test_with_source_2',
          ],
          'no_stub' => FALSE,
        ],
        'previous_migrate_map_entries' => [],
        'expected_migrate_map_entries' => [
          'migmag_lookup_test_with_source_2' => [],
          'migmag_lookup_test_with_source_4' => [
            [
              'sourceid1' => '4',
              'destid1' => '100',
              'source_row_status' => '1',
              'rollback_action' => '0',
            ],
          ],
        ],
      ],

      'Stubbing "4" in the right migration, with default value for the stub ID' => [
        'value' => 4,
        'expected_transformed_value' => 'dummy_source_data',
        'plugin_configuration' => [
          'migration' => [
            'migmag_lookup_test_with_source_4',
            'migmag_lookup_test_with_source_2',
          ],
          'no_stub' => FALSE,
          'stub_default_values' => [
            'value' => 'dummy_source_property',
          ],
        ],
        'previous_migrate_map_entries' => [],
        'expected_migrate_map_entries' => [
          'migmag_lookup_test_with_source_2' => [],
          'migmag_lookup_test_with_source_4' => [
            [
              'sourceid1' => '4',
              'destid1' => 'dummy_source_data',
              'source_row_status' => '1',
              'rollback_action' => '0',
            ],
          ],
        ],
      ],

      'Looking for not-yet migrated source "2", no stubbing' => [
        'value' => 2,
        'expected_transformed_value' => NULL,
        'plugin_configuration' => [
          'migration' => [
            'migmag_lookup_test_with_source_4',
            'migmag_lookup_test_with_source_2',
          ],
          'no_stub' => TRUE,
        ],
        'previous_migrate_map_entries' => [],
        'expected_migrate_map_entries' => [
          'migmag_lookup_test_with_source_2' => [],
          'migmag_lookup_test_with_source_4' => [],
        ],
      ],

      'Looking for missing source, without stubbing' => [
        'value' => 500,
        'expected_transformed_value' => NULL,
        'plugin_configuration' => [
          'migration' => [
            'migmag_lookup_test_with_source_4',
            'migmag_lookup_test_with_source_2',
          ],
          'no_stub' => TRUE,
        ],
        'previous_migrate_map_entries' => [],
        'expected_migrate_map_entries' => [
          'migmag_lookup_test_with_source_2' => [],
          'migmag_lookup_test_with_source_4' => [],
        ],
      ],

      'Looking for missing source with stubbing' => [
        'value' => 501,
        'expected_transformed_value' => NULL,
        'plugin_configuration' => [
          'migration' => [
            'migmag_lookup_test_with_source_4',
            'migmag_lookup_test_with_source_2',
          ],
          'no_stub' => FALSE,
        ],
        'previous_migrate_map_entries' => [],
        'expected_migrate_map_entries' => [
          'migmag_lookup_test_with_source_2' => [],
          'migmag_lookup_test_with_source_4' => [],
        ],
      ],
    ];
  }

  /**
   * Data provider for ::testPluginWithSqlSources.
   *
   * @return array[]
   *   The test cases.
   */
  public static function providerTestPluginWithSqlSources() {
    return [
      'Lookup for already migrated node "2" in the right migration, no stubbing' => [
        'value' => [10, 10, 'is'],
        'expected_transformed_value' => ['8', 10, 'is'],
        'plugin_configuration' => [
          'migration' => 'd7_node_complete:blog',
          'no_stub' => TRUE,
        ],
        'previous_migrate_map_entries' => [
          'd7_node_complete:blog' => [
            [
              'source_ids' => [
                'nid' => '10',
                'vid' => '10',
                'language' => 'is',
              ],
              'destination_ids' => ['8', 10, 'is'],
            ],
          ],
        ],
        'expected_migrate_map_entries' => [
          'd7_node_complete:blog' => [
            [
              'sourceid1' => '10',
              'sourceid2' => '10',
              'sourceid3' => 'is',
              'destid1' => '8',
              'destid2' => '10',
              'destid3' => 'is',
              'source_row_status' => '0',
              'rollback_action' => '0',
            ],
          ],
        ],
      ],

      'Lookup for already migrated node "6" in the right migration, no stubbing' => [
        'value' => 6,
        'expected_transformed_value' => ['6', 6, 'en'],
        'plugin_configuration' => [
          'migration' => [
            'd7_node_complete:test_content_type',
            'd7_node_complete:forum',
          ],
          'no_stub' => TRUE,
        ],
        'previous_migrate_map_entries' => [
          'd7_node_complete:forum' => [
            [
              'source_ids' => [
                'nid' => '6',
                'vid' => '6',
                'language' => 'en',
              ],
              'destination_ids' => ['6', 6, 'en'],
            ],
          ],
        ],
        'expected_migrate_map_entries' => [
          'd7_node_complete:test_content_type' => [],
          'd7_node_complete:forum' => [
            [
              'sourceid1' => '6',
              'sourceid2' => '6',
              'sourceid3' => 'en',
              'destid1' => '6',
              'destid2' => '6',
              'destid3' => 'en',
              'source_row_status' => '0',
              'rollback_action' => '0',
            ],
          ],
        ],
      ],

      'Stubbing node "5" with partial source IDs, with a base migration ID, no lookup' => [
        'value' => [5],
        'expected_transformed_value' => ['4', 5, 'en'],
        'plugin_configuration' => [
          'migration' => 'd7_node_complete',
          'no_stub' => FALSE,
        ],
        'previous_migrate_map_entries' => [],
        'expected_migrate_map_entries' => [
          'd7_node_complete:test_content_type' => [],
          'd7_node_complete:article' => [
            [
              'sourceid1' => '5',
              'sourceid2' => '5',
              'sourceid3' => 'en',
              'destid1' => '4',
              'destid2' => '5',
              'destid3' => 'en',
              'source_row_status' => '1',
              'rollback_action' => '0',
            ],
            [
              'sourceid1' => '5',
              'sourceid2' => '14',
              'sourceid3' => 'en',
              'destid1' => '4',
              'destid2' => '14',
              'destid3' => 'en',
              'source_row_status' => '1',
              'rollback_action' => '0',
            ],
          ],
          'd7_node_complete:blog' => [],
          'd7_node_complete:forum' => [],
          'd7_node_complete:et' => [],
        ],
      ],

      'Stubbing node "3" with partial source IDs in the right migration, no lookup' => [
        'value' => 3,
        'expected_transformed_value' => ['2', 3, 'is'],
        'plugin_configuration' => [
          'migration' => 'd7_node_complete:article',
          'no_stub' => FALSE,
        ],
        'previous_migrate_map_entries' => [],
        'expected_migrate_map_entries' => [
          'd7_node_complete:test_content_type' => NULL,
          'd7_node_complete:article' => [
            [
              'sourceid1' => '3',
              'sourceid2' => '12',
              'sourceid3' => 'is',
              'destid1' => '2',
              'destid2' => '12',
              'destid3' => 'is',
              'source_row_status' => '1',
              'rollback_action' => '0',
            ],
            [
              'sourceid1' => '3',
              'sourceid2' => '3',
              'sourceid3' => 'is',
              'destid1' => '2',
              'destid2' => '3',
              'destid3' => 'is',
              'source_row_status' => '1',
              'rollback_action' => '0',
            ],
          ],
          'd7_node_complete:blog' => NULL,
          'd7_node_complete:forum' => NULL,
          'd7_node_complete:et' => NULL,
        ],
      ],

      'Stubbing not-yet migrated node "1" with full IDs' => [
        'value' => ['1', '1', 'en'],
        'expected_transformed_value' => ['1', '1', 'en'],
        'plugin_configuration' => [
          'migration' => 'd7_node_complete',
        ],
        'previous_migrate_map_entries' => [],
        'expected_migrate_map_entries' => [
          'd7_node_complete:test_content_type' => [
            [
              'sourceid1' => '1',
              'sourceid2' => '1',
              'sourceid3' => 'en',
              'destid1' => '1',
              'destid2' => '1',
              'destid3' => 'en',
              'source_row_status' => '1',
              'rollback_action' => '0',
            ],
          ],
          'd7_node_complete:article' => [],
          'd7_node_complete:blog' => [],
          'd7_node_complete:forum' => [],
          'd7_node_complete:et' => [],
        ],
      ],

      'Stubbing not-yet migrated node "1" with some default value' => [
        'value' => ['1', '1', 'en'],
        'expected_transformed_value' => ['1', 1111, 'is'],
        'plugin_configuration' => [
          'migration' => 'd7_node_complete',
          'stub_default_values' => [
            'vid' => 'foo',
            'langcode' => 'bar',
          ],
        ],
        'previous_migrate_map_entries' => [],
        'expected_migrate_map_entries' => [
          'd7_node_complete:test_content_type' => [
            [
              'sourceid1' => '1',
              'sourceid2' => '1',
              'sourceid3' => 'en',
              'destid1' => '1',
              'destid2' => '1111',
              'destid3' => 'is',
              'source_row_status' => '1',
              'rollback_action' => '0',
            ],
          ],
          'd7_node_complete:article' => [],
          'd7_node_complete:blog' => [],
          'd7_node_complete:forum' => [],
          'd7_node_complete:et' => [],
        ],
        'row_data' => [
          'foo' => 1111,
          'bar' => 'is',
        ],
      ],

      'Looking for not-yet migrated node "1" without stubbing' => [
        'value' => 1,
        'expected_transformed_value' => NULL,
        'plugin_configuration' => [
          'migration' => 'd7_node_complete',
          'no_stub' => TRUE,
        ],
        'previous_migrate_map_entries' => [],
        'expected_migrate_map_entries' => [
          'd7_node_complete:test_content_type' => [],
          'd7_node_complete:article' => [],
          'd7_node_complete:blog' => [],
          'd7_node_complete:forum' => [],
          'd7_node_complete:et' => [],
        ],
      ],

      'Looking for missing node, without stubbing' => [
        'value' => 500,
        'expected_transformed_value' => NULL,
        'plugin_configuration' => [
          'migration' => 'd7_node_complete',
          'no_stub' => TRUE,
        ],
        'previous_migrate_map_entries' => [],
        'expected_migrate_map_entries' => [
          'd7_node_complete:test_content_type' => [],
          'd7_node_complete:article' => [],
          'd7_node_complete:blog' => [],
          'd7_node_complete:forum' => [],
          'd7_node_complete:et' => [],
        ],
      ],

      'Looking for missing node with stubbing' => [
        'value' => 500,
        'expected_transformed_value' => NULL,
        'plugin_configuration' => [
          'migration' => 'd7_node_complete',
          'no_stub' => FALSE,
        ],
        'previous_migrate_map_entries' => [],
        'expected_migrate_map_entries' => [
          'd7_node_complete:test_content_type' => [],
          'd7_node_complete:article' => [],
          'd7_node_complete:blog' => [],
          'd7_node_complete:forum' => [],
          'd7_node_complete:et' => [],
        ],
      ],

      'Looking for missing node with stubbing, with stub_id set' => [
        'value' => ['222', 546, 'is'],
        'expected_transformed_value' => ['546', '546', 'is'],
        'plugin_configuration' => [
          'migration' => 'd7_node_complete',
          'no_stub' => FALSE,
          'stub_id' => 'migmag_lookup_test_invalid_node_stubs',
        ],
        'previous_migrate_map_entries' => [],
        'expected_migrate_map_entries' => [
          'd7_node_complete:test_content_type' => [],
          'd7_node_complete:article' => [],
          'd7_node_complete:blog' => [],
          'd7_node_complete:forum' => [],
          'd7_node_complete:et' => [],
          'migmag_lookup_test_invalid_node_stubs' => [
            [
              'sourceid1' => '222',
              'sourceid2' => '546',
              'sourceid3' => 'is',
              'destid1' => '546',
              'destid2' => '546',
              'destid3' => 'is',
              'source_row_status' => '1',
              'rollback_action' => '0',
            ],
          ],
        ],
      ],

      'Looking for existing node with stubbing, with fallback_stub_id set' => [
        'value' => ['3', 12],
        'expected_transformed_value' => ['2', 12, 'is'],
        'plugin_configuration' => [
          'migration' => 'd7_node_complete',
          'no_stub' => FALSE,
          'fallback_stub_id' => 'migmag_lookup_test_invalid_node_stubs',
        ],
        'previous_migrate_map_entries' => [],
        'expected_migrate_map_entries' => [
          'd7_node_complete:test_content_type' => [],
          'd7_node_complete:article' => [
            [
              'sourceid1' => '3',
              'sourceid2' => '12',
              'sourceid3' => 'is',
              'destid1' => '2',
              'destid2' => '12',
              'destid3' => 'is',
              'source_row_status' => '1',
              'rollback_action' => '0',
            ],
          ],
          'd7_node_complete:blog' => [],
          'd7_node_complete:forum' => [],
          'd7_node_complete:et' => [],
          'migmag_lookup_test_invalid_node_stubs' => NULL,
        ],
      ],

      'Looking for missing node with stubbing, with fallback_stub_id set' => [
        'value' => ['222', 546, 'is'],
        'expected_transformed_value' => ['546', '546', 'is'],
        'plugin_configuration' => [
          'migration' => 'd7_node_complete',
          'no_stub' => FALSE,
          'fallback_stub_id' => 'migmag_lookup_test_invalid_node_stubs',
        ],
        'previous_migrate_map_entries' => [],
        'expected_migrate_map_entries' => [
          'd7_node_complete:test_content_type' => [],
          'd7_node_complete:article' => [],
          'd7_node_complete:blog' => [],
          'd7_node_complete:forum' => [],
          'd7_node_complete:et' => [],
          'migmag_lookup_test_invalid_node_stubs' => [
            [
              'sourceid1' => '222',
              'sourceid2' => '546',
              'sourceid3' => 'is',
              'destid1' => '546',
              'destid2' => '546',
              'destid3' => 'is',
              'source_row_status' => '1',
              'rollback_action' => '0',
            ],
          ],
        ],
      ],
    ];
  }

  /**
   * Data provider for ::testPluginWithSourceIds.
   *
   * @return array[]
   *   The test cases.
   */
  public static function providerTestPluginWithSourceIds() {
    return [
      'Stubbing row 5 with source ids config using base ID, set to 3 3' => [
        'value' => 5,
        'row_data' => [
          'derived_id' => 3,
          'derived_subid' => 3,
        ],
        'plugin_configuration' => [
          'migration' => [
            'migmag_lookup_test_with_source_4',
            'migmag_lookup_test_with_source_2',
          ],
          'fallback_stub_id' => 'migmag_lookup_test_derived:s_3_3',
          'no_stub' => FALSE,
          'source_ids' => [
            'migmag_lookup_test_derived' => [
              'derived_id',
              'derived_subid',
            ],
          ],
        ],
        'previous_migrate_map_entries' => [],
        'expected_transformed_value' => '100001',
        'expected_migrate_map_entries' => [
          'migmag_lookup_test_with_source_2' => [],
          'migmag_lookup_test_with_source_4' => [],
          'migmag_lookup_test_derived:s_1_1' => NULL,
          'migmag_lookup_test_derived:s_3_3' => [
            [
              'sourceid1' => '3',
              'sourceid2' => '3',
              'destid1' => '100001',
              'source_row_status' => '1',
              'rollback_action' => '0',
            ],
          ],
        ],
      ],

      'Stubbing row 10 with source ids config using full ID, set to 1 1' => [
        'value' => 10,
        'row_data' => [
          'derived_id' => 1,
          'derived_subid' => 1,
        ],
        'plugin_configuration' => [
          'migration' => [
            'migmag_lookup_test_with_source_2',
            'migmag_lookup_test_with_source_4',
          ],
          'fallback_stub_id' => 'migmag_lookup_test_derived:s_1_1',
          'no_stub' => FALSE,
          'source_ids' => [
            'migmag_lookup_test_derived:s_1_1' => [
              'derived_id',
              'derived_subid',
            ],
          ],
        ],
        'previous_migrate_map_entries' => [],
        'expected_transformed_value' => '1011',
        'expected_migrate_map_entries' => [
          'migmag_lookup_test_with_source_2' => [],
          'migmag_lookup_test_with_source_4' => [],
          'migmag_lookup_test_derived:s_1_1' => [
            [
              'sourceid1' => '1',
              'sourceid2' => '1',
              'destid1' => '1011',
              'source_row_status' => '1',
              'rollback_action' => '0',
            ],
          ],
          'migmag_lookup_test_derived:s_3_3' => NULL,
        ],
      ],

      // cspell:disable-next-line
      'Stubbing with source ids config set for all migrations (4 is valid in mltws_4)' => [
        'value' => ['a string', 'an another string'],
        'row_data' => [
          'id' => 4,
          'derived_id' => 2,
          'derived_subid' => 2,
        ],
        'plugin_configuration' => [
          'migration' => [
            'migmag_lookup_test_with_source_2',
            'migmag_lookup_test_with_source_4',
            'migmag_lookup_test_derived',
          ],
          'no_stub' => FALSE,
          'source_ids' => [
            'migmag_lookup_test_derived' => [
              'derived_id',
              'derived_subid',
            ],
            'migmag_lookup_test_with_source_2' => [
              'id',
            ],
            'migmag_lookup_test_with_source_4' => [
              'id',
            ],
          ],
        ],
        'previous_migrate_map_entries' => [],
        'expected_transformed_value' => '100',
        'expected_migrate_map_entries' => [
          'migmag_lookup_test_with_source_2' => [],
          'migmag_lookup_test_with_source_4' => [
            [
              'sourceid1' => '4',
              'destid1' => '100',
              'source_row_status' => '1',
              'rollback_action' => '0',
            ],
          ],
          'migmag_lookup_test_derived:s_1_1' => [],
          'migmag_lookup_test_derived:s_3_3' => [],
        ],
      ],

      // cspell:disable-next-line
      'Stubbing with source ids config set for all migrations (3 3 is valid in mltd:s_3_3)' => [
        'value' => ['a string', 'an another string'],
        'row_data' => [
          'id' => 3,
          'derived_id' => 3,
          'derived_subid' => 3,
        ],
        'plugin_configuration' => [
          'migration' => [
            'migmag_lookup_test_with_source_2',
            'migmag_lookup_test_with_source_4',
            'migmag_lookup_test_derived',
          ],
          'no_stub' => FALSE,
          'source_ids' => [
            'migmag_lookup_test_derived' => [
              'derived_id',
              'derived_subid',
            ],
            'migmag_lookup_test_with_source_2' => [
              'id',
            ],
            'migmag_lookup_test_with_source_4' => [
              'id',
            ],
          ],
        ],
        'previous_migrate_map_entries' => [],
        'expected_transformed_value' => '100001',
        'expected_migrate_map_entries' => [
          'migmag_lookup_test_with_source_2' => [],
          'migmag_lookup_test_with_source_4' => [],
          'migmag_lookup_test_derived:s_1_1' => [],
          'migmag_lookup_test_derived:s_3_3' => [
            [
              'sourceid1' => '3',
              'sourceid2' => '3',
              'destid1' => '100001',
              'source_row_status' => '1',
              'rollback_action' => '0',
            ],
          ],
        ],
      ],
    ];
  }

  /**
   * Checks the migration map records.
   *
   * @param array $expected_migration_map_entries
   *   The migration map record data to check, keyed by the migration's plugin
   *   ID.
   */
  protected function assertMigrationMaps(array $expected_migration_map_entries) :void {
    $migration_plugin_manager = $this->container->get('plugin.manager.migration');
    $connection = \Drupal::database();
    assert($migration_plugin_manager instanceof MigrationPluginManagerInterface);

    foreach ($expected_migration_map_entries as $migration_plugin_id => $expected_entries) {
      $migration = $migration_plugin_manager->createInstance($migration_plugin_id);
      assert($migration instanceof MigrationInterface);
      $id_map = $migration->getIdMap();
      assert($id_map instanceof Sql);

      // If no lookup was performed, the migration map shouldn't exist.
      if ($expected_entries === NULL) {
        $this->assertFalse(
          $connection->schema()->tableExists($id_map->mapTableName()),
          sprintf(
            "Migration map table of '%s' shouldn't exist, but it does.",
            $migration_plugin_id
          )
        );
        continue;
      }

      $actual_id_map_records = $connection->select($id_map->mapTableName())
        ->fields($id_map->mapTableName())
        ->execute()
        ->fetchAll(\PDO::FETCH_ASSOC);
      $cleaned_actual_id_map_records = array_reduce($actual_id_map_records, function (array $carry, array $record) {
        unset($record['source_ids_hash']);
        unset($record['hash']);
        // We're unable to mock this timestamp.
        // See https://drupal.org/i/3198608.
        unset($record['last_imported']);
        $carry[] = $record;
        return $carry;
      }, []);

      $this->assertEquals(
        $expected_entries,
        $cleaned_actual_id_map_records,
        sprintf(
          "Expected migration map of '%s' records aren't match with the actual ones.",
          $migration_plugin_id
        )
      );
    }
  }

  /**
   * Pre-fills migration maps.
   *
   * @param array $migrate_map_entries
   *   The migration map record data to save, keyed by the migration's plugin
   *   ID.
   */
  protected function populateMigrationMaps(array $migrate_map_entries) :void {
    $migration_plugin_manager = $this->container->get('plugin.manager.migration');
    assert($migration_plugin_manager instanceof MigrationPluginManagerInterface);

    // Pre-fill migration map if needed.
    foreach ($migrate_map_entries as $migration_plugin_id => $entries) {
      $migration = $migration_plugin_manager->createInstance($migration_plugin_id);
      assert($migration instanceof MigrationInterface);
      $id_map = $migration->getIdMap();
      $migration_rows = iterator_to_array($migration->getSourcePlugin(), FALSE);

      foreach ($entries as $entry) {
        [
          'source_ids' => $source_ids,
          'destination_ids' => $destination_id_values,
        ] = $entry;
        $matching_row = array_reduce($migration_rows, function ($matching_row, Row $row) use ($source_ids) {
          if ($row->getSourceIdValues() === $source_ids) {
            return $row;
          }
          return $matching_row;
        }, NULL);
        $this->assertInstanceOf(
          Row::class,
          $matching_row,
          sprintf(
            "'%s' wasn't able to identify the row which should get a destination ID record for migration '%s'. This is an example row source IDs array: %s",
            __METHOD__,
            $migration->getPluginId(),
            Variable::export(reset($migration_rows)->getSourceIdValues())
          )
        );
      }
      $id_map->saveIdMapping($matching_row, (array) $destination_id_values);
    }
  }

  /**
   * Loads a database fixture into the source database connection.
   *
   * Copied from MigrateDrupalTestBase.
   *
   * @param string $path
   *   Path to the dump file.
   *
   * @see \Drupal\Tests\migrate_drupal\Kernel\MigrateDrupalTestBase::loadFixture()
   */
  protected function loadFixture($path) {
    $default_db = Database::getConnection()->getKey();
    Database::setActiveConnection($this->sourceDatabase->getKey());

    if (substr($path, -3) == '.gz') {
      $path = 'compress.zlib://' . $path;
    }
    require $path;

    Database::setActiveConnection($default_db);
  }

}
