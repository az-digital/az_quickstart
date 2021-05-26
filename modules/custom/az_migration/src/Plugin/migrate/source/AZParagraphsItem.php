<?php

namespace Drupal\az_migration\Plugin\migrate\source;

use Drupal\migrate\Row;
use Drupal\paragraphs\Plugin\migrate\source\d7\ParagraphsItem;

/**
 * Drupal 7 Paragraph Item source plugin.
 *
 * @MigrateSource(
 *   id = "az_paragraphs_item"
 * )
 */
class AZParagraphsItem extends ParagraphsItem {

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'item_id' => $this->t('The paragraph_item id'),
      'revision_id' => $this->t('The paragraph_item revision id'),
      'bundle' => $this->t('The paragraph bundle'),
      'field_name' => $this->t('The paragraph field_name'),
      'bottom_space' => $this->t('The paragraph Bottom Space'),
    ];
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {

    // Get Item Id and revision Id of paragraph.
    $item_id = $row->getSourceProperty('item_id');
    $revision_id = $row->getSourceProperty('revision_id');

    // Checking the field collection fields present in the paragraph.
    if (!empty($row->getSourceProperty('field_collection_names'))) {
      // Getting field collection - fields names from configuration.
      $field_collection_field_names = explode(',', $row->getSourceProperty('field_collection_names'));
      foreach ($field_collection_field_names as $field) {

        // Geting field collention values for the paragraph.
        $field_collection_data = $this->getFieldValues('paragraphs_item', $field, $item_id, $revision_id);

        // Get Field API field values for each field collection item.
        $field_names = array_keys($this->getFields('field_collection_item', $field));

        foreach ($field_names as $field_collection_field_name) {
          $i = 0;
          $field_collection_field_values = [];
          foreach ($field_collection_data as $field_collection_data_item) {
            $field_collection_value = $this->getFieldValues('field_collection_item',
                                      $field_collection_field_name, $field_collection_data_item['value'],
                                      $field_collection_data_item['revision_id']);
            $field_collection_value = reset($field_collection_value);
            $field_collection_value['delta'] = $i;
            $field_collection_field_values[] = $field_collection_value;
            $i++;
          }
          $row->setSourceProperty($field_collection_field_name, $field_collection_field_values);
        }
      }
    }

    $paragraph_bottom_spacing = $this->select('paragraphs_item', 'pi')
      ->fields('pi', ['bottom_spacing'])
      ->condition('pi.item_id', $item_id)
      ->execute()
      ->fetchCol();
    $row->setSourceProperty('bottom_spacing', $paragraph_bottom_spacing);
    return parent::prepareRow($row);
  }

}
