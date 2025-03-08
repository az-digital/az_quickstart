<?php

namespace Drupal\az_migration\Plugin\migrate\process;

use Drupal\migrate\Plugin\migrate\process\MigrationLookup;
use Drupal\migrate\Attribute\MigrateProcess;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides media stubs while registering media for later download via queue.
 *
 * Available configuration keys
 * - migration: A single migration ID, or an array of migration IDs.
 * - source_ids: (optional) An array keyed by migration IDs with values that are
 *   a list of source properties.
 * - stub_id: (optional) Identifies the migration which will be used to create
 *   any stub entities.
 * - no_stub: (optional) Prevents the creation of a stub entity when no
 *   relationship is found in the migration map.
 * - url: url to download a file
 * - path: the stream wrapper and directory to store files.
 * - deferred: a map of migrations and data you wish to share with them.
 *
 * Example:
 *
 * This configuration will create a media stub, while registering a url
 * for later download via queue. At the time the download happens,
 * the deferred migrations will be run for the matching item and will
 * be passed this migration's 'id' field as their 'remote_id' field.
 * @code
 * process:
 *   field_az_photos/target_id:
 *     plugin: az_migration_remote_media
 *     migration: az_trellis_events_media
 *     source: id
 *     url: image_url
 *     deferred:
 *       az_trellis_events_files:
 *         remote_id: id
 *       az_trellis_events_media:
 *         remote_id: id
 * @endcode
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
