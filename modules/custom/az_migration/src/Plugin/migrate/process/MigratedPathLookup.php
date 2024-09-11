<?php

namespace Drupal\az_migration\Plugin\migrate\process;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'MigratedPathLookup' migrate process plugin.
 *
 * Available configuration keys
 * - term_migration: A single taxonomy term migration ID, or an array of
 *   taxonomy term migration IDs to reference migrated taxonomy term paths
 *   against.
 * - node_migration: A single node migration ID, or an array of node migration
 *   IDs to reference migrated node paths against.
 *
 * Examples:
 *
 * Consider a menu links migration, where you want to preserve internal links to
 * nodes or taxonomy terms that have already been migrated.
 * @code
 * process:
 *   link_path_processed:
 *     plugin: az_migrated_path_lookup
 *     term_migration:
 *       - az_person_categories_secondary
 *       - az_person_categories
 *     node_migration:
 *       - az_node_person
 *     source: link_path
 * @endcode
 *
 * @MigrateProcessPlugin(
 *  id = "az_migrated_path_lookup"
 * )
 */
class MigratedPathLookup extends ProcessPluginBase implements ContainerFactoryPluginInterface {
  /**
   * The migration plugin manager.
   *
   * @var \Drupal\migrate\Plugin\MigratePluginManagerInterface
   */
  protected $migrationPluginManager;

  /**
   * The migration to be executed.
   *
   * @var \Drupal\migrate\Plugin\MigrationInterface
   */
  protected $migration;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration = NULL) {
    $instance = new static(
      $configuration,
      $plugin_id,
      $plugin_definition
    );

    $instance->migration = $migration;
    $instance->migrationPluginManager = $container->get('plugin.manager.migrate.process');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $term_lookup_migration_ids = (array) $this->configuration['term_migration'];
    $node_lookup_migration_ids = (array) $this->configuration['node_migration'];

    $matches = [];
    if (preg_match('/^(node|taxonomy\/term)\/(\d+)$/', $value, $matches)) {
      $id = $matches[2];
      $base_path = $matches[1];

      switch ($base_path) {
        case 'taxonomy/term':
          $migration_ids = $term_lookup_migration_ids;
          break;

        case 'node':
          $migration_ids = $node_lookup_migration_ids;
          break;

        default:
          $migration_ids = [];
      }

      $config = [
        'migration' => $migration_ids,
      ];

      /** @var \Drupal\migrate\Plugin\migrate\process\MigrationLookup|bool $migmag_lookup */
      $migmag_lookup = $this->migrationPluginManager->createInstance('migmag_lookup', $config, $this->migration);
      if ($migmag_lookup) {
        $migrated_id = $migmag_lookup->transform($id, $migrate_executable, $row, $destination_property);
        if ($migrated_id) {
          $value = $base_path . '/' . $migrated_id;
        }
      }
    }
    return $value;
  }

}
