<?php

declare(strict_types=1);

namespace Drupal\migmag_process\Plugin\migrate\process;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\Exception\RequirementsException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateLookupInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Plugin\MigrationPluginManagerInterface;
use Drupal\migrate\Plugin\RequirementsInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

// cspell:ignore multifield

/**
 * Looks for destination bundle based on the source/destination entity type.
 *
 * This plugin performs a destination bundle lookup based on the available
 * bundle entity type migrations (e.g. for nodes these are the migrations with
 * 'entity:node_type' destination).
 *
 * Two types of operation are available, that can even be combined. See examples
 * below.
 *
 * Configuration options:
 *  - 'source_entity_type': A ('source' or '@destination') row property which
 *    contains the entity type ID ('comment', 'node', 'paragraphs_item') in the
 *    source. Defaults to NULL.
 *  - 'source_lookup_migrations': IDs of bundle entity type migrations
 *    ('d7_comment_type', 'd7_node_type', 'd7_paragraphs_type') where the target
 *    bundle lookup should be performed, keyed by the correspond migration row
 *    value of the row property defined in 'source_entity_type'. Defaults to an
 *    empty array.
 *  - 'destination_entity_type': A ('source' or '@destination') row property
 *    which contains the entity type ID ('comment', 'node', 'paragraph') in the
 *    destination. Defaults to '@entity_type'.
 *  - 'null_if_missing': If set to TRUE, then if there are no lookup results,
 *    the process plugin will return NULL. Otherwise the incoming value will be
 *    returned. Defaults to FALSE.
 *
 * Examples:
 *
 * @code
 * process:
 *   bundle:
 *     plugin: migmag_target_bundle
 *     source: bundle
 *     source_entity_type: entity_type
 *     source_lookup_migrations:
 *       multifield: multifield_type
 *       paragraphs_item: d7_paragraphs_type
 *       node:
 *         - d7_node_type
 *         - custom_node_type_migration_id
 * @endcode
 *
 * If you want to specify the lookup migrations which can contain the
 * destination bundle ID, then you should specify both the 'source_entity_type'
 * and the 'source_lookup_migrations' configurations.
 * In most of the cases the 'source_entity_type' value should be the entity type
 * ID in the source, such as 'node', 'taxonomy_term', 'field_collection_item' or
 * 'multifield'.
 * The 'source_lookup_migrations' configuration has to specify which migrations
 * may contain the destination ID of the migrated bundle entity type ID. These
 * migrations should be keyed by the actual value of the source entity type ID,
 * and they can be specified as string or as an array.
 *
 * @code
 * process:
 *   bundle:
 *     plugin: migmag_target_bundle
 *     source: bundle
 *     destination_entity_type: '@entity_type'
 * @endcode
 *
 * If you don't want (or you don't need) to specify lookup migrations per source
 * entity type ID, then you should use the 'destination_entity_type'
 * configuration. The 'destination_entity_type' value should be the entity type
 * ID on the destination. In case of entities provided by core, this equals to
 * the source entity type ID, but for 'multifield', 'field_collection_item' or
 * 'paragraphs_item' migrations, it is 'paragraph'.
 * If you use this option, then the corresponding bundle entity type migrations
 * (which meet the necessary conditions) will be identified by this process
 * plugin. For the necessary conditions check ::getBundleEntityTypeMigrations.
 *
 * @code
 * process:
 *   bundle:
 *     plugin: migmag_target_bundle
 *     source: bundle
 *     source_entity_type: entity_type
 *     source_lookup_migrations:
 *       multifield: multifield_type
 *       paragraphs_item: d7_paragraphs_type
 *       field_collection_item: d7_field_collection_type
 *     destination_entity_type: '@entity_type'
 * @endcode
 *
 * You can also combine the above two types of operation if you specify both
 * 'source_entity_type' and 'destination_entity_type'. In this case, if the
 * source entity type ID has mapped migrations in 'source_lookup_migrations',
 * then we will look for the destination IDs in those migrations.
 *
 * @MigrateProcessPlugin(
 *   id = "migmag_target_bundle"
 * )
 */
