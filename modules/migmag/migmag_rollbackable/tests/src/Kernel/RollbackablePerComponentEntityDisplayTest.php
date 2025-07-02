<?php

namespace Drupal\Tests\migmag_rollbackable\Kernel;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate\Plugin\MigrationInterface;

/**
 * Tests the rollbackability of entity view display components.
 *
 * @coversDefaultClass \Drupal\migmag_rollbackable\Plugin\migrate\destination\RollbackablePerComponentEntityDisplay
 *
 * @group migmag_rollbackable
 */
class RollbackablePerComponentEntityDisplayTest extends RollbackableDisplayComponentTestBase {

  /**
   * Base for the test migrations.
   *
   * @const array
   */
  const FIELD_FORMATTER_MIGRATION_BASE = [
    'idMap' => ['plugin' => 'smart_sql'],
    'source' => [
      'plugin' => 'embedded_data',
      'data_rows' => [
        [
          'entity_type' => 'user',
          'bundle' => 'user',
          'field_name' => 'field_1',
          'view_mode' => 'default',
          'options' => [
            'label' => 'hidden',
            'weight' => 32,
            'type' => 'number_integer',
            'settings' => [
              'thousand_separator' => '.',
              'prefix_suffix' => FALSE,
            ],
            'third_party_settings' => [],
          ],
        ],
        [
          'entity_type' => 'user',
          'bundle' => 'user',
          'field_name' => 'field_1',
          'view_mode' => 'full',
          'options' => [
            'label' => 'hidden',
            'weight' => 2,
            'type' => 'hidden',
            'settings' => [],
            'third_party_settings' => [],
          ],
        ],
        [
          'entity_type' => 'user',
          'bundle' => 'user',
          'field_name' => 'field_2',
          'view_mode' => 'full',
          'options' => [
            'label' => 'hidden',
            'weight' => 2,
            'type' => 'hidden',
            'settings' => [],
            'third_party_settings' => [],
          ],
        ],
        [
          'entity_type' => 'user',
          'bundle' => 'user',
          'field_name' => 'field_2',
          'view_mode' => 'default',
          'options' => [
            'label' => 'hidden',
            'weight' => 2,
            'type' => 'number_decimal',
            'settings' => [
              'thousand_separator' => "'",
              'decimal_separator' => ',',
              'scale' => 4,
              'prefix_suffix' => TRUE,
            ],
            'third_party_settings' => [],
          ],
        ],
      ],
      'ids' => [
        'entity_type' => ['type' => 'string'],
        'bundle' => ['type' => 'string'],
        'field_name' => ['type' => 'string'],
        'view_mode' => ['type' => 'string'],
      ],
    ],
    'process' => [
      'entity_type' => 'entity_type',
      'bundle' => 'bundle',
      'view_mode' => [
        'plugin' => 'static_map',
        'source' => 'view_mode',
        'bypass' => TRUE,
        'map' => [
          'full' => 'default',
        ],
      ],
      'field_name' => 'field_name',
      'options' => 'options',
      'hidden' => [
        'plugin' => 'static_map',
        'source' => '@options/type',
        'map' => [
          'hidden' => TRUE,
        ],
        'default_value' => FALSE,
      ],
    ],
    'destination' => [
      'plugin' => 'migmag_rollbackable_component_entity_display',
    ],
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    // cspell:disable-next-line
    $this->enableModules(['smart_sql_idmap']);

    $test_user_fields = [
      'field_1' => 'integer',
      'field_2' => 'decimal',
    ];

    foreach ($test_user_fields as $field_name => $field_type) {
      $storage = FieldStorageConfig::create([
        'field_name' => $field_name,
        'entity_type' => 'user',
        'type' => $field_type,
      ]);
      $storage->save();
      FieldConfig::create([
        'field_storage' => $storage,
        'bundle' => 'user',
      ])->save();
    }
  }

