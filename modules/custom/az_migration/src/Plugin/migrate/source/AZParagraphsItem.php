<?php

declare(strict_types=1);

namespace Drupal\az_migration\Plugin\migrate\source;

use Drupal\migrate\Attribute\MigrateSource;
use Drupal\migrate\Row;
use Drupal\paragraphs\Plugin\migrate\source\d7\ParagraphsItem;

/**
 * Drupal 7 Paragraph Item source plugin.
 *
 * @deprecated in az_quickstart:3.2.0 and is removed from az_quickstart:4.0.0.
 * There is no replacement.
 *
 * @see https://www.drupal.org/node/3533564
 *
 * Available configuration keys:
 * - bundle: (optional) If supplied, this will only return paragraphs
 *   of that particular type.
 */
#[MigrateSource('az_paragraphs_item')]
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

    $query->addField('p', 'bottom_spacing', 'bottom_spacing');
    $query->addField('p', 'view_mode', 'view_mode');

    // @phpstan-ignore-next-line
    $this->allowArchivedParagraphs = \Drupal::config('az_migration.settings')->get('allow_archived_paragraphs');
    if (!$this->allowArchivedParagraphs) {
      // Add a condition to the query to exclude rows where 'archived'
      // is marked as 1 (true).
      // In this context, a value of 0 in 'p.archived' means the paragraph
      // is not archived.
      $query->condition('p.archived', 0);
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
    // Retrieve item and revision IDs of the current paragraph.
    $item_id = $row->getSourceProperty('item_id');
    $revision_id = $row->getSourceProperty('revision_id');
    // Check if field collections are associated with this paragraph.
    if (!empty($row->getSourceProperty('field_collection_names'))) {
      // Get names of field collections for the paragraph.
      $field_collection_field_names = explode(',', $row->getSourceProperty('field_collection_names'));
      // Process each field collection.
      foreach ($field_collection_field_names as $field) {
        // Retrieve data for the current field collection.
        $field_collection_data = $this->getFieldValues('paragraphs_item', $field, $item_id, $revision_id);
        // Get field names for each item in the field collection.
        $field_names = array_keys($this->getFields('field_collection_item', $field));
        $field_collection_field_values = [];
        // Process each field in the field collection.
        foreach ($field_names as $field_collection_field_name) {
          foreach ($field_collection_data as $delta => $field_collection_data_item) {
            // Retrieve values for the current field collection item.
            $field_collection_value = $this->getFieldValues(
              'field_collection_item',
              $field_collection_field_name,
              $field_collection_data_item['value'],
              $field_collection_data_item['revision_id']
            );
            // Aggregate values for each field collection item.
            foreach ($field_collection_value as $field_collection_value_item) {
              $field_collection_field_values[$delta]['delta'] = $delta;
              $field_collection_field_values[$delta][$field_collection_field_name][] = $field_collection_value_item;
            }
          }
        }
        // Sort field collection values by their delta values.
        ksort($field_collection_field_values);
        // Set processed field collection values as a row property.
        $source_property_name = $field . '_values';
        $row->setSourceProperty($source_property_name, $field_collection_field_values);
      }
    }
    // Process all other fields of the paragraph item.
    foreach (array_keys($this->getFields('paragraphs_item', $row->getSourceProperty('bundle'))) as $field) {
      // Set the value of each field as a row property.
      $row->setSourceProperty($field, $this->getFieldValues('paragraphs_item', $field, $item_id, $revision_id));
    }

    return $row;
  }

}
