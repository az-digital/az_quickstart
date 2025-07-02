<?php

namespace Drupal\paragraphs\Plugin\migrate\process;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateLookupInterface;
use Drupal\migrate\MigrateStubInterface;
use Drupal\migrate\Plugin\migrate\process\MigrationLookup;
use Drupal\migrate\Plugin\MigrateIdMapInterface;
use Drupal\migrate\Plugin\MigratePluginManagerInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Plugin\MigrationPluginManagerInterface;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Looks up the value of a paragraph property based on previous migrations.
 *
 * Compared to the MigrationLookup, this process plugin can accept two new
 * configuration option: these are 'tags' and 'tag_ids'.
 * Every other configuration options are inherited from MigrationLookup. If
 * 'tags' has value, then the migration tag based lookup takes precedence over
 * the migration plugin ID based property lookup.
 *
 * @todo Clean up, add test coverage and document how the two extra config
 *   option works in https://drupal.org/i/3146646.
 *
 * @MigrateProcessPlugin(
 *   id = "paragraphs_lookup"
 * )
 */
class ParagraphsLookup extends MigrationLookup {

  /**
   * The migration plugin manager.
   *
   * @var \Drupal\migrate\Plugin\MigrationPluginManagerInterface
   */
  protected $migrationPluginManager;

  /**
   * The process plugin manager.
   *
   * @var \Drupal\migrate\Plugin\MigratePluginManager
   */
  protected $processPluginManager;

  /**
   * Constructs a MigrationLookup object.
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
   * @param \Drupal\migrate\MigrateStubInterface $migrate_stub
   *   The migrate stub service.
   * @param \Drupal\migrate\Plugin\MigrationPluginManagerInterface $migration_plugin_manager
   *   The Migration Plugin Manager Interface.
   * @param \Drupal\migrate\Plugin\MigratePluginManagerInterface $process_plugin_manager
   *   The process migration plugin manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, MigrateLookupInterface $migrate_lookup, MigrateStubInterface $migrate_stub, MigrationPluginManagerInterface $migration_plugin_manager, MigratePluginManagerInterface $process_plugin_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration, $migrate_lookup, $migrate_stub);

    $this->migrationPluginManager = $migration_plugin_manager;
    $this->processPluginManager = $process_plugin_manager;
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
      $container->get('migrate.stub'),
      $container->get('plugin.manager.migration'),
      $container->get('plugin.manager.migrate.process')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $source_id_values = [];
    $destination_ids = NULL;
    $migrations = [];
    if (isset($this->configuration['tags'])) {
      $tags = (array) $this->configuration['tags'];
      foreach ($tags as $tag) {
        /** @var \Drupal\migrate\Plugin\MigrationInterface[] $tag_migrations */
        $tag_migrations = $this->migrationPluginManager->createInstancesByTag($tag);
        $migrations += $tag_migrations;
        if (isset($this->configuration['tag_ids'][$tag])) {
          $configuration = ['source' => $this->configuration['tag_ids'][$tag]];
          try {
            $get_process_plugin = $this->processPluginManager
              ->createInstance('get', $configuration, $this->migration);
          }
          catch (PluginException $e) {
            continue;
          }
          $value = $get_process_plugin->transform(NULL, $migrate_executable, $row, $destination_property);
        }
        foreach ($tag_migrations as $migration_id => $migration) {
          $source_id_values[$migration_id] = (array) $value;
          $destination_ids = $this->lookupDestination($migration, $value);
          if ($destination_ids) {
            break 2;
          }
        }
      }
    }
    elseif (!empty($this->configuration['migration'])) {
      $destination_ids = parent::transform($value, $migrate_executable, $row, $destination_property);
      $migration_ids = $this->configuration['migration'];
      if (!is_array($migration_ids)) {
        $migration_ids = (array) $migration_ids;
      }
      /** @var \Drupal\migrate\Plugin\MigrationInterface[] $migrations */
      $migrations = $this->migrationPluginManager->createInstances($migration_ids);
      foreach ($migrations as $migration_id => $migration) {
        if (isset($this->configuration['source_ids'][$migration_id])) {
          $configuration = ['source' => $this->configuration['source_ids'][$migration_id]];
          $value = $this->processPluginManager
            ->createInstance('get', $configuration, $this->migration)
            ->transform(NULL, $migrate_executable, $row, $destination_property);
        }
        $source_id_values[$migration_id] = (array) $value;
        $destination_ids = $this->lookupDestination($migration, $value);
        if ($destination_ids) {
          break;
        }
      }
    }
    else {
      throw new MigrateException("Either Migration or Tags must be defined.");
    }

    if (!$destination_ids && !empty($this->configuration['no_stub'])) {
      return NULL;
    }

    if (!$destination_ids) {
      // If the lookup didn't succeed, figure out which migration will do the
      // stubbing.
      if (isset($this->configuration['stub_id'])) {
        $migration = $this->migrationPluginManager->createInstance($this->configuration['stub_id']);
        assert($migration instanceof MigrationInterface);
      }
      else {
        $migration = reset($migrations);
      }
      $destination_plugin = $migration->getDestinationPlugin(TRUE);
      // Only keep the process necessary to produce the destination ID.
      $process = $migration->getProcess();

      // We already have the source ID values but need to key them for the Row
      // constructor.
      $source_ids = $migration->getSourcePlugin()->getIds();
      $values = [];
      foreach (array_keys($source_ids) as $index => $source_id) {
        $values[$source_id] = $source_id_values[$migration->getPluginId()][$index];
      }

      // @todo use the migration.stub service.
      $stub_row = new Row($values + $migration->getSourceConfiguration(), $source_ids, TRUE);

      // Do a normal migration with the stub row.
      $migrate_executable->processRow($stub_row, $process);
      $destination_ids = [];
      $id_map = $migration->getIdMap();
      try {
        $destination_ids = $destination_plugin->import($stub_row);
      }
      catch (\Exception $e) {
        $id_map->saveMessage($stub_row->getSourceIdValues(), $e->getMessage());
      }

      if ($destination_ids) {
        $id_map->saveIdMapping($stub_row, $destination_ids, MigrateIdMapInterface::STATUS_NEEDS_UPDATE);
      }
    }
    if ($destination_ids) {
      if (count($destination_ids) == 1) {
        return reset($destination_ids);
      }
      else {
        return $destination_ids;
      }
    }

    throw new MigrateException("Paragraphs lookup wasn't able to find the corresponding property for paragraph with source ID $value for the destination property $destination_property.");
  }

  /**
   * Look for destination records.
   *
   * @param \Drupal\migrate\Plugin\MigrationInterface $migration
   *   The migration that should be checked.
   * @param string|string[] $value
   *   The source ID.
   *
   * @return array|false
   *   The array of the destination identifiers, or FALSE if destination cannot
   *   be determined.
   *
   * @throws \Drupal\migrate\MigrateException
   * @throws \Drupal\migrate\MigrateSkipProcessException
   */
  protected function lookupDestination(MigrationInterface $migration, $value) {
    $value = (array) $value;
    $this->skipInvalid($value);

    // Break out of the loop as soon as a destination ID is found.
    if ($destination_ids = $migration->getIdMap()->lookupDestinationIds($value)) {
      $destination_ids = array_combine(array_keys($migration->getDestinationPlugin()->getIds()), reset($destination_ids));
      return $destination_ids;
    }
    return FALSE;
  }

}
