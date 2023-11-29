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
    // Extract item_id and revision_id from the current row.
    $item_id = $row->getSourceProperty('item_id');
    $revision_id = $row->getSourceProperty('revision_id');

    // Process field collections associated with the paragraph.
    $this->processFieldCollections($row, $item_id, $revision_id);

    // Process individual fields of the paragraph.
    $this->processParagraphFields($row, $item_id, $revision_id);

    return $row;
  }

  /**
   * Processes field collections within a paragraph.
   *
   * Field collections are groups of fields that can be reused across different
   * entities.
   * This function iterates over each field collection defined in the source
   * property and processes them individually.
   *
   * @param \Drupal\migrate\Row $row
   *   The current row being processed.
   * @param string|int $item_id
   *   The item ID of the paragraph.
   * @param string|int $revision_id
   *   The revision ID of the paragraph.
   */
  private function processFieldCollections(Row $row, $item_id, $revision_id) {
    // Check if there are field collections defined for the paragraph.
    if (!empty($row->getSourceProperty('field_collection_names'))) {
      // Extract field collection names and process each collection.
      $field_collection_field_names = explode(',', $row->getSourceProperty('field_collection_names'));
      foreach ($field_collection_field_names as $field) {
        $this->processEachFieldCollection($row, $field, $item_id, $revision_id);
      }
    }
  }

  /**
   * Processes each individual field collection.
   *
   * Retrieves and sets the values for a specific field collection.
   *
   * @param \Drupal\migrate\Row $row
   *   The current row being processed.
   * @param string $field
   *   The field collection name.
   * @param string|int $item_id
   *   The item ID of the paragraph.
   * @param string|int $revision_id
   *   The revision ID of the paragraph.
   */
  private function processEachFieldCollection(Row $row, $field, $item_id, $revision_id) {
    // Retrieve field collection data for the specific field.
    $field_collection_data = $this->getFieldValues('paragraphs_item', $field, $item_id, $revision_id);
    // Process and sort field collection values.
    $field_collection_field_values = $this->getFieldCollectionValues($field_collection_data, $field);
    ksort($field_collection_field_values);
    // Set the processed values back into the row.
    $row->setSourceProperty($field . '_values', $field_collection_field_values);
  }

  /**
   * Retrieves and organizes field collection values.
   *
   * @param array $field_collection_data
   *   The raw field collection data.
   * @param string $field
   *   The field collection name.
   *
   * @return array
   *   The organized field collection values.
   */
  private function getFieldCollectionValues($field_collection_data, $field) {
    $field_collection_field_values = [];
    foreach ($field_collection_data as $delta => $field_collection_data_item) {
      $field_collection_value = $this->getFieldValues(
        'field_collection_item',
        $field,
        $field_collection_data_item['value'],
        $field_collection_data_item['revision_id']
      );
      $field_collection_field_values[$delta] = $field_collection_value;
    }

    return $field_collection_field_values;
  }

  /**
   * Processes paragraph fields.
   *
   * Iterates over all fields of a paragraph and sets their values in the row.
   *
   * @param \Drupal\migrate\Row $row
   *   The current row being processed.
   * @param string|int $item_id
   *   The item ID of the paragraph.
   * @param string|int $revision_id
   *   The revision ID of the paragraph.
   */
  private function processParagraphFields(Row $row, $item_id, $revision_id) {
    foreach (array_keys($this->getFields('paragraphs_item', $row->getSourceProperty('bundle'))) as $field) {
      $row->setSourceProperty($field, $this->getFieldValues('paragraphs_item', $field, $item_id, $revision_id));
    }
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
