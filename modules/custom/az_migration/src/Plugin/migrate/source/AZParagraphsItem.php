<?php

declare(strict_types = 1);

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
    $query = parent::query();

    // @phpstan-ignore-next-line
    $this->allowArchivedParagraphs = \Drupal::config('az_migration.settings')->get('allow_archived_paragraphs');

    $query->addField('p', 'bottom_spacing', 'bottom_spacing');
    $query->addField('p', 'view_mode', 'view_mode');

    if (!$this->allowArchivedParagraphs) {
      $this->excludeArchivedParagraphs($query);
    }

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {

    return [
      'bottom_space' => $this->t('The paragraph Bottom Space'),
      'view_mode' => $this->t('The paragraph view mode'),
    ] + parent::fields();

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

  /**
   * Modifies the query to exclude archived paragraphs.
   *
   * This method alters the existing query by adding a condition that filters
   * out paragraphs marked as archived. In Drupal, 'archived' often means the
   * paragraph is either deleted or not in active use, depending on the
   * specific implementation.
   *
   * By adding this condition, only active, non-archived paragraphs are fetched
   * by the query.
   *
   * @param \Drupal\Core\Database\Query\SelectInterface $query
   *   The query object that is being built for fetching paragraph items.
   *   This object is modified by reference.
   */
  private function excludeArchivedParagraphs($query) {
    // Add a condition to the query to exclude rows where 'archived'
    // is marked as 1 (true).
    // In this context, a value of 0 in 'p.archived' means the paragraph
    // is not archived.
    $query->condition('p.archived', 0);
  }

}
