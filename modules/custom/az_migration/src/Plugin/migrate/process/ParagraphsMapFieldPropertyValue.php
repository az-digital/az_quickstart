<?php

namespace Drupal\az_migration\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Process Plugin to Field map property value for paragraphs.
 *
 * @MigrateProcessPlugin(
 *   id = "paragraphs_field_property_mapping"
 * )
 */
class ParagraphsMapFieldPropertyValue extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    // Getting the field property values from the field collection.
    $key = $this->configuration['key'];
    $field_name = $this->configuration['field_name'];
    $value_key = $this->configuration['value'];
    // Get the field values.
    $field_values = $row->getSourceProperty($field_name);
    $value[$key] = $field_values[$value['delta']][$value_key];
    return $value;
  }

}
