<?php

declare(strict_types=1);

namespace Drupal\migmag_process\Plugin\migrate\process;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Component\Plugin\PluginBase;
use Drupal\migmag\Traits\MigMagMigrationConfigurationTrait;
use Drupal\migmag\Utility\MigMagSourceUtility;
use Drupal\migmag_process\MigMagMigrateStub;
use Drupal\migrate\Exception\RequirementsException;
use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateLookupInterface;
use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Plugin\MigrationPluginManagerInterface;
use Drupal\migrate\Plugin\migrate\process\MigrationLookup;
use Drupal\migrate\Row;
use Drupal\migrate_drupal\NodeMigrateType;
use Drupal\migrate_drupal\Plugin\migrate\source\Variable;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A migration lookup plugin which creates only valid stubs.
 *
 * Drupal core's migration_lookup plugin has some limitations in regards of
 * stubbing which cannot be worked around:
 * - MigrationLookup is not able to identify which migration contains a
 *   source record (row) with the matching IDs,
 * - Because of the above, it creates stubs for wrong migrations – for example
 *   in the current migration. Or, if the current migration isn't specified in
 *   the given lookup migrations configuration, then in the first lookup
 *   migration.
 * - The plugin might create stubs which aren't represented in the lookup
 *   migration (we call them "invalid stubs"). These stubs can't ever been
 *   updated.
 * - The plugin cannot create stubs from partial (not fully specified) source
 *   row IDs.
 * - You cannot pass destination property values to the migrate stub service,
 *   although it would be able to handle them appropriately.
 *
 * However, this plugin overcomes the above limitations:
 * - MigMagLookup is able to identify which migrations may contain the row
 *   specified with the source plugin IDs.
 * - Because if this, it is able to create stubs in the right migrations. If the
 *   row specified with its source IDs can be found in more lookup migrations,
 *   then it creates stubs for every matching source record.
 * - …so it only created valid stubs.
 * - Stub creation only fails if the underlying migration plugin instance makes
 *   it to fail.
 * - You CAN pass destination property values to the migrate stub service with
 *   the 'stub_default_values' configuration. The configuration is an array of
 *   row properties, keyed by the stub migration's destination properties. Row
 *   properties are fetched from the host migration's row.
 * - And the best one: it is able to create stubs based on not-fully-specified
 *   source row IDs. If you need the destination ID of a node referenced from
 *   an entity reference field, you only have to use the information you have
 *   from the original field value: the ID of the source node. You don't have to
 *   worry about how to get a revision ID (or a language code) just for looking
 *   for the migrated node's ID.
 *
 * If you specify the stub migration ID (which should store the stub) with the
 * 'stub_id' configuration key, then the old behavior is kept, so if you're
 * unlucky, you might have invalid stubs.
 *
 * If you want to create invalid stubs only when none of the provided lookup
 * migrations contain the specified source record, you should specify the
 * 'fallback' stub migration's plugin ID with the 'fallback_stub_id'
 * configuration key.
 *
 * Examples:
 *
 * Let the plugin determine which migrations contain the specified row, and
 * create stub for not-yet migrated sources:
 * @code
 * process:
 *   target_id:
 *     plugin: migmag_lookup
 *     migration: d7_node_complete
 *     source: nid
 * @endcode
 *
 * Use a fallback migration to collect invalid (missing) target ID references:
 * @code
 * process:
 *   target_id:
 *     plugin: migmag_lookup
 *     migration: d7_node_complete
 *     source: nid
 *     fallback_stub_id: migration_plugin_id_for_invalid_references
 * @endcode
 *
 * Use a specific migration for creating stubs:
 * @code
 * process:
 *   target_id:
 *     plugin: migmag_lookup
 *     migration: d7_node_complete
 *     source: nid
 *     stub_id: migration_plugin_id_for_every_fresh_stub
 * @endcode
 *
 * Define default values for the stubs being created:
 * @code
 * process:
 *   target_id:
 *     plugin: migmag_lookup
 *     migration: d7_node_complete
 *     source: nid
 *     stub_default_values:
 *       langcode: 'source_property',
 *       field_foo: '@destination_property'
 * @endcode
 *
 * @see https://drupal.org/i/3156730
 *
 * @MigrateProcessPlugin(
 *   id = "migmag_lookup"
 * )
 */
