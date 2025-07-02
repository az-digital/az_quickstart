<?php

namespace Drupal\Tests\migmag_process\Kernel\Plugin;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\TypedData\TranslatableInterface;
use Drupal\TestTools\Random;
use Drupal\Tests\migrate\Kernel\MigrateTestBase;
use Drupal\migmag_process\Plugin\migrate\process\MigMagGetEntityProperty;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate\Row;

/**
 * Tests the MigMagLookup migrate process plugin with real migrations.
 *
 * @coversDefaultClass \Drupal\migmag_process\Plugin\migrate\process\MigMagGetEntityProperty
 *
 * @group migmag_process
 */
class MigMagGetEntityPropertyTest extends MigrateTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'content_translation',
    'field',
    'language',
    'migmag_process',
    'node',
    'system',
    'text',
    'user',
  ];

  /**
   * Tests the plugin's transform method.
   *
   * @param array[][] $entity_values
   *   Values of the test entities to create before testing the plugin.
   *   Keys should be entity type IDs, values should be an array of entity
   *   values.
   * @param array $plugin_config
   *   The configuration to test the plugin with.
   * @param array|string|int $input_value
   *   The input value.
   * @param mixed $expected_value
   *   The expected value.
   * @param string|null $expected_exception_class
   *   The FQCN of the expected exception, if an exception should be thrown with
   *   the actual test data.
   * @param string|null $expected_exception_message
   *   The message of the expected exception, if any.
   *
   * @dataProvider providerTestTransform
   */
  public function testTransform(array $entity_values, array $plugin_config, $input_value, $expected_value, ?string $expected_exception_class = NULL, ?string $expected_exception_message = NULL) {
    $this->installSchema('system', ['sequences']);
    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installSchema('node', ['node_access']);
    $this->createTestEntities($entity_values);

    $executable = $this->prophesize(MigrateExecutable::class);
    $row = new Row(['id' => 'value'], ['id' => 'id']);

    if ($expected_exception_class) {
      $this->expectException($expected_exception_class);
      if ($expected_exception_message) {
        $this->expectExceptionMessage($expected_exception_message);
      }
    }
    $plugin = MigMagGetEntityProperty::create(
      $this->container,
      $plugin_config,
      'migmag_get_entity_property',
      []
    );

    $actual_transformed_value = $plugin->transform($input_value, $executable->reveal(), $row, 'destination_property');
    $this->assertEquals(
      $expected_value,
      $actual_transformed_value
    );
  }

  /**
   * Data provider for ::testTransform.
   *
   * @return array[]
   *   The test cases.
   */
  public static function providerTestTransform(): array {
    $field_storage_testcase_entities = [
      'field_storage_config' => [
        [
          'uuid' => '3dc5a501-4b59-49de-9eea-2266c0e700fe',
          'entity_type' => 'user',
          'field_name' => 'test_field',
          'type' => 'string',
          'cardinality' => -1,
        ],
      ],
    ];
    $user_testcase_entities = [
      'user' => [
        [
          'name' => 'test_user',
          'uid' => 5,
          'mail' => 'test_user@localhost',
        ],
      ],
    ];
    $node_testcase_entities = $user_testcase_entities + [
      'configurable_language' => [
        ['id' => 'hu'],
        ['id' => 'fr'],
      ],
      'node_type' => [
        ['type' => 'test_type'],
      ],
      'node' => [
        [
          'type' => 'test_type',
          'title' => 'Test node #1 rev #11 EN (def)',
          'nid' => '1',
          'vid' => '11',
          'langcode' => 'en',
          'uid' => '5',
        ],
        [
          'type' => 'test_type',
          'title' => 'Test node #1 rev #11 HU',
          'nid' => '1',
          'vid' => 11,
          'langcode' => 'hu',
          'uid' => '5',
        ],
        [
          'type' => 'test_type',
          'title' => 'Test node #1 rev #22 EN (def)',
          'nid' => '1',
          'vid' => 22,
          'langcode' => 'en',
          'uid' => '5',
        ],
        [
          'type' => 'test_type',
          'title' => 'Test node #1 rev #22 HU',
          'nid' => '1',
          'vid' => '22',
          'langcode' => 'hu',
          'uid' => '5',
        ],
        [
          'type' => 'test_type',
          'title' => 'Test node #1 rev #33 EN (def)',
          'nid' => '1',
          'vid' => '33',
          'langcode' => 'en',
          'uid' => '5',
        ],
        [
          'type' => 'test_type',
          'title' => 'Test node #1 rev #33 FR',
          'nid' => '1',
          'vid' => '33',
          'langcode' => 'fr',
          'uid' => '5',
        ],
        [
          'type' => 'test_type',
          'title' => 'Test node #2 HU (def)',
          'nid' => '2',
          'vid' => '44',
          'langcode' => 'hu',
          'uid' => '5',
        ],
      ],
    ];

    return [
      'field storage: id' => [
        'entity_values' => $field_storage_testcase_entities,
        'plugin_config' => [
          'entity_type_id' => 'field_storage_config',
          'property' => 'id',
        ],
        'input_value' => 'user.test_field',
        'expected_value' => 'user.test_field',
      ],

      'field storage: uuid' => [
        'entity_values' => $field_storage_testcase_entities,
        'plugin_config' => [
          'entity_type_id' => 'field_storage_config',
          'property' => 'uuid',
        ],
        'input_value' => 'user.test_field',
        'expected_value' => '3dc5a501-4b59-49de-9eea-2266c0e700fe',
      ],

      'field storage: entity_type' => [
        'entity_values' => $field_storage_testcase_entities,
        'plugin_config' => [
          'entity_type_id' => 'field_storage_config',
          'property' => 'entity_type',
        ],
        'input_value' => 'user.test_field',
        'expected_value' => 'user',
      ],

      'field storage: settings' => [
        'entity_values' => $field_storage_testcase_entities,
        'plugin_config' => [
          'entity_type_id' => 'field_storage_config',
          'property' => 'settings',
        ],
        'input_value' => 'user.test_field',
        'expected_value' => [
          'max_length' => 255,
          'is_ascii' => FALSE,
          'case_sensitive' => FALSE,
        ],
      ],

      'field storage: toArray' => [
        'entity_values' => $field_storage_testcase_entities,
        'plugin_config' => [
          'entity_type_id' => 'field_storage_config',
          'property' => 'toArray',
        ],
        'input_value' => 'user.test_field',
        'expected_value' => [
          'uuid' => '3dc5a501-4b59-49de-9eea-2266c0e700fe',
          'langcode' => 'en',
          'status' => TRUE,
          'dependencies' => [
            'module' => ['user'],
          ],
          'id' => 'user.test_field',
          'field_name' => 'test_field',
          'entity_type' => 'user',
          'type' => 'string',
          'settings' => [
            'max_length' => 255,
            'is_ascii' => FALSE,
            'case_sensitive' => FALSE,
          ],
          'module' => 'core',
          'locked' => FALSE,
          'cardinality' => -1,
          'translatable' => TRUE,
          'indexes' => [],
          'persist_with_no_fields' => FALSE,
          'custom_storage' => FALSE,
        ],
      ],

      'user: name' => [
        'entity_values' => $user_testcase_entities,
        'plugin_config' => [
          'entity_type_id' => 'user',
          'property' => 'name',
        ],
        'input_value' => 5,
        'expected_value' => [['value' => 'test_user']],
      ],

      'user: label' => [
        'entity_values' => $user_testcase_entities,
        'plugin_config' => [
          'entity_type_id' => 'user',
          'property' => 'label',
        ],
        'input_value' => 5,
        'expected_value' => 'test_user',
      ],

      'user: mail' => [
        'entity_values' => $user_testcase_entities,
        'plugin_config' => [
          'entity_type_id' => 'user',
          'property' => 'mail',
        ],
        'input_value' => 5,
        'expected_value' => [['value' => 'test_user@localhost']],
      ],

      'user: missing property' => [
        'entity_values' => $user_testcase_entities,
        'plugin_config' => [
          'entity_type_id' => 'user',
          'property' => Random::string(70),
        ],
        'input_value' => 5,
        'expected_value' => NULL,
      ],

      'user: setters cannot be used' => [
        'entity_values' => $user_testcase_entities,
        'plugin_config' => [
          'entity_type_id' => 'user',
          'property' => 'setLastLoginTime',
        ],
        'input_value' => 5,
        'expected_value' => NULL,
      ],

      'user: array input value' => [
        'entity_values' => $user_testcase_entities,
        'plugin_config' => [
          'entity_type_id' => 'user',
          'property' => 'label',
        ],
        'input_value' => [5],
        'expected_value' => 'test_user',
      ],

      'missing entity' => [
        'entity_values' => [],
        'plugin_config' => [
          'entity_type_id' => 'user',
          'property' => 'label',
        ],
        'input_value' => Random::string(4),
        'expected_value' => NULL,
      ],

      'missing entity storage' => [
        'entity_values' => [],
        'plugin_config' => [
          'entity_type_id' => 'missing',
          'property' => 'label',
        ],
        'input_value' => Random::string(4),
        'expected_value' => NULL,
        'expected_exception_class' => PluginNotFoundException::class,
        'expected_exception_message' => 'The "missing" entity type does not exist.',
      ],

      'node: load the default revision' => [
        'entity_values' => $node_testcase_entities,
        'plugin_config' => [
          'entity_type_id' => 'node',
          'property' => 'label',
        ],
        'input_value' => [1],
        'expected_value' => 'Test node #1 rev #33 EN (def)',
      ],

      'node: load a previous revision' => [
        'entity_values' => $node_testcase_entities,
        'plugin_config' => [
          'entity_type_id' => 'node',
          'property' => 'label',
          'load_revision' => TRUE,
        ],
        'input_value' => 11,
        'expected_value' => 'Test node #1 rev #11 EN (def)',
      ],

      'node: get the hungarian translation of the active revision' => [
        'entity_values' => $node_testcase_entities,
        'plugin_config' => [
          'entity_type_id' => 'node',
          'property' => 'label',
          'load_translation' => TRUE,
        ],
        'input_value' => [1, 'hu'],
        'expected_value' => 'Test node #1 rev #22 HU',
      ],

      'node: get the hungarian translation of a previous revision' => [
        'entity_values' => $node_testcase_entities,
        'plugin_config' => [
          'entity_type_id' => 'node',
          'property' => 'label',
          'load_revision' => TRUE,
          'load_translation' => TRUE,
        ],
        'input_value' => [11, 'hu'],
        'expected_value' => 'Test node #1 rev #11 HU',
      ],

      'node: get the english (def) translation of a previous revision' => [
        'entity_values' => $node_testcase_entities,
        'plugin_config' => [
          'entity_type_id' => 'node',
          'property' => 'label',
          'load_revision' => TRUE,
          'load_translation' => TRUE,
        ],
        'input_value' => [11, 'en'],
        'expected_value' => 'Test node #1 rev #11 EN (def)',
      ],

      'node: get missing french translation' => [
        'entity_values' => $node_testcase_entities,
        'plugin_config' => [
          'entity_type_id' => 'node',
          'property' => 'label',
          'load_revision' => TRUE,
          'load_translation' => TRUE,
        ],
        'input_value' => [22, 'fr'],
        'expected_value' => 'Test node #1 rev #22 EN (def)',
      ],

      'node: always the default translation is loaded' => [
        'entity_values' => $node_testcase_entities,
        'plugin_config' => [
          'entity_type_id' => 'node',
          'property' => 'label',
          'load_translation' => TRUE,
        ],
        'input_value' => [2, 'is'],
        'expected_value' => 'Test node #2 HU (def)',
      ],
    ];
  }

  /**
   * Creates the test entities.
   *
   * @param array[][] $test_entity_values
   *   Values of the test entities to create before testing the plugin.
   *   Keys should be entity type IDs, values should be an array of entity
   *   values.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *   When any of the test entities cannot be saved.
   */
  protected function createTestEntities(array $test_entity_values): void {
    foreach (array_keys($test_entity_values) as $entity_type_id) {
      $test_entity_storage = $this->container->get('entity_type.manager')->getStorage($entity_type_id);
      assert($test_entity_storage instanceof EntityStorageInterface);

      $entity_type = $test_entity_storage->getEntityType();
      $id_key = $entity_type->getKey('id');
      $revision_key = $entity_type->getKey('revision');
      $langcode_key = $entity_type->getKey('langcode');
      $previous_entity_ids = [];
      $test_entity = NULL;

      foreach ($test_entity_values[$entity_type_id] as $entity_data) {
        $new_revision = FALSE;
        if (
          !empty($previous_entity_ids) &&
          ($previous_entity_ids[$id_key] ?? NULL) == ($entity_data[$id_key] ?? NULL)
        ) {
          $this->assertTrue($entity_type->isRevisionable() || $entity_type->isTranslatable());

          // Do we want to add a new revision, or a new translation to the
          // recently saved entity?
          if (
            $entity_type->isRevisionable() &&
            ($previous_entity_ids[$revision_key] ?? NULL) != ($entity_data[$revision_key] ?? NULL)
          ) {
            $test_entity = $test_entity_storage->load($entity_data[$id_key]);
            $test_entity->setNewRevision(TRUE);
            foreach ($entity_data as $key => $value) {
              $test_entity->set($key, $value);
            }
            $new_revision = TRUE;
          }

          if (
            $entity_type->isTranslatable() &&
            ($previous_entity_ids[$langcode_key] ?? NULL) != ($entity_data[$langcode_key] ?? NULL)
            && !$new_revision
          ) {
            assert($test_entity instanceof TranslatableInterface);

            if (!$test_entity->hasTranslation($entity_data[$langcode_key])) {
              $translation = array_merge(
                array_diff_key($test_entity->toArray(), [$langcode_key => $langcode_key]),
                array_diff_key($entity_data, [$langcode_key => $langcode_key])
              );
              $test_entity = $test_entity->addTranslation($entity_data[$langcode_key], $translation);
            }
            else {
              $test_entity = $test_entity->getTranslation($entity_data[$langcode_key]);
              $test_entity->setNewRevision(TRUE);
              foreach (array_diff_key($entity_data, [$langcode_key => $langcode_key]) as $key => $value) {
                $test_entity->set($key, $value);
              }
            }
          }
        }
        else {
          $test_entity = $test_entity_storage->create($entity_data);
        }

        $test_entity->save();

        $previous_entity_ids = [$id_key => $test_entity->id()];
        if ($entity_type->isRevisionable()) {
          $previous_entity_ids[$revision_key] = $test_entity->getRevisionId();
        }
        if ($entity_type->isTranslatable()) {
          $previous_entity_ids[$langcode_key] = $test_entity->language()->getId();
        }
      }
    }
  }

}
