<?php

namespace Drupal\Tests\migmag_rollbackable\Kernel;

use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\migrate\MigrateExecutable;
use Drupal\migrate\Plugin\MigrationInterface;

/**
 * Tests the 'migmag_rollbackable_default_langcode' destination plugin.
 *
 * @coversDefaultClass \Drupal\migmag_rollbackable\Plugin\migrate\destination\RollbackableDefaultLangcode
 *
 * @group migmag_rollbackable
 */
class RollbackableDefaultLangcodeTest extends RollbackableDestinationTestBase {

  /**
   * Base for the test migrations.
   *
   * @const array
   */
  const DEFAULT_LANGCODE_MIGRATION_BASE = [
    'source' => [
      'plugin' => 'embedded_data',
      'data_rows' => [
        [
          'id' => 'base',
          'default_langcode' => 'is',
        ],
      ],
      'ids' => [
        'id' => ['type' => 'string'],
      ],
    ],
    'process' => [
      'default_langcode' => 'default_langcode',
    ],
    'destination' => [
      'plugin' => 'migmag_rollbackable_default_langcode',
      'config_name' => 'system.site',
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
   * Tests the rollbackability of 'rollbackable_default_langcode' destination.
   *
   * @dataProvider providerTestMigrationRollback
   */
  public function testDefaultLangcodeRollback(bool $with_preexisting_config = FALSE) {
    if ($with_preexisting_config) {
      $this->installConfig(['system']);
      $this->assertFalse($this->config('system.site')->isNew());
      $this->assertEquals(
        'en',
        $this->config('system.site')->getOriginal('default_langcode', FALSE)
      );
    }
    else {
      $system_settings = $this->config('system.site');
      $this->assertTrue($system_settings->isNew());
    }

    $system_settings_before_migration = $this->config('system.site')->getRawData();

    // Execute the base migration.
    $base_executable = new MigrateExecutable($this->baseMigration(), $this);
    $this->startCollectingMessages();
    $base_executable->import();
    $this->assertNoErrors();

    $expected_system_settings_after_base_migration = $system_settings_before_migration;
    $expected_system_settings_after_base_migration['default_langcode'] = 'is';
    $this->assertEquals(
      $expected_system_settings_after_base_migration,
      $this->config('system.site')->getRawData()
    );

    // Execute a subsequent migration which updates some of the previous
    // targets.
    $subsequent_executable = new MigrateExecutable($this->subsequentMigration(), $this);
    $this->startCollectingMessages();
    $subsequent_executable->import();
    $this->assertNoErrors();

    $expected_system_settings_after_subsequent_migration = $expected_system_settings_after_base_migration;
    $expected_system_settings_after_subsequent_migration['default_langcode'] = 'hu';

    $this->assertEquals(
      $expected_system_settings_after_subsequent_migration,
      $this->config('system.site')->getRawData()
    );

    // Roll back the subsequent.
    $subsequent_executable->rollback();

    $this->assertEquals(
      $expected_system_settings_after_base_migration,
      $this->config('system.site')->getRawData()
    );

    // Roll back the base migration.
    $base_executable->rollback();

    $this->assertEquals(
      $system_settings_before_migration,
      $this->config('system.site')->getRawData()
    );

    if ($with_preexisting_config) {
      $this->assertFalse($this->config('system.site')->isNew());
    }
    else {
      $this->assertTrue($this->config('system.site')->isNew());
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function baseMigration(): MigrationInterface {
    $definition = ['id' => 'default_langcode_base'] + self::DEFAULT_LANGCODE_MIGRATION_BASE;
    return $this->getMigrationPluginInstance($definition);
  }

  /**
   * {@inheritdoc}
   */
  protected function subsequentMigration(): MigrationInterface {
    $definition = self::DEFAULT_LANGCODE_MIGRATION_BASE;
    $definition['id'] = 'default_langcode_subsequent';
    $definition['source']['data_rows'] = [
      [
        'id' => 'base',
        'default_langcode' => 'hu',
      ],
    ];

    return $this->getMigrationPluginInstance($definition);
  }

  /**
   * {@inheritdoc}
   */
  protected function baseTranslationMigration(): ?MigrationInterface {
    // Translations are unintelligible in case of default langcode migration.
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  protected function subsequentTranslationMigration(): ?MigrationInterface {
    // Translations are unintelligible in case of default langcode migration.
    return NULL;
  }

}
