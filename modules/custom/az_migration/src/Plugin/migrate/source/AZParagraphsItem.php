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
  public function query() {
    $query = $this->select('paragraphs_item', 'p')
      ->fields('p',
        [
          'item_id',
          'bundle',
          'field_name',
          'archived',
          'bottom_spacing',
          'view_mode',
        ])
      ->fields('pr', ['revision_id']);
    $query->innerJoin('paragraphs_item_revision', 'pr', static::JOIN);
    // Omit archived (deleted) paragraphs.
    $query->condition('p.archived', 0);
    // This configuration item may be set by a deriver to restrict the
    // bundles retrieved.
    if ($this->configuration['bundle']) {
      $query->condition('p.bundle', $this->configuration['bundle']);
    }
    return $query;
  }

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
      'view_mode' => $this->t('The paragraph view mode'),
    ];
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    [
      'item_id' => $paragraph_id,
      'revision_id' => $paragraph_revision_id,
      'field_name' => $paragraph_parent_field_name,
      'bundle' => $bundle,
    ] = $row->getSource();

    $keep_row = TRUE;

    // We have to find the corresponding parent entity (which might be an
    // another paragraph). Active revision only.
    try {
      $parent_data_query = $this->getDatabase()->select(static::PARENT_FIELD_TABLE_PREFIX . $paragraph_parent_field_name, 'fd');
      $parent_data_query->addField('fd', 'entity_type', 'parent_type');
      $parent_data_query->addField('fd', 'entity_id', 'parent_id');
      $parent_data = $parent_data_query
        ->condition("fd.{$paragraph_parent_field_name}_value", $paragraph_id)
        ->condition("fd.{$paragraph_parent_field_name}_revision_id", $paragraph_revision_id)
        ->execute()->fetchAssoc();
    }
    catch (DatabaseExceptionWrapper $e) {
      // The paragraphs field data|revision table is missing, we cannot get
      // the parent entity identifiers. This is a corrupted database.
      $keep_row = FALSE;
    }

    if (!is_iterable($parent_data)) {
      // We cannot get the parent entity identifiers
      $keep_row = FALSE;
    }
    $row->setSourceProperty('keep', $keep_row);

    // Checking the field collection fields present in the paragraph.
    if (!empty($row->getSourceProperty('field_collection_names'))) {
      // Getting field collection - fields names from configuration.
      $field_collection_field_names = explode(',', $row->getSourceProperty('field_collection_names'));
      foreach ($field_collection_field_names as $field) {

        // Geting field collention values for the paragraph.
        $field_collection_data = $this->getFieldValues('paragraphs_item', $field, $paragraph_id, $paragraph_revision_id);

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
