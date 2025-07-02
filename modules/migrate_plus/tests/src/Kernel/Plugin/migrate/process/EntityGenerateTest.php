<?php

declare(strict_types = 1);

namespace Drupal\Tests\migrate_plus\Kernel\Plugin\migrate\process;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate\MigrateMessageInterface;
use Drupal\migrate\Plugin\MigrationPluginManager;
use Drupal\node\Entity\NodeType;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Tests\field\Traits\EntityReferenceFieldCreationTrait;

/**
 * Tests the migration plugin.
 *
 * @coversDefaultClass \Drupal\migrate_plus\Plugin\migrate\process\EntityGenerate
 * @group migrate_plus
 */
final class EntityGenerateTest extends KernelTestBase implements MigrateMessageInterface {

  use EntityReferenceFieldCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'migrate_plus',
    'migrate',
    'user',
    'system',
    'node',
    'taxonomy',
    'field',
    'text',
    'filter',
  ];

  private static ?string $bundle = 'page';
  private static ?string $fieldName = 'field_entity_reference';
  private static ?string $vocabulary = 'fruit';
  protected ?MigrationPluginManager $migrationPluginManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    // Create article content type.
    $values = [
      'type' => self::$bundle,
      'name' => 'Page',
    ];
    $node_type = NodeType::create($values);
    $node_type->save();

    $this->installEntitySchema('node');
    $this->installEntitySchema('taxonomy_term');
    $this->installEntitySchema('taxonomy_vocabulary');
    $this->installEntitySchema('user');
    $this->installSchema('user', 'users_data');
    $this->installConfig(self::$modules);

    // Create a vocabulary.
    $vocabulary = Vocabulary::create([
      'name' => self::$vocabulary,
      'description' => self::$vocabulary,
      'vid' => self::$vocabulary,
      'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
    ]);
    $vocabulary->save();

    // Create a field.
    $this->createEntityReferenceField(
      'node',
      self::$bundle,
      self::$fieldName,
      'Term reference',
      'taxonomy_term',
      'default',
      ['target_bundles' => [self::$vocabulary]],
      FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED
    );

    // Create a non-reference field.
    FieldStorageConfig::create([
      'field_name' => 'field_integer',
      'type' => 'integer',
      'entity_type' => 'node',
    ])->save();
    FieldConfig::create([
      'field_name' => 'field_integer',
      'entity_type' => 'node',
      'bundle' => self::$bundle,
    ])->save();

    $this->migrationPluginManager = \Drupal::service('plugin.manager.migration');
  }

  /**
   * Tests generating an entity.
   *
   * @dataProvider transformDataProvider
   *
   * @covers ::transform
   */
  public function testTransform(array $definition, array $expected, array $preSeed = []): void {
    // Pre seed some test data.
    foreach ($preSeed as $storageName => $values) {
      // If the first element of $values is a non-empty array, create multiple
      // entities. Otherwise, create just one entity.
      if (isset($values[0])) {
        foreach ($values as $itemValues) {
          $this->createTestData($storageName, $itemValues);
        }
      }
      else {
        $this->createTestData($storageName, $values);
      }
    }

    /** @var \Drupal\migrate\Plugin\Migration $migration */
    $migration = $this->migrationPluginManager->createStubMigration($definition);
    $reflector = new \ReflectionObject($migration->getDestinationPlugin());
    $attribute = $reflector->getProperty('storage');
    $attribute->setAccessible(TRUE);
    /** @var \Drupal\Core\Entity\EntityStorageBase $storage */
    $storage = $attribute->getValue($migration->getDestinationPlugin());
    $migrationExecutable = (new MigrateExecutable($migration, $this));
    $migrationExecutable->import();

    foreach ($expected as $row) {
      $entity = $storage->load($row['id']);
      $properties = array_diff_key($row, array_flip(['id']));
      foreach ($properties as $property => $value) {
        if (is_array($value)) {
          if (empty($value)) {
            $this->assertEmpty($entity->{$property}->getValue(), "Expected value is 'unset' but field $property is set.");
          }
          else {
            // Check if we're testing multiple values in one field. If so, loop
            // through them one-by-one and check that they're present in the
            // $entity.
            if (isset($value[0])) {
              foreach ($value as $valueID => $valueToCheck) {
                foreach ($valueToCheck as $key => $expectedValue) {
                  if (empty($expectedValue)) {
                    if (!$entity->{$property}->isEmpty()) {
                      $this->assertTrue($entity->{$property}[0]->entity->{$key}->isEmpty(), "Expected value is empty but field $property.$key is not empty.");
                    }
                    else {
                      $this->assertTrue($entity->{$property}->isEmpty(), "Expected value is empty but field $property is not empty.");
                    }
                  }
                  elseif ($entity->{$property}->getValue()) {
                    $this->assertEquals($expectedValue, $entity->get($property)->offsetGet($valueID)->entity->{$key}->value);
                  }
                  else {
                    $this->fail("Expected value: $expectedValue does not exist in $property.");
                  }
                }
              }
            }
            // If we get to this point, we're only checking a
            // single field value.
            else {
              foreach ($value as $key => $expectedValue) {
                if (empty($expectedValue)) {
                  if (!$entity->{$property}->isEmpty()) {
                    $this->assertTrue($entity->{$property}[0]->entity->{$key}->isEmpty(), "Expected value is empty but field $property.$key is not empty.");
                  }
                  else {
                    $this->assertTrue($entity->{$property}->isEmpty(), "BINBAZ Expected value is empty but field $property is not empty.");
                  }
                }
                elseif ($entity->{$property}->getValue()) {
                  $referenced_entity = $entity->{$property}[0]->entity;
                  $result_value = $referenced_entity instanceof ConfigEntityInterface ? $referenced_entity->get($key) : $referenced_entity->get($key)->value;
                  $this->assertEquals($expectedValue, $result_value);
                }
                else {
                  $this->fail("Expected value: $expectedValue does not exist in $property.");
                }
              }
            }
          }
        }
        else {
          $this->assertNotEmpty($entity, 'Entity with label ' . $row[$property] . ' is empty');
          $this->assertEquals($row[$property], $entity->label());
        }
      }
    }
  }

  /**
   * Test lookup without a reference field.
   */
  public function testNonReferenceField(): void {
    $values = [
      'name' => 'Apples',
      'vid' => self::$vocabulary,
      'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
    ];
    $this->createTestData('taxonomy_term', $values);

    // Not enough context is provided for a non reference field, so error out.
    $definition = [
      'source' => [
        'plugin' => 'embedded_data',
        'data_rows' => [
          [
            'id' => 1,
            'title' => 'content item 1',
            'term' => 'Apples',
          ],
        ],
        'ids' => [
          'id' => ['type' => 'integer'],
        ],
      ],
      'process' => [
        'id' => 'id',
        'type' => [
          'plugin' => 'default_value',
          'default_value' => self::$bundle,
        ],
        'title' => 'title',
        'field_integer' => [
          'plugin' => 'entity_generate',
          'source' => 'term',
        ],
      ],
      'destination' => [
        'plugin' => 'entity:node',
      ],
    ];
    /** @var \Drupal\migrate\Plugin\Migration $migration */
    $migration = $this->migrationPluginManager->createStubMigration($definition);
    $migrationExecutable = (new MigrateExecutable($migration, $this));
    $migrationExecutable->import();
    $this->assertStringEndsWith('Destination field type integer is not a recognized reference type.', $migration->getIdMap()->getMessages()->fetch()->message);
    $this->assertSame(1, $migration->getIdMap()->messageCount());

    // Enough context is provided so this should work.
    $definition = [
      'source' => [
        'plugin' => 'embedded_data',
        'data_rows' => [
          [
            'id' => 1,
            'title' => 'content item 1',
            'term' => 'Apples',
          ],
        ],
        'ids' => [
          'id' => ['type' => 'integer'],
        ],
      ],
      'process' => [
        'id' => 'id',
        'type' => [
          'plugin' => 'default_value',
          'default_value' => self::$bundle,
        ],
        'title' => 'title',
        'field_integer' => [
          'plugin' => 'entity_generate',
          'source' => 'term',
          'value_key' => 'name',
          'bundle_key' => 'vid',
          'bundle' => self::$vocabulary,
          'entity_type' => 'taxonomy_term',
        ],
      ],
      'destination' => [
        'plugin' => 'entity:node',
      ],
    ];
    /** @var \Drupal\migrate\Plugin\Migration $migration */
    $migration = $this->migrationPluginManager->createStubMigration($definition);
    $migrationExecutable = (new MigrateExecutable($migration, $this));
    $migrationExecutable->import();
    $this->assertEmpty($migration->getIdMap()->messageCount());
    $term = Term::load(1);
    $this->assertEquals('Apples', $term->label());
  }

  /**
   * Provides multiple migration definitions for "transform" test.
   */
  public static function transformDataProvider(): array {
    return [
      'no arguments' => [
        'definition' => [
          'source' => [
            'plugin' => 'embedded_data',
            'data_rows' => [
              [
                'id' => 1,
                'title' => 'content item 1',
                'term' => 'Apples',
              ],
              [
                'id' => 2,
                'title' => 'content item 2',
                'term' => 'Bananas',
              ],
              [
                'id' => 3,
                'title' => 'content item 3',
                'term' => 'Grapes',
              ],
            ],
            'ids' => [
              'id' => ['type' => 'integer'],
            ],
          ],
          'process' => [
            'id' => 'id',
            'type' => [
              'plugin' => 'default_value',
              'default_value' => self::$bundle,
            ],
            'title' => 'title',
            self::$fieldName => [
              'plugin' => 'entity_generate',
              'source' => 'term',
            ],
          ],
          'destination' => [
            'plugin' => 'entity:node',
          ],
        ],
        'expected' => [
          'row 1' => [
            'id' => 1,
            'title' => 'content item 1',
            self::$fieldName => [
              'tid' => 2,
              'name' => 'Apples',
            ],
          ],
          'row 2' => [
            'id' => 2,
            'title' => 'content item 2',
            self::$fieldName => [
              'tid' => 3,
              'name' => 'Bananas',
            ],
          ],
          'row 3' => [
            'id' => 3,
            'title' => 'content item 3',
            self::$fieldName => [
              'tid' => 1,
              'name' => 'Grapes',
            ],
          ],
        ],
        'preSeed' => [
          'taxonomy_term' => [
            'name' => 'Grapes',
            'vid' => self::$vocabulary,
            'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
          ],
        ],
      ],
      'no arguments_lookup_only' => [
        'definition' => [
          'source' => [
            'plugin' => 'embedded_data',
            'data_rows' => [
              [
                'id' => 1,
                'title' => 'content item 1',
                'term' => 'Apples',
              ],
              [
                'id' => 2,
                'title' => 'content item 2',
                'term' => 'Bananas',
              ],
              [
                'id' => 3,
                'title' => 'content item 3',
                'term' => 'Grapes',
              ],
            ],
            'ids' => [
              'id' => ['type' => 'integer'],
            ],
          ],
          'process' => [
            'id' => 'id',
            'type' => [
              'plugin' => 'default_value',
              'default_value' => self::$bundle,
            ],
            'title' => 'title',
            self::$fieldName => [
              'plugin' => 'entity_lookup',
              'source' => 'term',
            ],
          ],
          'destination' => [
            'plugin' => 'entity:node',
          ],
        ],
        'expected' => [
          'row 1' => [
            'id' => 1,
            'title' => 'content item 1',
            self::$fieldName => [
              'tid' => NULL,
              'name' => NULL,
            ],
          ],
          'row 2' => [
            'id' => 2,
            'title' => 'content item 2',
            self::$fieldName => [
              'tid' => NULL,
              'name' => NULL,
            ],
          ],
          'row 3' => [
            'id' => 3,
            'title' => 'content item 3',
            self::$fieldName => [
              'tid' => 1,
              'name' => 'Grapes',
            ],
          ],
        ],
        'preSeed' => [
          'taxonomy_term' => [
            'name' => 'Grapes',
            'vid' => self::$vocabulary,
            'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
          ],
        ],
      ],
      'provide values' => [
        'definition' => [
          'source' => [
            'plugin' => 'embedded_data',
            'data_rows' => [
              [
                'id' => 1,
                'title' => 'content item 1',
                'term' => 'Apples',
              ],
              [
                'id' => 2,
                'title' => 'content item 2',
                'term' => 'Bananas',
              ],
              [
                'id' => 3,
                'title' => 'content item 3',
                'term' => 'Grapes',
              ],
            ],
            'ids' => [
              'id' => ['type' => 'integer'],
            ],
          ],
          'process' => [
            'id' => 'id',
            'type' => [
              'plugin' => 'default_value',
              'default_value' => self::$bundle,
            ],
            'title' => 'title',
            'term_upper' => [
              'plugin' => 'callback',
              'source' => 'term',
              'callable' => 'strtoupper',
            ],
            self::$fieldName => [
              'plugin' => 'entity_generate',
              'source' => 'term',
              'values' => [
                'description' => '@term_upper',
              ],
            ],
          ],
          'destination' => [
            'plugin' => 'entity:node',
          ],
        ],
        'expected' => [
          'row 1' => [
            'id' => 1,
            'title' => 'content item 1',
            self::$fieldName => [
              'tid' => 2,
              'name' => 'Apples',
              'description' => 'APPLES',
            ],
          ],
          'row 2' => [
            'id' => 2,
            'title' => 'content item 2',
            self::$fieldName => [
              'tid' => 3,
              'name' => 'Bananas',
              'description' => 'BANANAS',
            ],
          ],
          'row 3' => [
            'id' => 3,
            'title' => 'content item 3',
            self::$fieldName => [
              'tid' => 1,
              'name' => 'Grapes',
              'description' => NULL,
            ],
          ],
        ],
        'preSeed' => [
          'taxonomy_term' => [
            'name' => 'Grapes',
            'vid' => self::$vocabulary,
            'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
          ],
        ],
      ],
      'provide multiple values' => [
        'definition' => [
          'source' => [
            'plugin' => 'embedded_data',
            'data_rows' => [
              [
                'id' => 1,
                'title' => 'content item 1',
                'term' => 'Apples',
              ],
              [
                'id' => 2,
                'title' => 'content item 2',
                'term' => 'Bananas',
              ],
              [
                'id' => 3,
                'title' => 'content item 3',
                'term' => 'Grapes',
              ],
            ],
            'ids' => [
              'id' => ['type' => 'integer'],
            ],
            'constants' => [
              'foo' => 'bar',
            ],
          ],
          'process' => [
            'id' => 'id',
            'type' => [
              'plugin' => 'default_value',
              'default_value' => self::$bundle,
            ],
            'title' => 'title',
            'term_upper' => [
              'plugin' => 'callback',
              'source' => 'term',
              'callable' => 'strtoupper',
            ],
            self::$fieldName => [
              'plugin' => 'entity_generate',
              'source' => 'term',
              'values' => [
                'name' => '@term_upper',
                'description' => 'constants/foo',
              ],
            ],
          ],
          'destination' => [
            'plugin' => 'entity:node',
          ],
        ],
        'expected' => [
          'row 1' => [
            'id' => 1,
            'title' => 'content item 1',
            self::$fieldName => [
              'tid' => 2,
              'name' => 'APPLES',
              'description' => 'bar',
            ],
          ],
          'row 2' => [
            'id' => 2,
            'title' => 'content item 2',
            self::$fieldName => [
              'tid' => 3,
              'name' => 'BANANAS',
              'description' => 'bar',
            ],
          ],
          'row 3' => [
            'id' => 3,
            'title' => 'content item 3',
            self::$fieldName => [
              'tid' => 1,
              'name' => 'Grapes',
              'description' => NULL,
            ],
          ],
        ],
        'preSeed' => [
          'taxonomy_term' => [
            'name' => 'Grapes',
            'vid' => self::$vocabulary,
            'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
          ],
        ],
      ],
      'lookup single existing term returns correct term' => [
        'definition' => [
          'source' => [
            'plugin' => 'embedded_data',
            'data_rows' => [
              [
                'id' => 1,
                'title' => 'content item 1',
                'term' => 'Grapes',
              ],
            ],
            'ids' => [
              'id' => ['type' => 'integer'],
            ],
          ],
          'process' => [
            'id' => 'id',
            'type' => [
              'plugin' => 'default_value',
              'default_value' => self::$bundle,
            ],
            'title' => 'title',
            self::$fieldName => [
              'plugin' => 'entity_lookup',
              'source' => 'term',
            ],
          ],
          'destination' => [
            'plugin' => 'entity:node',
          ],
        ],
        'expected' => [
          'row 1' => [
            'id' => 1,
            'title' => 'content item 1',
            self::$fieldName => [
              'tid' => 1,
              'name' => 'Grapes',
            ],
          ],
        ],
        'preSeed' => [
          'taxonomy_term' => [
            'name' => 'Grapes',
            'vid' => self::$vocabulary,
            'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
          ],
        ],
      ],
      'lookup single missing term returns null value' => [
        'definition' => [
          'source' => [
            'plugin' => 'embedded_data',
            'data_rows' => [
              [
                'id' => 1,
                'title' => 'content item 1',
                'term' => 'Apple',
              ],
            ],
            'ids' => [
              'id' => ['type' => 'integer'],
            ],
          ],
          'process' => [
            'id' => 'id',
            'type' => [
              'plugin' => 'default_value',
              'default_value' => self::$bundle,
            ],
            'title' => 'title',
            self::$fieldName => [
              'plugin' => 'entity_lookup',
              'source' => 'term',
            ],
          ],
          'destination' => [
            'plugin' => 'entity:node',
          ],
        ],
        'expected' => [
          'row 1' => [
            'id' => 1,
            'title' => 'content item 1',
            self::$fieldName => [],
          ],
        ],
        'preSeed' => [
          'taxonomy_term' => [
            'name' => 'Grapes',
            'vid' => self::$vocabulary,
            'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
          ],
        ],
      ],
      'lookup multiple existing terms returns correct terms' => [
        'definition' => [
          'source' => [
            'plugin' => 'embedded_data',
            'data_rows' => [
              [
                'id' => 1,
                'title' => 'content item 1',
                'term' => [
                  'Grapes',
                  'Apples',
                ],
              ],
            ],
            'ids' => [
              'id' => ['type' => 'integer'],
            ],
          ],
          'process' => [
            'id' => 'id',
            'title' => 'title',
            'type' => [
              'plugin' => 'default_value',
              'default_value' => self::$bundle,
            ],
            self::$fieldName => [
              'plugin' => 'entity_lookup',
              'source' => 'term',
            ],
          ],
          'destination' => [
            'plugin' => 'entity:node',
          ],
        ],
        'expected' => [
          'row 1' => [
            'id' => 1,
            'title' => 'content item 1',
            self::$fieldName => [
              [
                'tid' => 1,
                'name' => 'Grapes',
              ],
              [
                'tid' => 2,
                'name' => 'Apples',
              ],
            ],
          ],
        ],
        'preSeed' => [
          'taxonomy_term' => [
            [
              'name' => 'Grapes',
              'vid' => self::$vocabulary,
              'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
            ],
            [
              'name' => 'Apples',
              'vid' => self::$vocabulary,
              'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
            ],
          ],
        ],
      ],
      'lookup multiple mixed terms returns correct terms' => [
        'definition' => [
          'source' => [
            'plugin' => 'embedded_data',
            'data_rows' => [
              [
                'id' => 1,
                'title' => 'content item 1',
                'term' => [
                  'Grapes',
                  'Pears',
                ],
              ],
            ],
            'ids' => [
              'id' => ['type' => 'integer'],
            ],
          ],
          'process' => [
            'id' => 'id',
            'title' => 'title',
            'type' => [
              'plugin' => 'default_value',
              'default_value' => self::$bundle,
            ],
            self::$fieldName => [
              'plugin' => 'entity_lookup',
              'source' => 'term',
            ],
          ],
          'destination' => [
            'plugin' => 'entity:node',
          ],
        ],
        'expected' => [
          'row 1' => [
            'id' => '1',
            'title' => 'content item 1',
            self::$fieldName => [
              [
                'tid' => 1,
                'name' => 'Grapes',
              ],
            ],
          ],
        ],
        'preSeed' => [
          'taxonomy_term' => [
            [
              'name' => 'Grapes',
              'vid' => self::$vocabulary,
              'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
            ],
            [
              'name' => 'Apples',
              'vid' => self::$vocabulary,
              'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
            ],
          ],
        ],
      ],
      'lookup with empty term value returns no terms' => [
        'definition' => [
          'source' => [
            'plugin' => 'embedded_data',
            'data_rows' => [
              [
                'id' => 1,
                'title' => 'content item 1',
                'term' => [],
              ],
            ],
            'ids' => [
              'id' => ['type' => 'integer'],
            ],
          ],
          'process' => [
            'id' => 'id',
            'title' => 'title',
            'type' => [
              'plugin' => 'default_value',
              'default_value' => self::$bundle,
            ],
            self::$fieldName => [
              'plugin' => 'entity_lookup',
              'source' => 'term',
            ],
          ],
          'destination' => [
            'plugin' => 'entity:node',
          ],
        ],
        'expected' => [
          'row 1' => [
            'id' => 1,
            'title' => 'content item 1',
            self::$fieldName => [],
          ],
        ],
        'preSeed' => [
          'taxonomy_term' => [
            'name' => 'Grapes',
            'vid' => self::$vocabulary,
            'langcode' => LanguageInterface::LANGCODE_NOT_SPECIFIED,
          ],
        ],
      ],
      'lookup config entity' => [
        'definition' => [
          'source' => [
            'plugin' => 'embedded_data',
            'data_rows' => [
              [
                'id' => 1,
                'name' => 'user 1',
                'mail' => 'user1@user1.com',
                'roles' => ['role_1'],
              ],
            ],
            'ids' => [
              'id' => ['type' => 'integer'],
            ],
          ],
          'process' => [
            'id' => 'id',
            'name' => 'name',
            'roles' => [
              'plugin' => 'entity_lookup',
              'entity_type' => 'user_role',
              'value_key' => 'id',
              'source' => 'roles',
            ],
          ],
          'destination' => [
            'plugin' => 'entity:user',
          ],
        ],
        'expected' => [
          'row 1' => [
            'id' => 1,
            'name' => 'user 1',
            'roles' => [
              'id' => 'role_1',
              'label' => 'Role 1',
            ],
          ],
        ],
        'preSeed' => [
          'user_role' => [
            'id' => 'role_1',
            'label' => 'Role 1',
          ],
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function display($message, $type = 'status'): void {
    $this->assertTrue($type == 'status', $message);
  }

  /**
   * Create pre-seed test data.
   *
   * @param string $storageName
   *   The storage manager to create.
   * @param array $values
   *   The values to use when creating the entity.
   *
   * @return string|int
   *   The entity identifier.
   */
  private function createTestData($storageName, array $values) {
    /** @var \Drupal\Core\Entity\ContentEntityStorageInterface $storage */
    $storage = $this->container
      ->get('entity_type.manager')
      ->getStorage($storageName);
    $entity = $storage->create($values);
    $entity->save();
    return $entity->id();
  }

}