class MigMagTargetBundle extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The migration being executed.
   *
   * @var \Drupal\migrate\Plugin\MigrationInterface
   */
  protected $migration;

  /**
   * The migrate lookup service.
   *
   * @var \Drupal\migrate\MigrateLookupInterface
   */
  protected $migrateLookup;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The migration plugin manager.
   *
   * @var \Drupal\migrate\Plugin\MigrationPluginManagerInterface
   */
  protected $migrationPluginManager;

  /**
   * Constructs a new MigMagTargetBundle process plugin instance.
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
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\migrate\Plugin\MigrationPluginManagerInterface $migration_plugin_manager
   *   The migration plugin manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, MigrateLookupInterface $migrate_lookup, EntityTypeManagerInterface $entity_type_manager, MigrationPluginManagerInterface $migration_plugin_manager) {
    $configuration += [
      'source_entity_type' => NULL,
      'source_lookup_migrations' => [],
      'destination_entity_type' => '@entity_type',
      'null_if_missing' => FALSE,
    ];
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->migration = $migration;
    $this->migrateLookup = $migrate_lookup;
    $this->entityTypeManager = $entity_type_manager;
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
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.migration')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function transform($bundle, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $source_entity_type = $this->configuration['source_entity_type']
        ? $row->get($this->configuration['source_entity_type'])
        : NULL;
    // It is not necessary to define a source entity type ID. But if it is
    // defined, try to find the lookup migration IDs.
    $lookup_migration_ids = $source_entity_type && !empty($this->configuration['source_lookup_migrations'][$source_entity_type])
      ? (array) $this->configuration['source_lookup_migrations'][$source_entity_type]
      : [];

    // Discover the appropriate lookup migration IDs when no mapped migrations
    // were found and the destination entity type ID is defined.
    $destination_entity_type = $this->configuration['destination_entity_type']
      ? $row->get($this->configuration['destination_entity_type'])
      : NULL;
    if (
      empty($lookup_migration_ids) &&
      $destination_entity_type &&
      $definition = $this->entityTypeManager->getDefinition($destination_entity_type, FALSE)
    ) {
      if ($bundle_entity_type_id = $definition->getBundleEntityType()) {
        $lookup_migration_ids = array_keys(
          $this->getBundleEntityTypeMigrations($bundle_entity_type_id)
        );
      }

      // If the migration plugin ID, its partial derivative ID or base
      // plugin ID is used as a source_lookup_migration, we don't want to use
      // the migration for bundle lookup if we're operating based on the
      // 'destination_entity_type' configuration.
      // It seems to be a good idea to exclude migrations which are specified in
      // 'source_lookup_migrations' for other sources: since Bean Migrate
      // migrates D7 beans to D9 block_content entities, it could help a lot if
      // the lookup migrations of beans are excluded from the destination bundle
      // lookup of  block_content types.
      $all_source_lookup_migrations = array_unique(
        array_reduce(
          $this->configuration['source_lookup_migrations'] ?? [],
          function (array $carry, $ids) {
            $carry = array_merge(
              $carry,
              (array) $ids
            );
            return $carry;
          },
          []
        )
      );
      $lookup_migration_ids = array_filter($lookup_migration_ids, function (string $lookup_migration_candidate_id) use ($all_source_lookup_migrations) {
        $candidate_id_parts = explode(static::DERIVATIVE_SEPARATOR, $lookup_migration_candidate_id);
        $can_be_added = TRUE;
        for ($i = count($candidate_id_parts); $i > 0; $i--) {
          $temp_candidate_id = implode(static::DERIVATIVE_SEPARATOR, array_slice($candidate_id_parts, 0, $i));
          if (in_array($temp_candidate_id, $all_source_lookup_migrations, TRUE)) {
            $can_be_added = FALSE;
          }
        }
        return $can_be_added;
      });
    }

    // Perform lookup in the discovered bundle entity migrations (if any).
    foreach ($lookup_migration_ids as $lookup_migration_id) {
      try {
        $lookup_result = $this->migrateLookup->lookup($lookup_migration_id, (array) $bundle);
      }
      catch (\Exception $e) {
        $lookup_result = NULL;
      }

      // Comment field bundles have a 'comment_node_' prefix. So if there are
      // no results, repeat the lookup with a removed 'comment_node_' prefix
      // (if any).
      if (
        empty($lookup_result) &&
        (
          $source_entity_type === 'comment' ||
          $destination_entity_type === 'comment'
        ) &&
        ($bundle_truncated = preg_replace('/^comment_node_/', '', $bundle)) !== $bundle
      ) {
        $bundle_truncated = preg_replace('/^comment_node_/', '', $bundle);
        try {
          $lookup_result = $this->migrateLookup->lookup($lookup_migration_id, (array) $bundle_truncated);
        }
        catch (\Exception $e) {
          $lookup_result = NULL;
        }
      }

      if (is_array($lookup_result) && isset($lookup_result[0])) {
        $destination_bundle = reset($lookup_result[0]);
        break;
      }
    }

    if (!isset($destination_bundle) && $this->configuration['null_if_missing']) {
      return NULL;
    }

    return $destination_bundle ?? $bundle;
  }

  /**
   * Returns the IDs of entity bundle migrations with matching destination.
   *
   * @param string $bundle_entity_type_id
   *   The entity type ID of the bundle entity type.
   *
   * @return string[]
   *   The IDs of entity bundle migrations which destination matches the given
   *   bundle entity ID.
   */
  protected function getBundleEntityTypeMigrations($bundle_entity_type_id) {
    return array_filter(
      $this->migrationPluginManager->createInstances([]),
      function (MigrationInterface $migration) use ($bundle_entity_type_id) {
        if ($migration->getDestinationConfiguration()['plugin'] !== "entity:$bundle_entity_type_id") {
          return FALSE;
        }

        // Filter out migrations which don't met their requirements:
        // - Migrations which source plugin requirements aren't met.
        // - Migrations which destination plugin requirements aren't met.
        // - Migrations which dependencies aren't yet executed.
        if ($migration instanceof RequirementsInterface) {
          try {
            $migration->checkRequirements();
          }
          catch (RequirementsException $e) {
            return FALSE;
          }
        }

        // Filter out migrations which source or destination ID count does not
        // equal to 1.
        if (
          count($migration->getSourcePlugin()->getIds()) !== 1 ||
          count($migration->getDestinationPlugin()->getIds()) !== 1
        ) {
          return FALSE;
        }

        // Migrations which don't have any rows processed (yet) shouldn't be
        // used for lookup.
        return $migration->getIdMap()->processedCount();
      }
    );
  }

}
