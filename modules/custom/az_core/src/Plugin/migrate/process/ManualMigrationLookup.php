<?php

namespace Drupal\az_core\Plugin\migrate\process;

use Drupal\Core\Database\Database;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\MigrateSkipProcessException;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Process Plugin to handle content that was manually migrated.
 *
 * @MigrateProcessPlugin(
 *   id = "az_manual_migration_lookup"
 * )
 * Only works when the source database is a Drupal 7 database.
 *
 * This plugin looks up content from the source database and returns the field
 * data for the found entity. This requires an entity id from the source site.
 * This is useful for content that was manually migrated (Copy and Pasted) and not
 * tied to a migration.
 *
 * @return string source entity identifier Example: node title.
 *
 * Node example:
 *
 * @code
 *   process:
 *     field_entity_reference:
 *     plugin: sub_process
 *     source: field_source_entity_reference
 *     process:
 *       delta: delta
 *       target_id:
 *         - plugin: az_manual_migration_lookup
 *           source_entity_type: node
 *           source: target_id
 *         - plugin: entity_lookup
 *           entity_type: node
 *           value_key: title
 *           bundle: az_person
 *           bundle_key: type
 *           ignore_case: true
 * @endcode
 *
 * Taxonomy term example:
 *
 * @code
 *  process:
 *   field_tags:
 *    plugin: sub_process
 *    source: field_tags
 *    process:
 *      delta: delta
 *      target_id:
 *        - plugin: az_manual_migration_lookup
 *          source_entity_type: taxonomy_term
 *          source: tid
 *        - plugin: entity_lookup
 *          entity_type: taxonomy_term
 *          value_key: name
 *          bundle: az_news_tags
 *          bundle_key: vid
 *          ignore_case: true
 * @endcode
*/
class ManualMigrationLookup extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    if (!array_key_exists('source_entity_type', $configuration)) {
      throw new \InvalidArgumentException('Manual Migration Lookup plugin is missing source_entity_type configuration. Valid values are: node, taxonomy_term.');
    }

    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }


  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {

      $source_entity_type = $this->configuration['source_entity_type'];

      $id = $value;
      switch ($source_entity_type) {
        case 'node':
          // Lookup of content type.
          $value = Database::getConnection('default', 'migrate')
            ->query('SELECT title FROM {node} WHERE nid = :nid', [':nid' => $id])
            ->fetchField();
          break;

        case 'taxonomy_term':
            // Lookup of content type.
            $value = Database::getConnection('default', 'migrate')
              ->query('SELECT name FROM {taxonomy_term_data} WHERE tid = :tid', [':tid' => $id])
              ->fetchField();

            break;

        // Unimplemented type.
        default:
          break;
      }

      if (empty($value)) {
        $message = sprintf('Processing of destination property %s was skipped: No value found for source entity type %s with id %s.', $destination_property, $source_entity_type, $id);
        throw new MigrateSkipProcessException($message);
      }

    return $value;
  }

}