class MigMagLookup extends MigrationLookup {

  use MigMagMigrationConfigurationTrait;

  /**
   * The migrate stub service.
   *
   * @var \Drupal\migmag_process\MigMagMigrateStub
   */
  protected $migrateStub;

  /**
   * The migration plugin manager service.
   *
   * @var \Drupal\migrate\Plugin\MigrationPluginManagerInterface
   */
  protected $migrationPluginManager;

  /**
   * Constructs a new MigMagLookup plugin instance.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\migrate\Plugin\MigrationInterface $migration
   *   The Migration the plugin is being used in.
   * @param \Drupal\migrate\MigrateLookupInterface $migrate_lookup
   *   The migrate lookup service.
   * @param \Drupal\migmag_process\MigMagMigrateStub $migrate_stub
   *   The migrate stub service.
   * @param \Drupal\migrate\Plugin\MigrationPluginManagerInterface $migration_plugin_manager
   *   The migration plugin's manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, MigrateLookupInterface $migrate_lookup, MigMagMigrateStub $migrate_stub, MigrationPluginManagerInterface $migration_plugin_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration, $migrate_lookup, $migrate_stub);
    $this->migrationPluginManager = $migration_plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, ?MigrationInterface $migration = NULL) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $migration,
      $container->get('migrate.lookup'),
      $container->get('migmag_process.lookup.stub'),
      $container->get('plugin.manager.migration')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $lookup_migration_ids = (array) $this->configuration['migration'];
    $destination_ids = NULL;
    $source_id_values = [];
    foreach ($lookup_migration_ids as $lookup_migration_id) {
      $lookup_source_ids = (array) $value;
      if (isset($this->configuration['source_ids'][$lookup_migration_id])) {
        $lookup_source_ids = array_values($row->getMultiple($this->configuration['source_ids'][$lookup_migration_id]));
      }
      $this->skipInvalid($lookup_source_ids);
      $source_id_values[$lookup_migration_id] = $lookup_source_ids;

      // Re-throw any PluginException as a MigrateException so the executable
      // can shut down the migration.
      try {
        $destination_id_array = $this->migrateLookup->lookup($lookup_migration_id, $lookup_source_ids);
      }
      catch (PluginNotFoundException $e) {
        $destination_id_array = [];
      }
      catch (MigrateException $e) {
        throw $e;
      }
      catch (\Exception $e) {
        throw new MigrateException(sprintf('A %s was thrown while processing this migration lookup', gettype($e)), $e->getCode(), $e);
      }

      if ($destination_id_array) {
        $destination_ids = array_values(reset($destination_id_array));
        break;
      }
    }

    if (!$destination_ids && !empty($this->configuration['no_stub'])) {
      return NULL;
    }

    if (!$destination_ids) {
      // Pre-fill source ID values for 'stub_id' and 'fallback_stub_id'.
      foreach (['stub_id', 'fallback_stub_id'] as $stub_config_key) {
        if (empty($this->configuration[$stub_config_key])) {
          continue;
        }

        $stub_migration_id = $this->configuration[$stub_config_key];
        if (!isset($source_id_values[$stub_migration_id])) {
          $plugin_id_parts = explode(PluginBase::DERIVATIVE_SEPARATOR, $stub_migration_id);

          for ($i = count($plugin_id_parts); $i > 0; $i--) {
            $plugin_id_to_check = implode(
              PluginBase::DERIVATIVE_SEPARATOR,
              array_slice($plugin_id_parts, 0, $i)
            );
            if (isset($this->configuration['source_ids'][$plugin_id_to_check])) {
              $source_id_values[$stub_migration_id] = array_values(
                $row->getMultiple($this->configuration['source_ids'][$plugin_id_to_check])
              );
            }
          }

          $source_id_values[$stub_migration_id] = $source_id_values[$stub_migration_id] ?? (array) $value;
        }
      }

      $stub_default_values = [];
      foreach ($this->configuration['stub_default_values'] ?? [] as $destination_property => $destination_property_value_source) {
        $stub_default_values[$destination_property] = $row->get($destination_property_value_source);
      }
      $destination_ids = $this->getDestinationIds($source_id_values, $stub_default_values);
    }
    if ($destination_ids) {
      if (count($destination_ids) == 1) {
        return reset($destination_ids);
      }
      else {
        return $destination_ids;
      }
    }

    return NULL;
  }

  /**
   * Returns the IDs of the migration plugins where the stub can be created.
   *
   * @return string[][]
   *   The full ID of the migrations, keyed by the plugin ID as they were
   *   defined in the process plugin's configuration.
   */
  protected function getStubMigrationIds(): array {
    $stub_migration_ids = [];
    $stub_migration_candidates = (array) $this->configuration['migration'];
    // "d7_comment" contains the "d7_node_complete" migration twice.
    $stub_migration_candidates = array_unique($stub_migration_candidates);

    if (count($stub_migration_candidates) > 1) {
      [
        'legacy_core_version' => $legacy_drupal,
        'node_migration_is_complete' => $node_migration_type_is_complete,
      ] = static::getSourceMigrationInfo();

      // "statistics_node_translation_counter" searches source values in both
      // "d6_node_translation" and "d7_node_translation".
      if ($legacy_drupal) {
        $stub_migration_candidates = array_filter($stub_migration_candidates, function (string $id) use ($legacy_drupal) {
          return !preg_match('/^d[^' . $legacy_drupal . ']_.+$/', $id);
        });

        // If this migration tries to look up a destination value in both
        // "d*_node_translation" and "d*_node_complete", we can decide which one
        // should be used.
        $node_migration_ids = [
          "d{$legacy_drupal}_node_translation",
          "d{$legacy_drupal}_node_complete",
          "d{$legacy_drupal}_node",
        ];
        if (empty(array_diff($stub_migration_candidates, $node_migration_ids))) {
          if ($node_migration_type_is_complete) {
            $translation_migration_key = array_search("d{$legacy_drupal}_node_translation", $stub_migration_candidates);
            $node_migration_key = array_search("d{$legacy_drupal}_node", $stub_migration_candidates);
            if ($translation_migration_key !== FALSE) {
              unset($stub_migration_candidates[$translation_migration_key]);
            }
            if ($node_migration_key !== FALSE) {
              unset($stub_migration_candidates[$node_migration_key]);
            }
          }
          else {
            $complete_migration_key = array_search("d{$legacy_drupal}_node_complete", $stub_migration_candidates);
            unset($stub_migration_candidates[$complete_migration_key]);
          }
        }
      }
    }

    if (count($stub_migration_candidates) < 1) {
      return [];
    }

    foreach ($stub_migration_candidates as $lookup_migration_id) {
      $stub_migrations = [];
      try {
        $stub_migrations = $this->migrationPluginManager->createInstances([$lookup_migration_id]);
      }
      catch (PluginException $e) {
      }

      $lookup_migration_base_id = explode(PluginBase::DERIVATIVE_SEPARATOR, $lookup_migration_id)[0];
      $stub_migration_ids[$lookup_migration_base_id] = array_unique(
        array_merge(
          $stub_migration_ids[$lookup_migration_base_id] ?? [],
          array_reduce($stub_migrations, function (array $carry, MigrationInterface $migration) {
            $carry[] = $migration->id();
            return $carry;
          }, [])
        )
      );
    }

    foreach (['stub_id', 'fallback_stub_id'] as $stub_config_key) {
      if (empty($this->configuration[$stub_config_key])) {
        continue;
      }
      $stub_migration_id = $this->configuration[$stub_config_key];
      $stub_migration_base_id = explode(PluginBase::DERIVATIVE_SEPARATOR, $stub_migration_id)[0];
      $stub_migration_ids[$stub_migration_base_id] = [$stub_migration_id];

      // Do not check 'fallback_stub_id' config if 'stub_id' was configured.
      break 1;
    }

    return $stub_migration_ids;
  }

