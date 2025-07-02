<?php

namespace Drupal\metatag\Plugin\migrate\source\d7;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\migrate\Exception\RequirementsException;
use Drupal\migrate\Plugin\MigrationDeriverTrait;
use Drupal\migrate_drupal\MigrationConfigurationTrait;
use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Deriver for Metatag-D7 field instances.
 *
 * Covers d7_metatag_field_instance and
 * d7_metatag_field_instance_widget_settings.
 */
class MetatagFieldInstanceDeriver extends DeriverBase implements ContainerDeriverInterface {

  use MigrationDeriverTrait;
  use MigrationConfigurationTrait;
  use StringTranslationTrait;

  /**
   * Required entity type DB tables, keyed by the entity type ID.
   *
   * @var string[][]
   */
  protected $supportedEntityTypesTables = [
    'node' => ['node', 'node_type'],
    'taxonomy_term' => ['taxonomy_term_data', 'taxonomy_vocabulary'],
    'user' => [],
  ];

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a PathRedirectDeriver instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $source = $this->getSourcePlugin($base_plugin_definition['source']['plugin']);
    assert($source instanceof DrupalSqlBase);

    try {
      $source->checkRequirements();
    }
    catch (RequirementsException $e) {
      // If the source plugin requirements failed, that means we do not have a
      // Drupal source database configured - return nothing.
      return $this->derivatives;
    }
    foreach ($this->supportedEntityTypesTables as $entity_type_id => $entity_type_tables) {
      // Skip if the entity type is missing.
      if (!$this->entityTypeManager->hasDefinition($entity_type_id)) {
        continue;
      }

      // Skip if the required database tables are missing.
      foreach ($entity_type_tables as $entity_type_table) {
        if (!$source->getDatabase()->schema()->tableExists($entity_type_table)) {
          continue;
        }
      }

      $base_query = $source->getDatabase()->select('metatag', 'm');
      $base_query->condition('m.entity_type', $entity_type_id);

      // If there are no metatags for this entity type, no derivatives needed.
      $metatag_count = (int) (clone $base_query)->countQuery()->execute()->fetchField();
      if ($metatag_count === 0) {
        continue;
      }

      $metatags_grouped_by_bundle = [];
      switch ($entity_type_id) {
        case 'node':
          // We want to get a per-node-type metatag migration. So we inner join
          // the base query on node table based on the parsed node ID.
          $base_query->join('node', 'n', "n.nid = m.entity_id");
          $base_query->fields('n', ['type']);
          // We'll need the "human" name of the node type.
          $base_query->join('node_type', 'nt', 'nt.type = n.type');
          $base_query->fields('nt', ['name']);
          $base_query->groupBy('n.type');
          $base_query->groupBy('nt.name');

          // Get every node-related metatag, grouped by node type.
          $rows = $base_query->execute()->fetchAllAssoc('type');
          $metatags_grouped_by_bundle = array_reduce($rows, function (array $carry, $row) {
            $carry[$row->type] = $row->name;

            return $carry;
          }, []);
          break;

        case 'taxonomy_term':
          // Join the taxonomy term data table to the base query; based on
          // the parsed taxonomy term ID.
          $base_query->join('taxonomy_term_data', 'ttd', "ttd.tid = m.entity_id");
          $base_query->fields('ttd', ['vid']);
          // Since the "taxonomy_term_data" table contains only the taxonomy
          // vocabulary ID, but not the vocabulary name, we have to inner
          // join the "taxonomy_vocabulary" table as well.
          $base_query->join('taxonomy_vocabulary', 'tv', 'ttd.vid = tv.vid');
          $base_query->fields('tv', ['machine_name', 'name']);
          $base_query->groupBy('ttd.vid');
          $base_query->groupBy('tv.machine_name');
          $base_query->groupBy('tv.name');

          // Get all of the metatags whose destination is a taxonomy
          // term URL.
          $rows = $base_query->execute()->fetchAllAssoc('machine_name');
          $metatags_grouped_by_bundle = array_reduce($rows, function (array $carry, $row) {
            $carry[$row->machine_name] = $row->name;

            return $carry;
          }, []);
          break;
      }

      // If we have per-bundle results for a content entity type, we are
      // able to derive migrations per entity type and bundle.
      // Dependency metadata is added in metatag_migration_plugins_alter().
      if (!empty($metatags_grouped_by_bundle)) {
        foreach ($metatags_grouped_by_bundle as $bundle_id => $bundle_label) {
          $derivative_id = "$entity_type_id:$bundle_id";
          $this->derivatives[$derivative_id] = $base_plugin_definition;
          $this->derivatives[$derivative_id]['source']['entity_type_id'] = $entity_type_id;
          $this->derivatives[$derivative_id]['source']['entity_type'] = $entity_type_id;
          $this->derivatives[$derivative_id]['source']['bundle'] = $bundle_id;
          $this->derivatives[$derivative_id]['label'] = $this->t('@label of @type @entity-type-label', [
            '@label' => $base_plugin_definition['label'],
            '@type' => $bundle_label,
            '@entity-type-label' => $this->entityTypeManager->getDefinition($entity_type_id)->getPluralLabel(),
          ]);
          // :<entity type ID>:<bundle> suffix for dependencies on
          // d7_metatag_field_instance.
          foreach (['d7_metatag_field_instance'] as $dep_id) {
            $dependency_index = array_search($dep_id, $this->derivatives[$derivative_id]['migration_dependencies']['required']);
            if ($dependency_index !== FALSE) {
              $this->derivatives[$derivative_id]['migration_dependencies']['required'][$dependency_index] .= ":$entity_type_id:$bundle_id";
            }
          }
          // Add bundle dependency.
          switch ($entity_type_id) {
            case 'node':
              $this->derivatives[$derivative_id]['migration_dependencies']['required'][] = "d7_node_type:$bundle_id";
              break;

            case 'taxonomy_term':
              $this->derivatives[$derivative_id]['migration_dependencies']['required'][] = "d7_taxonomy_vocabulary:$bundle_id";
              break;
          }

          // :<entity type ID> suffix for dependencies on d7_metatag_field.
          foreach (['d7_metatag_field'] as $dep_id) {
            $dependency_index = array_search($dep_id, $this->derivatives[$derivative_id]['migration_dependencies']['required']);
            if ($dependency_index !== FALSE) {
              $this->derivatives[$derivative_id]['migration_dependencies']['required'][$dependency_index] .= ":$entity_type_id";
            }
          }
        }
      }
      // If we don't have per-bundle results, we will derive only a
      // per-entity-type metatag migration.
      else {
        $this->derivatives[$entity_type_id] = $base_plugin_definition;
        $this->derivatives[$entity_type_id]['source']['entity_type_id'] = $entity_type_id;
        $this->derivatives[$entity_type_id]['label'] = $this->t('@label of @type', [
          '@label' => $base_plugin_definition['label'],
          '@type' => $this->entityTypeManager->getDefinition($entity_type_id)->getPluralLabel(),
        ]);
        // :<entity type ID> suffix for dependencies on
        // d7_metatag_field and d7_metatag_field_instance.
        foreach (['d7_metatag_field', 'd7_metatag_field_instance'] as $dep_id) {
          $dependency_index = array_search($dep_id, $this->derivatives[$entity_type_id]['migration_dependencies']['required']);
          if ($dependency_index !== FALSE) {
            $this->derivatives[$entity_type_id]['migration_dependencies']['required'][$dependency_index] .= ":$entity_type_id";
          }
        }
      }
    }

    return $this->derivatives;
  }

}
