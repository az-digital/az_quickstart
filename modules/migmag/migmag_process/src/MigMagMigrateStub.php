<?php

declare(strict_types=1);

namespace Drupal\migmag_process;

use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Database\DatabaseExceptionWrapper;
use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate\MigrateStub;
use Drupal\migrate\Plugin\migrate\source\SqlBase;
use Drupal\migrate\Row;

/**
 * An alternative migrate stub service.
 *
 * This stub service is able to create stubs even for partial entity IDs.
 *
 * When an entity reference field is referencing node 17, and node 17 happens to
 * be the French translation of node 13, then we need a stub to be created for
 * node 13.
 *
 * Because entity references only identify the referenced entity by ID (the `id`
 * entity key), and not by revision ID (the `revision` entity key) nor langcode
 * (the `langcode` entity key), we need the ability to create stubs for those
 * entity reference fields that will eventually resolve to a more specific
 * entity — entity reference fields simply do not reference a particular
 * revision nor a language.
 *
 * Note: the contrib entity_reference_revisions module does reference a
 * particular revision!
 *
 * @see https://drupal.org/i/3156730
 */
class MigMagMigrateStub extends MigrateStub {

  /**
   * Static cache for identifying the main stub process.
   *
   * If this property is set to true, it means that the current process belongs
   * to an entity being stubbed, so the service won't try to create the
   * 'sub-stub' entity.
   *
   * @var bool
   */
  protected static $mainStubProcessHasStarted = FALSE;