  /**
   * Creates valid stub in the right migration and returns its destination IDs.
   *
   * @param array $source_id_values
   *   The source ID values keyed by migration (base|full) IDs.
   * @param array $stub_default_values
   *   Destination property values to be set to the stub entity before it's
   *   getting imported.
   *
   * @return array|null
   *   The destination IDs of the stub, or NULL if it cannot be created.
   *
   * @throws \Drupal\migrate\MigrateException
   * @throws \Drupal\migrate\MigrateSkipRowException
   */
  protected function getDestinationIds(array $source_id_values, array $stub_default_values = []): ?array {
    $destination_ids = NULL;
    $exception = NULL;
    foreach ($this->getStubMigrationIds() as $stub_migration_full_ids) {
      foreach ($stub_migration_full_ids as $stub_migration_full_id) {
        try {
          $current_source_id_values = NULL;
          $create_only_valid_stubs = TRUE;
          $id_parts = explode(PluginBase::DERIVATIVE_SEPARATOR, $stub_migration_full_id);
          for ($i = count($id_parts); $i > 0; $i--) {
            $plugin_id_to_check = implode(
              PluginBase::DERIVATIVE_SEPARATOR,
              array_slice($id_parts, 0, $i)
            );
            if (!$current_source_id_values) {
              $current_source_id_values = $source_id_values[$plugin_id_to_check] ?? NULL;
            }
            // If we're operating on the provided 'stub_id' or
            // 'fallback_stub_id', we allow creating invalid stubs.
            if ($create_only_valid_stubs) {
              $create_only_valid_stubs =
                ($this->configuration['stub_id'] ?? NULL) !== $plugin_id_to_check &&
                ($this->configuration['fallback_stub_id'] ?? NULL) !== $plugin_id_to_check;
            }
          }

          if ($current_source_id_values) {
            $destination_ids = $this->migrateStub->createStub($stub_migration_full_id, $current_source_id_values, $stub_default_values, FALSE, $create_only_valid_stubs);
          }
        }
        catch (\LogicException $e) {
          // MigrateStub::createStub() shouldn't throw LogicException because
          // we pass a full migration plugin ID param, but the original clause
          // also caught LogicException thrown in MigrateStub::doCreateStub().
          // So we have to continue to catch this for BC.
          // @todo Fix upstream MigrateStub::doCreateStub() – it should catch
          //   the expected Throwables and thrown them as MigrateException.
        }
        catch (PluginNotFoundException $e) {
          // This also catches PluginNotFoundException thrown in
          // MigrateStub::doCreateStub().
        }
        catch (MigrateException $exception) {
        }
        catch (MigrateSkipRowException $exception) {
        }
        catch (\Exception $exception) {
        }

        if ($destination_ids) {
          break 2;
        }
      }
    }

    // Rethrow the last exception as a MigrateException so the executable can
    // shut down the migration.
    if (empty($destination_ids) && $exception) {
      if (
        $exception instanceof MigrateException ||
        $exception instanceof MigrateSkipRowException
      ) {
        throw $exception;
      }

      throw new MigrateException(sprintf('A(n) %s was thrown while attempting to stub, with the following message: %s.', get_class($exception), $exception->getMessage()), $exception->getCode(), $exception);
    }

    return !empty($destination_ids) ? $destination_ids : NULL;
  }

