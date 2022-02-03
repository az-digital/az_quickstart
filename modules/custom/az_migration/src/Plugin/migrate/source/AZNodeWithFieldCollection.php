<?php

namespace Drupal\az_migration\Plugin\migrate\source;

use Drupal\migrate\Row;
use Drupal\node\Plugin\migrate\source\d7\Node as D7Node;

/**
 * Custom node source for convering field collections
 * into a single paragraph.
 *
 * @MigrateSource(
 *   id = "az_node_with_field_collection"
 * )
 */
class AZNodeWithFieldCollection extends D7Node {

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {

    // Get Item Id and revision Id of paragraph.
    $nid = $row->getSourceProperty('nid');
    $vid = $row->getSourceProperty('vid');
    $type = $row->getSourceProperty('type');

    // Checking the field collection fields present in the paragraph.
    if (!empty($row->getSourceProperty('field_collection_names'))) {
      // Getting field collection - fields names from configuration.
      $field_collection_field_names = explode(',', $row->getSourceProperty('field_collection_names'));
      foreach ($field_collection_field_names as $field) {

        // Geting field collention values for the paragraph.
        $field_collection_data = $this->getFieldValues('node', $field, $nid, $vid);

        // Get Field API field values for each field collection item.
        $field_names = array_keys($this->getFields('field_collection_item', $field));

        $field_collection_field_values = [];
        foreach ($field_names as $field_collection_field_name) {
          foreach ($field_collection_data as $delta => $field_collection_data_item) {
            $field_collection_value = $this->getFieldValues(
                  'field_collection_item',
                  $field_collection_field_name,
                  $field_collection_data_item['value'],
                  $field_collection_data_item['revision_id']
              );
            foreach ($field_collection_value as $field_collection_value_item) {
              $field_collection_field_values[$delta]['delta'] = $delta;
              $field_collection_field_values[$delta][$field_collection_field_name][] = $field_collection_value_item;
            }
          }
        }
        ksort($field_collection_field_values);
        $source_property_name = $field . '_values';
        $row->setSourceProperty($source_property_name, $field_collection_field_values);
      }
    }
    return parent::prepareRow($row);
  }

}
