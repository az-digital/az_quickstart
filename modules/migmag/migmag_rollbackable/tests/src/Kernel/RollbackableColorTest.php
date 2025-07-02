<?php

namespace Drupal\Tests\migmag_rollbackable\Kernel;

use Drupal\migrate\MigrateExecutable;
use Drupal\migrate\Plugin\MigrationInterface;

/**
 * Tests the rollbackability of theme color settings destination.
 *
 * @coversDefaultClass \Drupal\migmag_rollbackable\Plugin\migrate\destination\RollbackableColor
 *
 * @group migmag_rollbackable
 */
class RollbackableColorTest extends RollbackableDestinationTestBase {

  protected const FRONTEND_THEME = 'olivero';
  protected const ADMIN_THEME = 'claro';

  /**
   * Base for the test migrations.
   *
   * @const array
   */
  const COLOR_MIGRATION_BASE = [
    'source' => [
      'plugin' => 'embedded_data',
      'config_prefix' => 'color.theme.',
      'data_rows' => [
        [
          'element_name' => 'files',
          'value' => [
            'public://color/' . self::FRONTEND_THEME . '-112137/logo.png',
            'public://color/' . self::FRONTEND_THEME . '-112137/colors.css',
          ],
          'theme_name' => self::FRONTEND_THEME,
        ],
        [
          'element_name' => 'logo',
          'value' => 'public://color/' . self::FRONTEND_THEME . '-112137/logo.png',
          'theme_name' => self::FRONTEND_THEME,
        ],
        [
          'element_name' => 'palette',
          'value' => [
            'bg' => '#112137',
            'fg' => '#ffffff',
          ],
          'theme_name' => self::FRONTEND_THEME,
        ],
        [
          'element_name' => 'stylesheets',
          'value' => [
            'public://color/' . self::FRONTEND_THEME . '-112137/colors.css',
          ],
          'theme_name' => self::FRONTEND_THEME,
        ],
      ],
      'ids' => [
        'element_name' => ['type' => 'string'],
        'theme_name' => ['type' => 'string'],
      ],
    ],
    'process' => [
      'element_name' => 'element_name',
      'configuration_name' => [
        'plugin' => 'concat',
        'source' => [
          'config_prefix',
          'theme_name',
        ],
      ],
      'value' => 'value',
    ],
    'destination' => [
      'plugin' => 'migmag_rollbackable_color',
    ],
  ];

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    // Color is not yet 11-compatible.
    // @code
    // 'color',
    // @endcode
  ];

  /**
   * Tests the rollbackability of color settings destination.
   *
   * @dataProvider providerTestMigrationRollback
   */
  public function testColorRollback(bool $with_preexisting_config = TRUE) {
    // Color is not yet 11-compatible.
    $this->markTestSkipped("Skip test since contrib Color module is not yet compatible with Drupal core 11.");
    $this->container->get('theme_installer')->install([
      self::FRONTEND_THEME,
      self::ADMIN_THEME,
    ]);
    $frontend_color_config_name = 'color.theme.' . self::FRONTEND_THEME;
    $admin_color_config_name = 'color.theme.' . self::ADMIN_THEME;
    $frontend_color = $this->config($frontend_color_config_name);
    $admin_color = $this->config($admin_color_config_name);
    $this->assertTrue($frontend_color->isNew());
    $this->assertTrue($admin_color->isNew());

    if ($with_preexisting_config) {
      $frontend_color->set('logo', 'public://color/initial-dummy-logo.png')->save();
      $admin_color->set('logo', 'public://color/initial-dummy-logo.png')->save();
    }

    $frontend_color_data_before_migration = $frontend_color->getRawData();
    $admin_color_data_before_migration = $admin_color->getRawData();

    // Execute the base migration.
    $base_executable = new MigrateExecutable($this->baseMigration(), $this);
    $this->startCollectingMessages();
    $base_executable->import();
    $this->assertNoErrors();

    $expected_frontend_color_after_base_migration = [
      'files' => [
        'public://color/' . self::FRONTEND_THEME . '-112137/logo.png',
        'public://color/' . self::FRONTEND_THEME . '-112137/colors.css',
      ],
      'logo' => 'public://color/' . self::FRONTEND_THEME . '-112137/logo.png',
      'palette' => [
        'bg' => '#112137',
        'fg' => '#ffffff',
      ],
      'stylesheets' => [
        'public://color/' . self::FRONTEND_THEME . '-112137/colors.css',
      ],
    ];

    $this->assertEquals(
      $expected_frontend_color_after_base_migration,
      $this->config($frontend_color_config_name)->getRawData()
    );

    if (!$with_preexisting_config) {
      $this->assertTrue($this->config($admin_color_config_name)->isNew());
    }

    // Execute another migration which updates some of the previous targets.
    $subsequent_executable = new MigrateExecutable($this->subsequentMigration(), $this);
    $this->startCollectingMessages();
    $subsequent_executable->import();
    $this->assertNoErrors();

    $expected_frontend_color_after_subsequent_migration = $expected_frontend_color_after_base_migration;
    $expected_frontend_color_after_subsequent_migration['palette'] = [
      'bg' => '#ffffff',
      'fg' => '#112137',
    ];

    $this->assertEquals(
      $expected_frontend_color_after_subsequent_migration,
      $this->config($frontend_color_config_name)->getRawData()
    );

    $this->assertEquals(
      [
        'files' => [
          'public://color/' . self::ADMIN_THEME . '-34f55321/logo.png',
        ],
        'logo' => 'public://color/' . self::ADMIN_THEME . '-34f55321/logo.png',
      ],
      $this->config('color.theme.' . self::ADMIN_THEME)->getRawData()
    );

    // Roll back the last migration.
    $subsequent_executable->rollback();

    if (!$with_preexisting_config) {
      $this->assertFalse($this->config($frontend_color_config_name)->isNew());
      $this->assertTrue($this->config($admin_color_config_name)->isNew());
    }
    $this->assertEquals(
      $expected_frontend_color_after_base_migration,
      $this->config($frontend_color_config_name)->getRawData()
    );
    $this->assertEquals(
      $admin_color_data_before_migration,
      $this->config($admin_color_config_name)->getRawData()
    );

    // Roll back the base migration.
    $base_executable->rollback();

    $this->assertEquals(
      $frontend_color_data_before_migration,
      $this->config($frontend_color_config_name)->getRawData()
    );
    $this->assertEquals(
      $admin_color_data_before_migration,
      $this->config($admin_color_config_name)->getRawData()
    );

    if ($with_preexisting_config) {
      $this->assertFalse($this->config($frontend_color_config_name)->isNew());
      $this->assertFalse($this->config($admin_color_config_name)->isNew());
    }
    else {
      $this->assertTrue($this->config($frontend_color_config_name)->isNew());
      $this->assertTrue($this->config($admin_color_config_name)->isNew());
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function baseMigration(): MigrationInterface {
    $definition = ['id' => 'color_base'] + self::COLOR_MIGRATION_BASE;
    return $this->getMigrationPluginInstance($definition);
  }

  /**
   * {@inheritdoc}
   */
  protected function subsequentMigration(): MigrationInterface {
    $definition = self::COLOR_MIGRATION_BASE;
    $definition['id'] = 'color_subsequent';
    $definition['source']['data_rows'] = [
      [
        'element_name' => 'palette',
        'value' => [
          'bg' => '#ffffff',
          'fg' => '#112137',
        ],
        'theme_name' => self::FRONTEND_THEME,
      ],
      [
        'element_name' => 'files',
        'value' => [
          'public://color/' . self::ADMIN_THEME . '-34f55321/logo.png',
        ],
        'theme_name' => self::ADMIN_THEME,
      ],
      [
        'element_name' => 'logo',
        'value' => 'public://color/' . self::ADMIN_THEME . '-34f55321/logo.png',
        'theme_name' => self::ADMIN_THEME,
      ],
    ];

    return $this->getMigrationPluginInstance($definition);
  }

  /**
   * {@inheritdoc}
   */
  protected function baseTranslationMigration(): ?MigrationInterface {
    // Color destination cannot have translation destination.
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  protected function subsequentTranslationMigration(): ?MigrationInterface {
    // Color destination cannot have translation destination.
    return NULL;
  }

}
