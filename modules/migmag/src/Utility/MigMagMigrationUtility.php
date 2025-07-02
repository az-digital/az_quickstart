<?php

declare(strict_types=1);

namespace Drupal\migmag\Utility;

use Drupal\Component\Plugin\PluginBase;

// cspell:ignore barbaz

/**
 * Utility for manipulating migration plugin definitions and instances.
 */
class MigMagMigrationUtility {

  /**
   * List of migration lookup process plugin IDs.
   *
   * @const string[]
   *
   * @see \Drupal\migrate\Plugin\Migration::findMigrationDependencies
   */
  const LOOKUP_PROCESS_PLUGINS = [
    'migration',
    'migration_lookup',
    'migmag_lookup',
  ];

  /**
   * List of migration process plugin IDs which have underlying process configs.
   *
   * @const string[]
   *
   * @see \Drupal\migrate\Plugin\Migration::findMigrationDependencies
   */
  const PROCESS_PLUGINS_WITH_SUBPROCESS = [
    'iterator',
    'sub_process',
  ];

  /**
   * Converts destination property process to a list of process plugin configs.
   *
   * @param array|string $process_pipeline
   *   A migration process plugin configuration.
   *
   * @return array[]
   *   The plugin process mapping as an associative array.
   */
  public static function getAssociativeMigrationProcess($process_pipeline): array {
    if (is_string($process_pipeline)) {
      return [['plugin' => 'get', 'source' => $process_pipeline]];
    }
    elseif (array_key_exists('plugin', $process_pipeline)) {
      $process_pipeline = [$process_pipeline];
    }

    return $process_pipeline;
  }

  /**
   * Updates migrations used in migration lookup plugins in migration processes.
   *
   * @param array $migration_definition
   *   A migration plugin definition to process.
   * @param string[] $lookup_migrations_to_update
   *   List of the lookup migrations to update: key is the migration ID which
   *   has to be replaced, the value is the migration ID to be used instead.
   * @param string[] $lookup_migrations_to_remove
   *   List of the lookup migration IDs which have to be removed.
   */
  public static function updateMigrationLookups(array &$migration_definition, array $lookup_migrations_to_update = [], array $lookup_migrations_to_remove = []): void {
    foreach ($migration_definition['process'] as &$process_pipeline) {
      // "$process_pipeline" can be an array of multiple process plugin configs:
      // @code
      // 'ids' => [
      //   0 => [
      //     'plugin' => 'migration_lookup',
      //     'source' => 'node_type',
      //     'migration' => 'd7_node_type',
      //   ],
      //   1 => [
      //     'plugin' => 'skip_on_empty',
      //     'method' => 'row',
      //   ],
      // ]
      // @endcode
      //
      // ...or a single process config:
      // @code
      // 'ids' => [
      //   'plugin' => 'migration_lookup',
      //   'source' => 'node_type',
      //   'migration' => 'd7_node_type',
      // ]
      // @endcode
      //
      // ...or just a string:
      // @code
      // 'ids' => 'd7_node_type'
      // @endcode
      if (!is_array($process_pipeline)) {
        continue;
      }

      $is_single = isset($process_pipeline['plugin']);
      $processed_process_pipeline = $is_single
        ? [$process_pipeline]
        : $process_pipeline;

      foreach ($processed_process_pipeline as &$process_config) {
        if (self::isValidMigrationLookupConfiguration($process_config)) {
          self::processMigrationLookupPluginDefinition($process_config, $lookup_migrations_to_update, $lookup_migrations_to_remove);
        }
        elseif (self::pluginHasSubProcess($process_config)) {
          self::updateMigrationLookups($process_config, $lookup_migrations_to_update, $lookup_migrations_to_remove);
        }
        elseif (self::pluginIsMigmagTry($process_config)) {
          $dummy_migration = [
            'process' => [
              'dummy_destination_property' => $process_config['process'],
            ],
          ];
          self::updateMigrationLookups($dummy_migration, $lookup_migrations_to_update, $lookup_migrations_to_remove);
          $process_config['process'] = $dummy_migration['process']['dummy_destination_property'];
        }
      }

      $process_pipeline = $is_single
        ? reset($processed_process_pipeline)
        : $processed_process_pipeline;
    }
  }

  /**
   * Removes IDs of missing migration dependencies from a migration definition.
   *
   * @param array $migration_definition
   *   The migration plugin definition to process.
   * @param string[] $available_migration_ids
   *   The list of the available, discovered migrations' IDs.
   * @param string|string[]|null $base_id
   *   The ID, or list of IDs of the migrations which should be removed from the
   *   migration dependencies if they are missing. These IDs can be partial IDs
   *   as well, so if this $base_id is "foo:bar", then either "foo:bar", or
   *   "foo:bar:baz" will be removed if these are missing (but "foo:barbaz" will
   *   be kept). If this is NULL, then every single missing dependency is
   *   removed.
   *   Optional, defaults to NULL.
   */
  public static function removeMissingMigrationDependencies(array &$migration_definition, array $available_migration_ids, $base_id = NULL): void {
    if (empty($migration_definition['migration_dependencies']['required'])) {
      return;
    }

    $dependencies_to_check = $migration_definition['migration_dependencies']['required'];
    if ($base_id !== NULL) {
      $base_ids = (array) $base_id;
      $dependencies_to_check = array_filter(
        $dependencies_to_check,
        function ($dependency_id) use ($base_ids) {
          $match = FALSE;
          foreach ($base_ids as $base_id) {
            $match = $dependency_id === $base_id || strpos($dependency_id, $base_id . PluginBase::DERIVATIVE_SEPARATOR) === 0
              ? TRUE
              : $match;
          }
          return $match;
        }
      );
    }

    foreach ($dependencies_to_check as $required_dependency_id) {
      if (!in_array($required_dependency_id, $available_migration_ids, TRUE)) {
        $dependency_key = array_search($required_dependency_id, $migration_definition['migration_dependencies']['required'], TRUE);
        unset($migration_definition['migration_dependencies']['required'][$dependency_key]);
      }
    }
  }

