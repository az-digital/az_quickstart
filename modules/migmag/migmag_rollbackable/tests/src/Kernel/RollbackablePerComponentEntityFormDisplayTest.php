<?php

namespace Drupal\Tests\migmag_rollbackable\Kernel;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate\Plugin\MigrationInterface;

/**
 * Tests the rollbackability of entity form display components.
 *
 * @coversDefaultClass \Drupal\migmag_rollbackable\Plugin\migrate\destination\RollbackablePerComponentEntityFormDisplay
 *
 * @group migmag_rollbackable
 */
class RollbackablePerComponentEntityFormDisplayTest extends RollbackableDisplayComponentTestBase {

  /**
   * Base for the test migrations.
   *
   * @const array
   */
  const FIELD_WIDGET_MIGRATION_BASE = [
    'source' => [
      'plugin' => 'embedded_data',
      'data_rows' => [
        [
          'entity_type' => 'user',
          'bundle' => 'user',
          'field_name' => 'field_1',
          'hidden' => FALSE,
          'options' => [
            'weight' => 32,
            'type' => 'options_select',
            'settings' => [],
            'third_party_settings' => [],
          ],
        ],
        [
          'entity_type' => 'user',
          'bundle' => 'user',
          'field_name' => 'field_2',
          'hidden' => FALSE,
          'options' => [
            'weight' => 2,
            'type' => 'entity_reference_autocomplete',
            'settings' => [
              'match_operator' => 'CONTAINS',
              'match_limit' => 4,
              'size' => 100,
              'placeholder' => '',
            ],
            'third_party_settings' => [],
          ],
        ],
      ],
      'ids' => [
        'entity_type' => ['type' => 'string'],
        'bundle' => ['type' => 'string'],
        'field_name' => ['type' => 'string'],
      ],
    ],
    'process' => [
      'entity_type' => 'entity_type',
      'bundle' => 'bundle',
      'form_mode' => [
        'plugin' => 'default_value',
        'default_value' => 'default',
      ],
      'field_name' => 'field_name',
      'options' => 'options',
    ],
    'destination' => [
      'plugin' => 'migmag_rollbackable_component_entity_form_display',
    ],
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    $test_user_fields = [
      'field_1' => 'entity_reference',
      'field_2' => 'entity_reference',
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
  public function testFormDisplayComponentRollback(bool $with_preexisting_display) {
    $display = $this->getDisplayEntity('form', 'user', 'user', 'default');

    // Verify whether default form display exists.
    if ($with_preexisting_display) {
      $display->setStatus(TRUE);
      $display->save();
    }

    $display_data_before_migration = $this->getCleanedFormDisplayData('user', 'user', 'default');

    // Import...
    $base_executable = new MigrateExecutable($this->baseMigration(), $this);
    $this->startCollectingMessages();
    $base_executable->import();
    $this->assertNoErrors();

    // ...and validate the results.
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
        'field_1' => [
          'weight' => 32,
          'type' => 'options_select',
          'settings' => [],
          'third_party_settings' => [],
          'region' => 'content',
        ],
        'field_2' => [
          'weight' => 2,
          'type' => 'entity_reference_autocomplete',
          'settings' => [
            'match_operator' => 'CONTAINS',
            'match_limit' => 4,
            'size' => 100,
            'placeholder' => '',
          ],
          'third_party_settings' => [],
          'region' => 'content',
        ],
        'account' => [
          'weight' => -10,
          'settings' => [],
          'third_party_settings' => [],
          'region' => 'content',
        ],
        'language' => [
          'weight' => 0,
          'settings' => [],
          'third_party_settings' => [],
          'region' => 'content',
        ],
      ],
      'hidden' => [],
    ];

    $this->assertEquals(
      $expected_display_after_base_migration,
      $this->getCleanedFormDisplayData('user', 'user', 'default')
    );

    // Execute the subsequent migration.
    $subsequent_executable = new MigrateExecutable($this->subsequentMigration(), $this);
    $this->startCollectingMessages();
    $subsequent_executable->import();
    $this->assertNoErrors();

    $expected_display_after_subsequent_migration = $expected_display_after_base_migration;
    $expected_display_after_subsequent_migration['content']['field_2'] = [
      'weight' => 55,
      'type' => 'entity_reference_autocomplete_tags',
      'settings' => [
        'match_operator' => 'STARTS_WITH',
        'match_limit' => 5,
        'size' => 60,
        'placeholder' => 'a placeholder',
      ],
      'third_party_settings' => [],
      'region' => 'content',
    ];

    $this->assertEquals(
      $expected_display_after_subsequent_migration,
      $this->getCleanedFormDisplayData('user', 'user', 'default')
    );

    // Roll back subsequent migration.
    $this->stopCollectingMessages();
    $subsequent_executable->rollback();

    $this->assertEquals(
      $expected_display_after_base_migration,
      $this->getCleanedFormDisplayData('user', 'user', 'default')
    );

    // Roll back the base migration.
    $base_executable->rollback();

    $this->assertEquals(
      $display_data_before_migration,
      $this->getCleanedFormDisplayData('user', 'user', 'default')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function baseMigration(): MigrationInterface {
    $definition = ['id' => 'fwm_base'] + self::FIELD_WIDGET_MIGRATION_BASE;
    return $this->getMigrationPluginInstance($definition);
  }

  /**
   * {@inheritdoc}
   */
  protected function subsequentMigration(): MigrationInterface {
    $definition = self::FIELD_WIDGET_MIGRATION_BASE;
    $definition['id'] = 'fwm_subsequent';
    $definition['source']['data_rows'] = [
      [
        'entity_type' => 'user',
        'bundle' => 'user',
        'field_name' => 'field_2',
        'hidden' => FALSE,
        'options' => [
          'weight' => 55,
          'type' => 'entity_reference_autocomplete_tags',
          'settings' => [
            'match_operator' => 'STARTS_WITH',
            'match_limit' => 5,
            'size' => 60,
            'placeholder' => 'a placeholder',
          ],
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
    // Entity form display component destination isn't used for translation
    // migration in Drupal core.
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  protected function subsequentTranslationMigration(): ?MigrationInterface {
    // Entity form display component destination isn't used for translation
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
  protected function getCleanedFormDisplayData(string $entity_type, string $bundle, string $view_mode) {
    $display = $this->getDisplayEntity('form', $entity_type, $bundle, $view_mode);
    $ignored_props = ['uuid'];
    return array_diff_key($display->toArray(), array_combine($ignored_props, $ignored_props));
  }

}