  /**
   * Creates a stub.
   *
   * @param string $migration_id
   *   The migration to stub.
   * @param array $source_ids
   *   An array of source ids.
   * @param array $default_values
   *   (optional) An array of default values to add to the stub.
   * @param bool $key_by_destination_ids
   *   (optional) NULL or TRUE to force indexing of the return array by
   *   destination id keys (default), or FALSE to return the raw return value of
   *   the destination plugin's ::import() method. The return value from
   *   MigrateDestinationInterface::import() is very poorly defined as "The
   *   entity ID or an indication of success". In practice, the mapping systems
   *   expect and all destination plugins return an array of destination
   *   identifiers. Unfortunately these arrays are inconsistently keyed. The
   *   core destination plugins return a numerically indexed array of
   *   destination identifiers, but several contrib destinations return an array
   *   of identifiers indexed by the destination keys. This method will
   *   generally index all return arrays for consistency and to provide as much
   *   information as possible, but this parameter is added for backwards
   *   compatibility to allow accessing the original array.
   * @param bool $create_only_valid
   *   (optional) Create stub only if the provided source IDs can be found in
   *   the source of the given migration. Defaults to FALSE.
   *
   * @return array|false
   *   An array of destination ids for the new stub, keyed by destination id
   *   key, or false if the stub failed.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\migrate\MigrateException
   * @throws \LogicException
   */
  public function createStub($migration_id, array $source_ids, array $default_values = [], $key_by_destination_ids = NULL, bool $create_only_valid = FALSE) {
    if (!$create_only_valid) {
      return parent::createStub($migration_id, $source_ids, $default_values, $key_by_destination_ids);
    }

    // If the main stub process was started, then this process would create a
    // stub for an entity being stubbed.
    // We will return FALSE.
    if (self::$mainStubProcessHasStarted) {
      return FALSE;
    }
    else {
      self::$mainStubProcessHasStarted = TRUE;
    }

    $migrations = $this->migrationPluginManager->createInstances([$migration_id]);
    if (!$migrations) {
      throw new PluginNotFoundException($migration_id);
    }
    if (count($migrations) !== 1) {
      throw new \LogicException(sprintf('Cannot stub derivable migration "%s". You must specify the id of a specific derivative to stub.', $migration_id));
    }
    $migration = reset($migrations);
    $source_plugin = $migration->getSourcePlugin();
    $source_plugin_ids = $source_plugin->getIds();
    $source_id_keys = array_keys($source_plugin_ids);
    $source_id_keys_with_aliases = [];
    foreach ($source_plugin_ids as $key => $key_data) {
      $full_key = empty($key_data['alias'])
        ? $key
        : "{$key_data['alias']}.{$key}";
      $source_id_keys_with_aliases[] = $full_key;
    }
    $source_ids = array_combine(array_slice($source_id_keys, 0, count($source_ids)), $source_ids);
    $source_ids_with_aliases = array_combine(array_slice($source_id_keys_with_aliases, 0, count($source_ids)), $source_ids);

    // Check the existence of a source that matches the source IDs before
    // blindly create a stub.
    $stub_should_be_created = FALSE;
    $sql_succeed = $source_plugin instanceof SqlBase;
    if (!$stub_should_be_created && $source_plugin instanceof SqlBase) {
      $source_plugin_query = $source_plugin->query();
      try {
        foreach ($source_ids_with_aliases as $source_id => $source_id_value) {
          $source_plugin_query->condition($source_id, $source_id_value);
        }
        $rows_to_stub = array_reduce($source_plugin_query->execute()->fetchAll(), function (array $carry, $source_data) use ($source_plugin, $migration) {
          $row = new Row($source_data, $migration->getSourcePlugin()->getIds(), TRUE);
          // Stubbing nodes with core node migrations needs prepareRow.
          // Drupal core 8.9.x has a lot of source plugins which are returning
          // void (NULL), so we have to strictly check for FALSE in case of
          // skipped rows.
          if ($source_plugin->prepareRow($row) !== FALSE) {
            $carry[] = $row;
          }
          return $carry;
        }, []);

        $stub_should_be_created = (bool) count($rows_to_stub);
      }
      catch (DatabaseExceptionWrapper $e) {
        $sql_succeed = FALSE;
      }
    }
    // Cannot use the source plugin's query and condition, let's do the slower
    // discovery.
    if (!$stub_should_be_created && !$sql_succeed) {
      try {
        foreach ($source_plugin as $row) {
          assert($row instanceof Row);
          foreach ($source_ids_with_aliases as $source_id_key => $source_id_value) {
            if ($row->getSourceProperty($source_id_key) !== $source_id_value) {
              continue 2;
            }
          }
          $stub_should_be_created = TRUE;

          // Stubbing needs prepareRow.
          if ($source_plugin->prepareRow($row)) {
            $rows_to_stub[] = $row;
          }
        }
      }
      catch (\Exception $e) {
      }
    }

    $stubs = [];
    if ($stub_should_be_created) {
      // We will create stubs from every matching row.
      foreach ($rows_to_stub as $row_to_stub) {
        // @todo Needs core issue to fix upstream: the "status" field won't get
        // populated (because it was not a field in D7). For now we work around
        // this by passing every source row column as the set of default values,
        // which will cause the status field to get populated.
        assert($row_to_stub instanceof Row);
        try {
          if ($stub = $this->doCreateStub($migration, $row_to_stub->getSource(), $default_values)) {
            $stubs[] = $stub;
          }
        }
        // MigrateStub::doCreateStub() also throws MigrateSkipRowException.
        // @todo Remove after https://www.drupal.org/i/3188455 is fixed.
        catch (MigrateSkipRowException $e) {
        }
        catch (MigrateException $e) {
        }
      }
    }

    // If the return from ::import is numerically indexed, and we aren't
    // requesting the raw return value, index it associatively using the
    // destination id keys.
    $stub = empty($stubs)
      ? FALSE
      : reset($stubs);
    if ($stub && ($key_by_destination_ids !== FALSE) && array_keys($stub) === range(0, count($stub) - 1)) {
      $stub = array_combine(array_keys($migration->getDestinationPlugin()->getIds()), $stub);
    }

    // The main stub process finished, reset the static variable.
    self::$mainStubProcessHasStarted = FALSE;

    return $stub;
  }

}