  /**
   * Tests the rollbackability of entity display component destination.
   *
   * @dataProvider providerTestMigrationRollback
   */
  public function testViewDisplayComponentRollback(bool $with_preexisting_display) {
    $display = $this->getDisplayEntity('view', 'user', 'user', 'default');

    // Verify whether default view display exists.
    if ($with_preexisting_display) {
      $display->setStatus(TRUE);
      $display->save();
    }

    $display_data_before_migration = $this->getCleanedViewDisplayData('user', 'user', 'default');

    // Import the base migration...
    $base_executable = new MigrateExecutable($this->baseMigration(), $this);
    $this->startCollectingMessages();
    $base_executable->import();
    $this->assertNoErrors();

    // ...and validate its results.
    $expected_display_after_base_migration = [
      'langcode' => 'en',
      'status' => TRUE,
      'dependencies' => [
        'config' => [
          'field.field.user.user.field_1',
          'field.field.user.user.field_2',
        ],
        'module' => [
          'user',
        ],
      ],
      'id' => 'user.user.default',
      'targetEntityType' => 'user',
      'bundle' => 'user',
      'mode' => 'default',
      'content' => [
        'field_2' => [
          'label' => 'hidden',
          'weight' => 2,
          'type' => 'number_decimal',
          'settings' => [
            'thousand_separator' => "'",
            'decimal_separator' => ',',
            'scale' => 4,
            'prefix_suffix' => TRUE,
          ],
          'third_party_settings' => [],
          'region' => 'content',
        ],
        'member_for' => [
          'weight' => 5,
          'settings' => [],
          'third_party_settings' => [],
          'region' => 'content',
        ],
      ],
      'hidden' => [
        'field_1' => TRUE,
      ],
    ];
    $this->assertEquals(
      $expected_display_after_base_migration,
      $this->getCleanedViewDisplayData('user', 'user', 'default')
    );

    // Execute the subsequent migration.
    $subsequent_executable = new MigrateExecutable($this->subsequentMigration(), $this);
    $this->startCollectingMessages();
    $subsequent_executable->import();
    $this->assertNoErrors();

    $expected_display_after_subsequent_migration = $expected_display_after_base_migration;
    $expected_display_after_subsequent_migration['content']['field_2'] = [
      'label' => 'hidden',
      'weight' => 55,
      'type' => 'number_unformatted',
      'settings' => [],
      'third_party_settings' => [],
      'region' => 'content',
    ];

    $this->assertEquals(
      $expected_display_after_subsequent_migration,
      $this->getCleanedViewDisplayData('user', 'user', 'default')
    );

    // Roll back subsequent migration.
    $this->stopCollectingMessages();
    $subsequent_executable->rollback();

    $this->assertEquals(
      $expected_display_after_base_migration,
      $this->getCleanedViewDisplayData('user', 'user', 'default')
    );

    // Roll back the base migration.
    $base_executable->rollback();

    $this->assertEquals(
      $display_data_before_migration,
      $this->getCleanedViewDisplayData('user', 'user', 'default')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function baseMigration(): MigrationInterface {
    $definition = ['id' => 'ffm_base'] + self::FIELD_FORMATTER_MIGRATION_BASE;
    return $this->getMigrationPluginInstance($definition);
  }

  /**
   * {@inheritdoc}
   */
  protected function subsequentMigration(): MigrationInterface {
    $definition = self::FIELD_FORMATTER_MIGRATION_BASE;
    $definition['id'] = 'ffm_subsequent';
    $definition['source']['data_rows'] = [
      [
        'entity_type' => 'user',
        'bundle' => 'user',
        'field_name' => 'field_2',
        'view_mode' => 'default',
        'options' => [
          'label' => 'hidden',
          'weight' => 55,
          'type' => 'number_unformatted',
          'settings' => [],
          'third_party_settings' => [],
        ],
      ],
    ];

    return $this->getMigrationPluginInstance($definition);
  }

  /**
   * {@inheritdoc}
   */
  protected function baseTranslationMigration(): ?MigrationInterface {
    // Entity view display component destination isn't used for translation
    // migration in Drupal core.
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  protected function subsequentTranslationMigration(): ?MigrationInterface {
    // Entity view display component destination isn't used for translation
    // migration in Drupal core.
    return NULL;
  }

  /**
   * Returns the array representation of an entity display, without the UUID.
   *
   * @param string $entity_type
   *   The entity type.
   * @param string $bundle
   *   The bundle.
   * @param string $view_mode
   *   The view mode.
   *
   * @return array
   *   The array representation of an entity display, without the UUID.
   */
  protected function getCleanedViewDisplayData(string $entity_type, string $bundle, string $view_mode) {
    $display = $this->getDisplayEntity('view', $entity_type, $bundle, $view_mode);
    $ignored_props = ['uuid'];
    return array_diff_key($display->toArray(), array_combine($ignored_props, $ignored_props));
  }

}
