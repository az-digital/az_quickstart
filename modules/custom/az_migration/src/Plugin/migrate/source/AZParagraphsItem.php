<?php

namespace Drupal\az_migration\Plugin\migrate\source;

use Drupal\migrate\Row;
use Drupal\paragraphs\Plugin\migrate\source\d7\ParagraphsItem;

/**
 * Drupal 7 Paragraph Item source plugin.
 *
 * Available configuration keys:
 * - bundle: (optional) If supplied, this will only return paragraphs
 *   of that particular type.
 *
 * @MigrateSource(
 *   id = "az_paragraphs_item"
 * )
 */
class AZParagraphsItem extends ParagraphsItem {

  /**
   * Whether to migrate archived paragraphs.
   *
   * @var bool
   */
  protected $allowArchivedParagraphs;

  /**
   * {@inheritdoc}
   */
  public function query() {
    // @phpstan-ignore-next-line
    $this->allowArchivedParagraphs = \Drupal::config('az_migration.settings')->get('allow_archived_paragraphs');

    $query = $this->select('paragraphs_item', 'p')
      ->fields('p',
        ['item_id',
          'bundle',
          'field_name',
          'archived',
          'bottom_spacing',
          'view_mode',
        ])
      ->fields('pr', ['revision_id']);
    $query->innerJoin('paragraphs_item_revision', 'pr', static::JOIN);

    if (!$this->allowArchivedParagraphs) {
      // Omit archived (deleted or stale) paragraphs.
      $query->condition('p.archived', 0);
    }
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

    foreach (array_keys($this->getFields('paragraphs_item', $row->getSourceProperty('bundle'))) as $field) {
      $row->setSourceProperty($field, $this->getFieldValues('paragraphs_item', $field, $item_id, $revision_id));
    }

    return $row;
  }

}