  /**
   * Changes migration lookups migration IDs to the given IDs.
   *
   * @param array $process_plugin_config
   *   A single migration process plugin configuration.
   * @param string[] $lookup_migrations_to_update
   *   List of the lookup migrations to update: key is the migration ID which
   *   has to be replaced, the value is the migration ID(s) to be added instead.
   * @param string[] $lookup_migrations_to_remove
   *   List of the lookup migration IDs which have to be removed.
   */
  protected static function processMigrationLookupPluginDefinition(array &$process_plugin_config, array $lookup_migrations_to_update, array $lookup_migrations_to_remove): void {
    // Migration can be a single migration plugin ID (aka string), or an
    // array of migration plugin IDs.
    if (is_string($process_plugin_config['migration'])) {
      if (isset($lookup_migrations_to_update[$process_plugin_config['migration']])) {
        $process_plugin_config['migration'] = $lookup_migrations_to_update[$process_plugin_config['migration']];
      }
      elseif (in_array($process_plugin_config['migration'], $lookup_migrations_to_remove, TRUE)) {
        $process_plugin_config = array_filter([
          'plugin' => 'get',
          'source' => $process_plugin_config['source'] ?? NULL,
        ]);
      }
    }
    elseif (is_array($process_plugin_config['migration'])) {
      foreach ($lookup_migrations_to_update as $original_migration_plugin_id => $replacement_plugin_id) {
        $lookup_index = array_search($original_migration_plugin_id, $process_plugin_config['migration']);

        if ($lookup_index !== FALSE) {
          if (is_array($replacement_plugin_id)) {
            $process_plugin_config['migration'] = array_merge(
              array_slice($process_plugin_config['migration'], 0, $lookup_index, TRUE),
              $replacement_plugin_id,
              array_slice($process_plugin_config['migration'], $lookup_index + 1, NULL, TRUE)
            );
          }
          else {
            $process_plugin_config['migration'][$lookup_index] = $replacement_plugin_id;
          }
        }
      }

      foreach ($lookup_migrations_to_remove as $plugin_id_to_remove) {
        $lookup_index = array_search($plugin_id_to_remove, $process_plugin_config['migration']);

        if ($lookup_index !== FALSE) {
          $process_plugin_config['migration'] = array_merge(
            array_slice($process_plugin_config['migration'], 0, $lookup_index, TRUE),
            array_slice($process_plugin_config['migration'], $lookup_index + 1, NULL, TRUE)
          );
        }
      }
    }
  }

  /**
   * Determines whether a process plugin configuration has a sub process.
   *
   * @param string|array $plugin_configuration
   *   A process plugin's configuration.
   *
   * @return bool
   *   Whether the process plugin configuration has a sub process.
   */
  protected static function pluginHasSubProcess($plugin_configuration): bool {
    return is_array($plugin_configuration) &&
      !empty($plugin_configuration['process']) &&
      in_array($plugin_configuration['plugin'], self::PROCESS_PLUGINS_WITH_SUBPROCESS, TRUE);
  }

  /**
   * Determines whether the process plugin configuration is a migmag_try config.
   *
   * @param string|array $plugin_configuration
   *   A process plugin's configuration.
   *
   * @return bool
   *   Whether the process plugin configuration is a migmag_try config.
   */
  protected static function pluginIsMigmagTry($plugin_configuration): bool {
    return is_array($plugin_configuration) &&
      !empty($plugin_configuration['process']) &&
      $plugin_configuration['plugin'] === 'migmag_try';
  }

  /**
   * Determines whether a process plugin configuration's is a valid lookup.
   *
   * @param string|array $plugin_configuration
   *   A process plugin's configuration.
   *
   * @return bool
   *   Whether the process plugin configuration's is a valid lookup.
   */
  protected static function isValidMigrationLookupConfiguration($plugin_configuration): bool {
    return self::pluginIdIsMigrationLookup($plugin_configuration) &&
      self::lookupContainsValidMigrationConfig($plugin_configuration);
  }

  /**
   * Determines whether a process plugin configuration's plugin is a lookup.
   *
   * @param string|array $plugin_configuration
   *   A process plugin configuration.
   *
   * @return bool
   *   Whether the process plugin configuration's plugin is a lookup.
   */
  protected static function pluginIdIsMigrationLookup($plugin_configuration): bool {
    return is_array($plugin_configuration) &&
      !empty($plugin_configuration['plugin']) &&
      in_array($plugin_configuration['plugin'], self::LOOKUP_PROCESS_PLUGINS, TRUE);
  }

  /**
   * Determines whether a lookup's migration configuration's is valid.
   *
   * @param array $plugin_configuration
   *   A migration process plugin's configuration.
   *
   * @return bool
   *   Whether the lookup's migration configuration's is valid.
   */
  protected static function lookupContainsValidMigrationConfig(array $plugin_configuration): bool {
    $config_is_available = !empty($plugin_configuration['migration']) &&
      (
        is_string($plugin_configuration['migration']) ||
        is_array($plugin_configuration['migration'])
      );
    if (!$config_is_available) {
      return FALSE;
    }
    return count((array) $plugin_configuration['migration']) ===
      count(
        array_filter(
          (array) $plugin_configuration['migration'],
          'is_string'
        )
      );
  }

}
