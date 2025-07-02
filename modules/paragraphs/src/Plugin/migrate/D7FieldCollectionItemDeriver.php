<?php

namespace Drupal\paragraphs\Plugin\migrate;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Database\DatabaseExceptionWrapper;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\migrate\Exception\RequirementsException;
use Drupal\migrate\Plugin\MigrationDeriverTrait;
use Drupal\migrate_drupal\FieldDiscoveryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Deriver for field collections.
 */
class D7FieldCollectionItemDeriver extends DeriverBase implements ContainerDeriverInterface {

  use MigrationDeriverTrait;
  use StringTranslationTrait;

  /**
   * The base plugin ID this derivative is for.
   *
   * @var string
   */
  protected $basePluginId;

  /**
   * The migration field discovery service.
   *
   * @var \Drupal\migrate_drupal\FieldDiscoveryInterface
   */
  protected $fieldDiscovery;

  /**
   * D7FieldCollectionItemDeriver constructor.
   *
   * @param string $base_plugin_id
   *   The base plugin ID for the plugin ID.
   * @param \Drupal\migrate_drupal\FieldDiscoveryInterface $field_discovery
   *   The migration field discovery service.
   */
  public function __construct($base_plugin_id, FieldDiscoveryInterface $field_discovery) {
    $this->basePluginId = $base_plugin_id;
    $this->fieldDiscovery = $field_discovery;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $base_plugin_id,
      $container->get('migrate_drupal.field_discovery')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $types = static::getSourcePlugin('d7_field_collection_type');

    try {
      $types->checkRequirements();
    }
    catch (RequirementsException $e) {
      return $this->derivatives;
    }

    try {
      foreach ($types as $row) {
        /** @var \Drupal\migrate\Row $row */
        $values = $base_plugin_definition;
        $fc_bundle = $row->getSourceProperty('field_name');
        $p_bundle = $row->getSourceProperty('bundle');
        $values['label'] = $this->t('@label (@type)', [
          '@label' => $values['label'],
          '@type' => $row->getSourceProperty('name'),
        ]);
        $values['source']['field_name'] = $fc_bundle;
        $values['destination']['default_bundle'] = $p_bundle;

        /** @var \Drupal\migrate\Plugin\Migration $migration */
        $migration = \Drupal::service('plugin.manager.migration')
          ->createStubMigration($values);
        $migration->setProcessOfProperty('parent_id', 'parent_id');
        $migration->setProcessOfProperty('parent_type', 'parent_type');
        $migration->setProcessOfProperty('parent_field_name', 'field_name');

        $this->fieldDiscovery->addBundleFieldProcesses($migration, 'field_collection_item', $fc_bundle);
        $this->derivatives[$p_bundle] = $migration->getPluginDefinition();
      }
    }
    catch (DatabaseExceptionWrapper $e) {
      // Once we begin iterating the source plugin it is possible that the
      // source tables will not exist. This can happen when the
      // MigrationPluginManager gathers up the migration definitions but we do
      // not actually have a Drupal 7 source database.
    }
    return $this->derivatives;
  }

}
