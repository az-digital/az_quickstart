<?php

namespace Drupal\field_group_migrate\Plugin\migrate;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Database\DatabaseExceptionWrapper;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\migrate\Exception\RequirementsException;
use Drupal\migrate\Plugin\MigrationDeriverTrait;
use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

/**
 * Derives Drupal 7 field group migrations per entity type and bundle.
 */
class D7FieldGroupDeriver extends DeriverBase {

  use MigrationDeriverTrait;
  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $field_group_source = static::getSourcePlugin('d7_field_group');

    try {
      $field_group_source->checkRequirements();
    }
    catch (RequirementsException $e) {
      // The requirements of the "d7_field_group" source plugin can fail if:
      // - The source database is not configured or it isn't a Drupal 7 DB.
      // - The Field Group module is not enabled on the source Drupal instance.
      return $this->derivatives;
    }

    assert($field_group_source instanceof DrupalSqlBase);

    try {
      foreach ($field_group_source as $field_group_row) {
        assert($field_group_row instanceof Row);
        [
          'entity_type' => $entity_type,
          'bundle' => $bundle,
        ] = $field_group_row->getSource();

        $derivative_id = implode(PluginBase::DERIVATIVE_SEPARATOR, [
          $entity_type,
          $bundle,
        ]);

        if (!empty($this->derivatives[$derivative_id])) {
          continue;
        }
        $derivative_definition = $base_plugin_definition;
        $derivative_definition['source']['entity_type'] = $entity_type;
        $derivative_definition['source']['bundle'] = $bundle;
        $derivative_definition['label'] = $this->t('@label of @entity_type (bundle: @bundle)', [
          '@label' => $derivative_definition['label'],
          '@entity_type' => $entity_type,
          '@bundle' => $bundle,
        ]);
        $this->derivatives[$derivative_id] = $derivative_definition;
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
