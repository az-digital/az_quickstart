<?php

namespace Drupal\az_migration\Plugin\migrate\source;

use Drupal\migrate\Attribute\MigrateSource;
use Drupal\migrate\Row;
use Drupal\node\Plugin\migrate\source\d7\Node as D7Node;

/**
 * Extends D7Node source plugin with field collection to paragraphs conversion.
 *
 * Converts field collection content field values into paragraphs content.
 *
 * Available configuration keys:
 * - node_type: The node_types to get from the source - can be a string or
 *   an array. If not declared then nodes of all types will be retrieved.
 * - field_collection_names: The field_collection types to get from the source -
 *   can be a string or an array. If not declared then nothing will be
 *   retrieved.
 *
 * Examples:
 *
 * @code
 * source:
 *   plugin: az_node_with_field_collection
 *   node_type: page
 *   field_collection_names: field_accordion
 * @endcode
 *
 * In this example nodes of type page and test along with the field_accordion
 * and field_contacts field collections are retrieved from the source database.
 *
 * @code
 * source:
 *   plugin: az_node_with_field_collection
 *   node_type: [page, test]
 *   field_collection_names:
 *     - field_accordion
 *     - field_contacts
 * @endcode
 *
 * For additional configuration keys refer to the parent classes.
 *
 * @see \Drupal\migrate\Plugin\migrate\source\SqlBase
 * @see \Drupal\migrate\Plugin\migrate\source\SourcePluginBase
 * @see \Drupal\node\Plugin\migrate\source\d7\Node
 */
#[MigrateSource('az_node_with_field_collection')]
class NodeWithFieldCollection extends D7Node {

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {

    // Get Item Id and revision Id of paragraph.
    $nid = $row->getSourceProperty('nid');
    $vid = $row->getSourceProperty('vid');

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
