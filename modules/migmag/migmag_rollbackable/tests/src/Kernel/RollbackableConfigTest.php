<?php

namespace Drupal\Tests\migmag_rollbackable\Kernel;

use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\migrate\Plugin\MigrationInterface;

/**
 * Tests the rollbackability of the 'migmag_rollbackable_config' destination.
 *
 * @coversDefaultClass \Drupal\migmag_rollbackable\Plugin\migrate\destination\RollbackableConfig
 *
 * @group migmag_rollbackable
 */
class RollbackableConfigTest extends RollbackableDestinationTestBase {

  /**
   * Base for the test migrations.
   *
   * @const array
   */
  const CONFIG_MIGRATION_BASE = [
    'source' => [
      'plugin' => 'embedded_data',
      'data_rows' => [
        [
          'id' => 'base',
          'features_name' => FALSE,
          'features_slogan' => FALSE,
        ],
      ],
      'ids' => [
        'id' => ['type' => 'string'],
      ],
    ],
    'process' => [
      'features/name' => 'features_name',
      'features/slogan' => 'features_slogan',
    ],
    'destination' => [
      'plugin' => 'migmag_rollbackable_config',
      'config_name' => 'system.theme.global',
    ],
  ];

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'language',
  ];

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();

    ConfigurableLanguage::createFromLangcode('is')->save();
    ConfigurableLanguage::createFromLangcode('hu')->save();
  }

  /**
   * Tests the rollbackability of 'rollbackable_config' destination.
   *
   * @dataProvider providerTestMigrationRollback
   */
  public function testConfigRollback(bool $with_preexisting_config) {
    $language_manager = $this->container->get('language_manager');
    assert($language_manager instanceof LanguageManagerInterface);
    if ($with_preexisting_config) {
      $this->installConfig(['system']);
      $this->assertFalse($this->config('system.theme.global')->isNew());

      $hu_language_override = $language_manager->getLanguageConfigOverride('hu', 'system.theme.global');
      $hu_language_override
        ->set('features', ['name' => TRUE, 'slogan' => FALSE])
        ->set('langcode', 'hu')
        ->save();
      $this->assertFalse($language_manager->getLanguageConfigOverride('hu', 'system.theme.global')->isNew());
    }
    else {
      $global_theme_settings = $this->config('system.theme.global');
      $this->assertTrue($global_theme_settings->isNew());
      $hu_translation = $language_manager->getLanguageConfigOverride('hu', 'system.theme.global');
      $this->assertTrue($hu_translation->isNew());
    }

    $global_theme_settings_before_migration = $this->config('system.theme.global')->getRawData();

    // Execute the base migration.
    $base_executable = $this->getMigrateExecutable($this->baseMigration());
    $this->startCollectingMessages();
    $base_executable->import();
    $this->assertNoErrors();

    $expected_global_theme_settings_after_base_migration = $global_theme_settings_before_migration;
    $expected_global_theme_settings_after_base_migration['features']['name'] = FALSE;
    $expected_global_theme_settings_after_base_migration['features']['slogan'] = FALSE;
    $this->assertEquals(
      $expected_global_theme_settings_after_base_migration,
      $this->config('system.theme.global')->getRawData()
    );

    // Execute an another migration which updates some of the previous targets.
    $subsequent_executable = $this->getMigrateExecutable($this->subsequentMigration());
    $this->startCollectingMessages();
    $subsequent_executable->import();
    $this->assertNoErrors();

    $expected_global_theme_settings_after_subsequent_migration = $expected_global_theme_settings_after_base_migration;
    $expected_global_theme_settings_after_subsequent_migration['features']['name'] = FALSE;
    $expected_global_theme_settings_after_subsequent_migration['features']['slogan'] = TRUE;

    $this->assertEquals(
      $expected_global_theme_settings_after_subsequent_migration,
      $this->config('system.theme.global')->getRawData()
    );

    // Make sure that the language config override data equals to the expected
    // data before executing any translation migration.
    $hu_translation_before_migration = $language_manager->getLanguageConfigOverride('hu', 'system.theme.global')->get();
    $this->assertEquals(
      $hu_translation_before_migration,
      $language_manager->getLanguageConfigOverride('hu', 'system.theme.global')->get()
    );

    // Execute the base translation migration.
    $translation_base_executable = $this->getMigrateExecutable($this->baseTranslationMigration());
    $this->startCollectingMessages();
    $translation_base_executable->import();
    $this->assertNoErrors();

    $expected_base_override_hu = [
      'features' => [
        'name' => TRUE,
        'slogan' => TRUE,
      ],
      'langcode' => 'hu',
    ];
    $this->assertEquals(
      $expected_base_override_hu,
      $language_manager->getLanguageConfigOverride('hu', 'system.theme.global')->get()
    );
    $this->assertEquals(
      [],
      $language_manager->getLanguageConfigOverride('is', 'system.theme.global')->get()
    );

    // Execute an another translation migration which updates the previous
    // target's language override.
    $translation_subsequent_executable = $this->getMigrateExecutable($this->subsequentTranslationMigration());
    $this->startCollectingMessages();
    $translation_subsequent_executable->import();
    $this->assertNoErrors();

    $this->assertEquals(
      [
        'features' => [
          'name' => FALSE,
          'slogan' => FALSE,
        ],
        'langcode' => 'hu',
      ],
      $language_manager->getLanguageConfigOverride('hu', 'system.theme.global')->get()
    );
    $this->assertEquals(
      [
        'features' => [
          'name' => TRUE,
          'slogan' => TRUE,
        ],
        'langcode' => 'is',
      ],
      $language_manager->getLanguageConfigOverride('is', 'system.theme.global')->get()
    );

    // Roll back the last translation migration.
    $translation_subsequent_executable->rollback();
    $this->assertEquals(
      $expected_base_override_hu,
      $language_manager->getLanguageConfigOverride('hu', 'system.theme.global')->get()
    );
    $this->assertEquals(
      [],
      $language_manager->getLanguageConfigOverride('is', 'system.theme.global')->get()
    );

    // Roll back the "base" translation migration.
    $translation_base_executable->rollback();
    $this->assertEquals(
      $hu_translation_before_migration,
      $language_manager->getLanguageConfigOverride('hu', 'system.theme.global')->get()
    );

    // Roll back the subsequent default migration.
    $subsequent_executable->rollback();
    $this->assertEquals(
      $expected_global_theme_settings_after_base_migration,
      $this->config('system.theme.global')->get()
    );

    // Roll back the base migration.
    $base_executable->rollback();

    $this->assertEquals(
      $global_theme_settings_before_migration,
      $this->config('system.theme.global')->get()
    );

    if ($with_preexisting_config) {
      $this->assertFalse($this->config('system.theme.global')->isNew());
    }
    else {
      $this->assertTrue($this->config('system.theme.global')->isNew());
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function baseMigration(): MigrationInterface {
    $definition = ['id' => 'config_base'] + self::CONFIG_MIGRATION_BASE;
    return $this->getMigrationPluginInstance($definition);
  }

  /**
   * {@inheritdoc}
   */
  protected function subsequentMigration(): MigrationInterface {
    $definition = self::CONFIG_MIGRATION_BASE;
    $definition['id'] = 'config_subsequent';
    $definition['source']['data_rows'] = [
      [
        'id' => 'subsequent',
        'features_name' => FALSE,
        'features_slogan' => TRUE,
      ],
    ];

    return $this->getMigrationPluginInstance($definition);
  }

  /**
   * {@inheritdoc}
   */
  protected function baseTranslationMigration(): ?MigrationInterface {
    $definition = self::CONFIG_MIGRATION_BASE;
    $definition['id'] = 'config_translation_base';
    $definition['source']['data_rows'] = [
      [
        'id' => 'base_translation_hu',
        'features_name' => TRUE,
        'features_slogan' => TRUE,
        'langcode' => 'hu',
      ],
    ];
    $definition['source']['ids']['langcode'] = ['type' => 'string'];
    $definition['process']['langcode'] = 'langcode';
    $definition['destination']['translations'] = TRUE;

    return $this->getMigrationPluginInstance($definition);
  }

  /**
   * {@inheritdoc}
   */
  protected function subsequentTranslationMigration(): ?MigrationInterface {
    $definition = self::CONFIG_MIGRATION_BASE;
    $definition['id'] = 'config_translation_subsequent';
    $definition['source']['data_rows'] = [
      [
        'id' => 'subsequent_translation_hu',
        'features_name' => FALSE,
        'features_slogan' => FALSE,
        'langcode' => 'hu',
      ],
      [
        'id' => 'subsequent_translation_is',
        'features_name' => TRUE,
        'features_slogan' => TRUE,
        'langcode' => 'is',
      ],
    ];
    $definition['source']['ids']['langcode'] = ['type' => 'string'];
    $definition['process']['langcode'] = 'langcode';
    $definition['destination']['translations'] = TRUE;

    return $this->getMigrationPluginInstance($definition);
  }

}
