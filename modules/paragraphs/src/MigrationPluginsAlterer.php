<?php

namespace Drupal\paragraphs;

use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\migrate\Plugin\MigrationDeriverTrait;
use Drupal\paragraphs\Plugin\migrate\field\FieldCollection;

/**
 * Class MigrationPluginsAlterer.
 */
final class MigrationPluginsAlterer {

  use MigrationDeriverTrait;

  /**
   * Paragraphs' logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $loggerChannel;

  /**
   * Constructs a MigratePluginAlterer object.
   *
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory service.
   */
  public function __construct(LoggerChannelFactoryInterface $logger_factory) {
    $this->loggerChannel = $logger_factory->get('paragraphs');
  }

  /**
   * Adds field collection and paragraph migration dependencies where needed.
   *
   * @param array[] $migrations
   *   An associative array of migrations keyed by migration ID, the same that
   *   is passed to hook_migration_plugins_alter() hooks.
   */
  public function alterMigrationPlugins(array &$migrations) {
    foreach ($migrations as &$migration) {
      if (!isset($migration['process']) || !is_array($migration['process'])) {
        continue;
      }

      foreach (['entity_type', 'targetEntityType'] as $process_property) {
        if (isset($migration['process'][$process_property])) {
          $this->paragraphsMigrationEntityTypeAdjust($migration, $process_property);
          $this->paragraphsMigrationBundleAdjust($migration);
          $migration['migration_dependencies']['optional'][] = 'd7_field_collection_type';
          $migration['migration_dependencies']['optional'][] = 'd7_paragraphs_type';
        }
      }
    }
  }

  /**
   * Map field_collection_item and 'paragraphs_item' fields to 'paragraph'.
   *
   * @param array $migration
   *   Thei migration to process.
   * @param string $process_property
   *   The process destination.
   */
  public function paragraphsMigrationEntityTypeAdjust(array &$migration, $process_property) {
    if (!$this->paragraphsMigrationPrepareProcess($migration['process'], $process_property)) {
      return;
    }

    $entity_type_process = &$migration['process'][$process_property];
    $entity_type_process[] = [
      'plugin' => 'static_map',
      'map' => [
        'field_collection_item' => 'paragraph',
        'paragraphs_item' => 'paragraph',
      ],
      'bypass' => TRUE,
    ];
  }

  /**
   * Remove 'field_' prefix from field collection bundles.
   *
   * @param array $migration
   *   The migration configuration to process.
   */
  public function paragraphsMigrationBundleAdjust(array &$migration) {
    // @see https://www.drupal.org/project/drupal/releases/9.1.4
    // @see https://www.drupal.org/project/drupal/issues/2565931
    $key = version_compare(\Drupal::VERSION, '9.1.4', '<')
      ? 'bundle'
      : 'bundle_mapped';
    if (!$this->paragraphsMigrationPrepareProcess($migration['process'], $key)) {
      return;
    }

    $bundle_process = &$migration['process'][$key];
    $bundle_process[] = [
      'plugin' => 'paragraphs_process_on_value',
      'source_value' => 'entity_type',
      'expected_value' => 'field_collection_item',
      'process' => [
        'plugin' => 'paragraphs_strip_field_prefix',
      ],
    ];
  }

  /**
   * Converts a migration process to array for adding another process elements.
   *
   * @param array $process
   *   The array of process definitions of a migration.
   * @param string $property
   *   The property which process definition should me converted to an array of
   *   process definitions.
   *
   * @return bool
   *   TRUE when the action was successful, FALSE otherwise.
   */
  public function paragraphsMigrationPrepareProcess(array &$process, $property): bool {
    if (!isset($process[$property])) {
      return FALSE;
    }

    $process_element = &$process[$property];

    // Try to play with other modules altering this, and don't replace it
    // outright unless it's unchanged.
    if (is_string($process_element)) {
      $process_element = [
        [
          'plugin' => 'get',
          'source' => $process_element,
        ],
      ];
    }
    elseif (is_array($process_element) && array_key_exists('plugin', $process_element)) {
      $process_element = [$process_element];
    }

    if (!is_array($process_element)) {
      $this->loggerChannel->error('Unknown migration process element type: @type.', ['@type' => gettype($process_element)]);
      return FALSE;
    }

    return TRUE;
  }

}