  /**
   * Returns legacy core version and info about the node migration.
   *
   * @return array
   *   The legacy core version, keyed by "legacy_core_version" (a string or
   *   FALSE);
   *   A boolean indicating whether the node migration type is "complete" or
   *   not, keyed by "node_migration_is_complete".
   */
  protected static function getSourceMigrationInfo(): array {
    // Let's try to use the simplest Drupal source plugin.
    $variable_source_plugin = NULL;
    try {
      $variable_source_plugin = MigMagSourceUtility::getSourcePlugin([
        'plugin' => 'variable',
        'variables' => [],
      ]);
    }
    catch (PluginNotFoundException $e) {
    }
    catch (RequirementsException $e) {
    }
    if (!$variable_source_plugin instanceof Variable) {
      // If the 'variable' source plugin is not available or its requirements
      // are not met, then we can assume that this is not a Drupal to Drupal
      // migration.
      return [
        'legacy_core_version' => 0,
        'node_migration_is_complete' => TRUE,
      ];
    }
    $source_db = $variable_source_plugin->getDatabase();
    $legacy_core_version = static::getSourceDrupalVersion($source_db);
    $node_migration_is_complete = class_exists(NodeMigrateType::class)
      && is_callable([NodeMigrateType::class, 'getNodeMigrateType'])
      && NodeMigrateType::getNodeMigrateType($source_db, $legacy_core_version) === NodeMigrateType::NODE_MIGRATE_TYPE_COMPLETE;

    return [
      'legacy_core_version' => $legacy_core_version,
      'node_migration_is_complete' => $node_migration_is_complete,
    ];
  }

}
