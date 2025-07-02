<?php

declare(strict_types=1);

namespace Drupal\migmag\Utility;

/**
 * Utility for migration source plugins.
 */
class MigMagSourceUtility {

  /**
   * Instantiates a migration source plugin from a plugin ID or configuration.
   *
   * This is a smarter alternative to MigrationDeriverTrait::getSourcePlugin,
   * which isn't able to accept plugin configuration, which means that you're
   * unable to instantiate the 'variable' source plugin in Drupal 8.9.x.
   *
   * @param string|array $source_plugin
   *   The source plugin ID, or a full source plugin configuration.
   *
   * @return \Drupal\migrate\Plugin\MigrateSourceInterface|\Drupal\migrate\Plugin\RequirementsInterface
   *   The fully initialized source plugin.
   */
  public static function getSourcePlugin($source_plugin) {
    $source_plugin_configuration = is_string($source_plugin)
      ? ['plugin' => $source_plugin, 'ignore_map' => TRUE]
      : ['ignore_map' => TRUE] + $source_plugin;
    $stub_migration_definition = [
      'source' => $source_plugin_configuration,
      'destination' => [
        'plugin' => 'null',
      ],
      'idMap' => [
        'plugin' => 'null',
      ],
    ];
    return \Drupal::service('plugin.manager.migration')
      ->createStubMigration($stub_migration_definition)
      ->getSourcePlugin();
  }

}
