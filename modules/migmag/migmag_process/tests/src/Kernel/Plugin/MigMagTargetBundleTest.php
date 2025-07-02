<?php

namespace Drupal\Tests\migmag_process\Kernel\Plugin;

use Drupal\Tests\migrate\Kernel\MigrateTestBase;
use Drupal\migmag_process\Plugin\migrate\process\MigMagTargetBundle;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;

/**
 * Tests the MigMagTargetBundle migrate process plugin with real migrations.
 *
 * @coversDefaultClass \Drupal\migmag_process\Plugin\migrate\process\MigMagTargetBundle
 *
 * @group migmag_process
 */
class MigMagTargetBundleTest extends MigrateTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'entity_test',
    'migmag_process',
    'migmag_target_bundle_test',
    'taxonomy',
  ];

  /**
   * Tests the MigMagTargetBundle migrate process plugin.
   *
   * @param int|string|array $value
   *   The value to pass to the lookup plugin instance.
   * @param array|null $row_data
   *   The actual migration row's data.
   * @param string|array $expected_transformed_value
   *   The expected transformed value.
   * @param array $plugin_configuration
   *   The configuration the plugin should be tested with.
   * @param null|string|string[] $migrations_to_execute
   *   An array of migration IDs to execute before testing transform.
   *
   * @dataProvider providerTestPlugin
   */
  public function testPlugin($value, $row_data, $expected_transformed_value, array $plugin_configuration, $migrations_to_execute = NULL) {
    $migration = $this->prophesize(MigrationInterface::class);
    $executable = $this->prophesize(MigrateExecutable::class);
    $row_data_source = $row_data['source'] ?? [
      'dummy_source_property' => 'dummy_source_data',
    ];
    $row = new Row(
      $row_data_source,
      array_combine(array_keys($row_data_source), array_keys($row_data_source))
    );
    foreach ($row_data['destination'] ?? [] as $destination_property => $destination_value) {
      $row->setDestinationProperty($destination_property, $destination_value);
    }

    if ($migrations_to_execute) {
      $this->startCollectingMessages();
      foreach ((array) $migrations_to_execute as $migration_to_execute) {
        $this->executeMigrations((array) $migration_to_execute);
      }
      $this->assertEmpty($this->migrateMessages);
    }

    $plugin = MigMagTargetBundle::create(
      $this->container,
      $plugin_configuration,
      'migmag_target_bundle',
      [],
      $migration->reveal()
    );
    $actual_transformed_value = $plugin->transform($value, $executable->reveal(), $row, 'destination_property');
    $this->assertEquals(
      $expected_transformed_value,
      $actual_transformed_value
    );
  }

  /**
   * Test the plugin with comment types.
   *
   * @param int|string|array $value
   *   The value to pass to the lookup plugin instance.
   * @param array|null $row_data
   *   The actual migration row's data.
   * @param string|array $expected_transformed_value
   *   The expected transformed value.
   * @param array $plugin_configuration
   *   The configuration the plugin should be tested with.
   * @param null|string|string[] $migrations_to_execute
   *   An array of migration IDs to execute before testing transform.
   *
   * @dataProvider providerTestPluginWithCommentTypes
   */
  public function testPluginWithCommentTypes($value, $row_data, $expected_transformed_value, array $plugin_configuration, $migrations_to_execute = NULL) {
    $this->enableModules([
      'comment',
      'field',
      'filter',
      'text',
      'system',
    ]);
    $this->installConfig(['comment']);

    self::testPlugin($value, $row_data, $expected_transformed_value, $plugin_configuration, $migrations_to_execute);
  }

  /**
   * Test the plugin with combined config, including source & destination types.
   *
   * @param int|string|array $value
   *   The value to pass to the lookup plugin instance.
   * @param array|null $row_data
   *   The actual migration row's data.
   * @param string|array $expected_transformed_value
   *   The expected transformed value.
   * @param array $plugin_configuration
   *   The configuration the plugin should be tested with.
   * @param null|string|string[] $migrations_to_execute
   *   An array of migration IDs to execute before testing transform.
   *
   * @dataProvider providerTestPluginWithCombinedConfig
   */
  public function testPluginWithCombinedConfig($value, $row_data, $expected_transformed_value, array $plugin_configuration, $migrations_to_execute = NULL) {
    self::testPlugin($value, $row_data, $expected_transformed_value, $plugin_configuration, $migrations_to_execute);
  }

  /**
   * Data provider for ::testPlugin.
   *
   * @return array[]
   *   The test cases.
   */
  public static function providerTestPlugin() {
    return [
      'Not yet migrated taxonomy vocabulary, with fallback' => [
        'value' => 'missing',
        'row_data' => NULL,
        'expected_transformed_value' => 'missing',
        'plugin_configuration' => [
          'null_if_missing' => FALSE,
        ],
      ],

      'Not yet migrated taxonomy vocabulary without fallback' => [
        'value' => 'vocabulary 1',
        'row_data' => NULL,
        'expected_transformed_value' => NULL,
        'plugin_configuration' => [
          'null_if_missing' => TRUE,
        ],
      ],

      'Entity test bundle with non-matching source entity type, no fallback' => [
        'value' => 'bundle 1',
        'row_data' => [
          'source' => ['source_prop' => 'non-matching-source-type'],
        ],
        'expected_transformed_value' => NULL,
        'plugin_configuration' => [
          'source_entity_type' => 'source_prop',
          'null_if_missing' => TRUE,
        ],
        'migrations_to_execute' => 'migmag_tbt_entity_test',
      ],

      'Entity test bundle with missing source entity type, no fallback' => [
        'value' => 'bundle 1',
        'row_data' => [
          'source' => ['other_source_prop' => 1],
        ],
        'expected_transformed_value' => NULL,
        'plugin_configuration' => [
          'source_entity_type' => 'source_prop',
          'null_if_missing' => TRUE,
        ],
        'migrations_to_execute' => 'migmag_tbt_entity_test',
      ],

      'Entity test bundle, no fallback, without lookup migrations config' => [
        'value' => 'bundle 1',
        'row_data' => [
          'source' => ['source_prop' => 'entity_test_with_bundle'],
        ],
        'expected_transformed_value' => NULL,
        'plugin_configuration' => [
          'source_entity_type' => 'source_prop',
          'null_if_missing' => TRUE,
        ],
        'migrations_to_execute' => 'migmag_tbt_entity_test',
      ],

      "Taxonomy vocabulary 'vocabulary 1', no fallback, single lookup migration" => [
        'value' => 'vocabulary 1',
        'row_data' => [
          'source' => ['source_prop' => 'taxonomy_vocabulary'],
        ],
        'expected_transformed_value' => 'vocabulary_1',
        'plugin_configuration' => [
          'source_entity_type' => 'source_prop',
          'null_if_missing' => TRUE,
          'source_lookup_migrations' => [
            'taxonomy_vocabulary' => 'migmag_tbt_vocabulary',
          ],
        ],
        'migrations_to_execute' => 'migmag_tbt_vocabulary',
      ],

      "Taxonomy vocabulary 'derivative 2 vocab' with derived lookup migrations" => [
        'value' => 'derivative 2 vocab',
        'row_data' => [
          'source' => ['source_prop' => 'taxonomy_vocabulary'],
        ],
        'expected_transformed_value' => 'derivative_2_vocab',
        'plugin_configuration' => [
          'source_entity_type' => 'source_prop',
          'null_if_missing' => TRUE,
          'source_lookup_migrations' => [
            'taxonomy_vocabulary' => [
              'migmag_tbt_vocabulary',
              'migmag_tbt_vocabulary_derived',
            ],
          ],
        ],
        'migrations_to_execute' => [
          'migmag_tbt_vocabulary',
          'migmag_tbt_vocabulary_derived',
        ],
      ],

      "Entity test bundle 'bundle 2' with non-matching custom destination entity type conf" => [
        'value' => 'bundle 2',
        'row_data' => [
          'destination' => ['destination_prop' => 'taxonomy_vocabulary'],
        ],
        'expected_transformed_value' => NULL,
        'plugin_configuration' => [
          'destination_entity_type' => '@destination_prop',
          'null_if_missing' => TRUE,
        ],
        'migrations_to_execute' => [
          'migmag_tbt_entity_test',
          'migmag_tbt_vocabulary',
          'migmag_tbt_vocabulary_derived',
        ],
      ],

      "Entity test bundle 'bundle 2' with default destination entity type conf" => [
        'value' => 'bundle 2',
        'row_data' => [
          'destination' => ['entity_type' => 'taxonomy_vocabulary'],
        ],
        'expected_transformed_value' => NULL,
        'plugin_configuration' => [
          'null_if_missing' => TRUE,
        ],
        'migrations_to_execute' => [
          'migmag_tbt_entity_test',
          'migmag_tbt_vocabulary',
          'migmag_tbt_vocabulary_derived',
        ],
      ],

      "Entity test bundle 'bundle 2' with matching destination entity type" => [
        'value' => 'bundle 2',
        'row_data' => [
          'destination' => ['destination_prop' => 'entity_test_with_bundle'],
        ],
        'expected_transformed_value' => 'bundle_2',
        'plugin_configuration' => [
          'destination_entity_type' => '@destination_prop',
          'null_if_missing' => TRUE,
        ],
        'migrations_to_execute' => [
          'migmag_tbt_entity_test',
          'migmag_tbt_vocabulary',
          'migmag_tbt_vocabulary_derived',
        ],
      ],

      "Taxonomy vocabulary 'vocabulary 2' with matching destination entity type" => [
        'value' => 'vocabulary 2',
        'row_data' => [
          'destination' => ['destination_prop' => 'taxonomy_term'],
        ],
        'expected_transformed_value' => 'vocabulary_2',
        'plugin_configuration' => [
          'destination_entity_type' => '@destination_prop',
          'null_if_missing' => TRUE,
        ],
        'migrations_to_execute' => [
          'migmag_tbt_entity_test',
          'migmag_tbt_vocabulary',
          'migmag_tbt_vocabulary_derived',
        ],
      ],

      "Taxonomy vocabulary 'derivative 1 vocab 2' with matching destination entity type" => [
        'value' => 'derivative 1 vocab 2',
        'row_data' => [
          'destination' => ['destination_prop' => 'taxonomy_term'],
        ],
        'expected_transformed_value' => 'derivative_1_vocab_2',
        'plugin_configuration' => [
          'destination_entity_type' => '@destination_prop',
          'null_if_missing' => TRUE,
        ],
        'migrations_to_execute' => [
          'migmag_tbt_entity_test',
          'migmag_tbt_vocabulary',
          'migmag_tbt_vocabulary_derived',
        ],
      ],
    ];
  }

  /**
   * Data provider for ::testPluginWithCommentTypes.
   *
   * @return array[]
   *   The test cases.
   */
  public static function providerTestPluginWithCommentTypes() {
    return [
      "Comment bundle 'test_type' looking for 'test_type'" => [
        'value' => 'test_type',
        'row_data' => [
          'destination' => ['destination_prop' => 'comment'],
        ],
        'expected_transformed_value' => 'comment_node_test_type',
        'plugin_configuration' => [
          'destination_entity_type' => '@destination_prop',
          'null_if_missing' => TRUE,
        ],
        'migrations_to_execute' => 'migmag_tbt_comment',
      ],

      "Comment bundle 'test_type' looking for 'comment_node_test_type'" => [
        'value' => 'comment_node_test_type',
        'row_data' => [
          'destination' => ['destination_prop' => 'comment'],
        ],
        'expected_transformed_value' => 'comment_node_test_type',
        'plugin_configuration' => [
          'destination_entity_type' => '@destination_prop',
          'null_if_missing' => TRUE,
        ],
        'migrations_to_execute' => 'migmag_tbt_comment',
      ],

      "Comment bundle 'comment_node_test' looking for 'comment_node_test'" => [
        'value' => 'comment_node_test',
        'row_data' => [
          'destination' => ['destination_prop' => 'comment'],
        ],
        'expected_transformed_value' => 'comment_node_comment_node_test',
        'plugin_configuration' => [
          'destination_entity_type' => '@destination_prop',
          'null_if_missing' => TRUE,
        ],
        'migrations_to_execute' => 'migmag_tbt_comment',
      ],

      "Comment bundle 'comment_node_test' looking for 'comment_node_comment_node_test'" => [
        'value' => 'comment_node_comment_node_test',
        'row_data' => [
          'destination' => ['destination_prop' => 'comment'],
        ],
        'expected_transformed_value' => 'comment_node_comment_node_test',
        'plugin_configuration' => [
          'destination_entity_type' => '@destination_prop',
          'null_if_missing' => TRUE,
        ],
        'migrations_to_execute' => 'migmag_tbt_comment',
      ],

      "Comment bundle 'forum' looking for 'forum'" => [
        'value' => 'forum',
        'row_data' => [
          'destination' => ['destination_prop' => 'comment'],
        ],
        'expected_transformed_value' => 'comment_forum',
        'plugin_configuration' => [
          'destination_entity_type' => '@destination_prop',
          'null_if_missing' => TRUE,
        ],
        'migrations_to_execute' => 'migmag_tbt_comment',
      ],

      "Comment bundle 'forum' looking for 'comment_node_forum'" => [
        'value' => 'comment_node_forum',
        'row_data' => [
          'destination' => ['destination_prop' => 'comment'],
        ],
        'expected_transformed_value' => 'comment_forum',
        'plugin_configuration' => [
          'destination_entity_type' => '@destination_prop',
          'null_if_missing' => TRUE,
        ],
        'migrations_to_execute' => 'migmag_tbt_comment',
      ],
    ];
  }

  /**
   * Data provider for ::testPluginWithCombinedConfig.
   *
   * @return array[]
   *   The test cases.
   */
  public static function providerTestPluginWithCombinedConfig() {
    return [
      "Migrations used in source lookup should be excluded with destination entity type" => [
        'value' => 'vocabulary 2',
        'row_data' => [
          'destination' => ['destination_prop' => 'taxonomy_term'],
        ],
        'expected_transformed_value' => NULL,
        'plugin_configuration' => [
          'source_entity_type' => 'source_entity_type_prop',
          'source_lookup_migrations' => [
            'something_else' => [
              'migmag_tbt_vocabulary_derived',
              'migmag_tbt_vocabulary',
            ],
          ],
          'destination_entity_type' => '@destination_prop',
          'null_if_missing' => TRUE,
        ],
        'migrations_to_execute' => [
          'migmag_tbt_vocabulary',
          'migmag_tbt_vocabulary_derived',
        ],
      ],

      "Order of the source lookup migrations determines the returned value" => [
        'value' => 'vocabulary 2',
        'row_data' => [
          'source' => ['source_entity_type_prop' => 'something_else'],
          'destination' => ['destination_prop' => 'taxonomy_term'],
        ],
        'expected_transformed_value' => 'vocabulary_2_1',
        'plugin_configuration' => [
          'source_entity_type' => 'source_entity_type_prop',
          'source_lookup_migrations' => [
            'something_else' => [
              'migmag_tbt_vocabulary_derived:id_collision',
              'migmag_tbt_vocabulary',
            ],
          ],
          'destination_entity_type' => '@destination_prop',
          'null_if_missing' => TRUE,
        ],
        'migrations_to_execute' => [
          'migmag_tbt_vocabulary',
          'migmag_tbt_vocabulary_derived:id_collision',
        ],
      ],

      "Taxonomy vocabulary 'vocabulary 2' with lookup addressing specific migration" => [
        'value' => 'vocabulary 2',
        'row_data' => [
          'source' => ['source_entity_type_prop' => 'something_else'],
          'destination' => ['destination_prop' => 'taxonomy_term'],
        ],
        'expected_transformed_value' => 'vocabulary_2_1',
        'plugin_configuration' => [
          'source_entity_type' => 'source_entity_type_prop',
          'source_lookup_migrations' => [
            'something_else' => 'migmag_tbt_vocabulary',
          ],
          'destination_entity_type' => '@destination_prop',
          'null_if_missing' => TRUE,
        ],
        'migrations_to_execute' => [
          'migmag_tbt_vocabulary_derived',
          'migmag_tbt_vocabulary',
        ],
      ],

      "Taxonomy vocabulary 'vocabulary 2' without lookup conf" => [
        'value' => 'vocabulary 2',
        'row_data' => [
          'destination' => ['destination_prop' => 'taxonomy_term'],
        ],
        'expected_transformed_value' => 'vocabulary_2_1',
        'plugin_configuration' => [
          'source_entity_type' => 'source_entity_type_prop',
          'source_lookup_migrations' => [
            'something_else' => 'migmag_tbt_vocabulary',
          ],
          'destination_entity_type' => '@destination_prop',
          'null_if_missing' => TRUE,
        ],
        'migrations_to_execute' => [
          'migmag_tbt_vocabulary',
          'migmag_tbt_vocabulary_derived',
        ],
      ],
    ];
  }

}
