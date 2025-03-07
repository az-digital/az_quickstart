<?php

namespace Drupal\az_migration\Plugin\migrate\process;

use Drupal\migrate\Plugin\migrate\process\MigrationLookup;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\Attribute\MigrateProcess;
use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateLookupInterface;
use Drupal\migrate\MigrateSkipRowException;
use Drupal\migrate\MigrateStubInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Looks up the value of a property based on a previous migration.
 *
 * It is important to maintain relationships among content coming from the
 * source site. For example, on the source site, a given user account may
 * have an ID of 123, but the Drupal user account created from it may have
 * a uid of 456. The migration process maintains the relationships between
 * source and destination identifiers in map tables, and this information
 * is leveraged by the migration_lookup process plugin.
 *
 * Available configuration keys
 * - migration: A single migration ID, or an array of migration IDs.
 * - source_ids: (optional) An array keyed by migration IDs with values that are
 *   a list of source properties.
 * - stub_id: (optional) Identifies the migration which will be used to create
 *   any stub entities.
 * - no_stub: (optional) Prevents the creation of a stub entity when no
 *   relationship is found in the migration map.
 *
 * Examples:
 *
 * Consider a node migration, where you want to maintain authorship. Let's
 * assume that users are previously migrated in a migration named 'users'. The
 * 'users' migration saved the mapping between the source and destination IDs in
 * a map table. The node migration example below maps the node 'uid' property so
 * that we first take the source 'author' value and then do a lookup for the
 * corresponding Drupal user ID from the map table.
 * @code
 * process:
 *   uid:
 *     plugin: migration_lookup
 *     migration: users
 *     source: author
 * @endcode
 *
 * The value of 'migration' can be a list of migration IDs. When using multiple
 * migrations it is possible each use different source identifiers. In this
 * case one can use source_ids which is an array keyed by the migration IDs
 * and the value is a list of source properties. See example below.
 * @code
 * process:
 *   uid:
 *     plugin: migration_lookup
 *     migration:
 *       - users
 *       - members
 *     source_ids:
 *       users:
 *         - author
 *       members:
 *         - id
 * @endcode
 *
 * It's not required to describe source identifiers for each migration. If the
 * source identifier for a migration is not specified, the default source value
 * will be used. In the example below, the 'author' source property will be used
 * to do a lookup in the 'users' migration, and the 'uid' property in the
 * 'members' migration.
 * @code
 * process:
 *   uid:
 *     plugin: migration_lookup
 *     source: uid
 *     migration:
 *       - users
 *       - members
 *     source_ids:
 *       users:
 *         - author
 * @endcode
 *
 * If the migration_lookup plugin does not find the source ID in the migration
 * map it will create a stub entity for the relationship to use. This stub is
 * generated by the migration provided. In the case of multiple migrations the
 * first value of the migration list will be used, but you can select the
 * migration you wish to use by using the stub_id configuration key. The example
 * below uses 'members' migration to create stub entities.
 * @code
 * process:
 *   uid:
 *     plugin: migration_lookup
 *     migration:
 *       - users
 *       - members
 *     stub_id: members
 * @endcode
 *
 * To prevent the creation of a stub entity when no relationship is found in the
 * migration map, 'no_stub' configuration can be used as shown below.
 * @code
 * process:
 *   uid:
 *     plugin: migration_lookup
 *     migration: users
 *     no_stub: true
 *     source: author
 * @endcode
 *
 * If the source value passed in to the plugin is NULL, boolean FALSE, an empty
 * array or an empty string, the plugin will return NULL and stop further
 * processing on the pipeline. This is done for backwards compatibility reasons,
 * and future versions of this plugin should simply return NULL and allow
 * processing to continue.
 * @see https://www.drupal.org/project/drupal/issues/3246666
 *
 * @see \Drupal\migrate\Plugin\MigrateProcessInterface
 */
#[MigrateProcess('az_migration_remote_media')]
class MigrationRemoteMedia extends MigrationLookup {

  /**
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected $queueFactory;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, ?MigrationInterface $migration = NULL) {
    $instance = parent::create(
      $container,
      $configuration,
      $plugin_id,
      $plugin_definition,
      $migration,
    );
    $instance->queueFactory = $container->get('queue');
    return $instance;
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\migrate\MigrateException
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $value = parent::transform($value, $migrate_executable, $row, $destination_property);
    $url_field = $this->configuration['url'];
    // If we don't have a URL, there's nothing to do.
    if (empty($url_field)) {
      return $value;
    }
    $url = $row->get($url_field);
    if (empty($url)) {
      return $value;
    }

    // Build source list for deferred migrations.
    $migrations = $this->configuration['deferred'] ?? [];
    $map = [];
    foreach ($migrations as $key => $migration) {
      // Interpolate as fields.
      foreach ($migration as $property => $field) {
        if (!is_array($field)) {
          // Get the row value for the field.
          $field = $row->get($field);
        }
        $map[$key][$property] = $field;
      }
    }
    // Register the deferred queue.
    // This will later download the file.
    $job = [
      'url' => $url,
      'path' => ($this->configuration['path'] ?? 'public://'),
      'deferred' => $map,
    ];

    $deferred = $this->configuration['deferred'];
    $this->queueFactory->get('az_deferred_media')->createItem($job);

    return $value;
  }

}
