<?php

namespace Drupal\az_migration\Plugin\migrate\process;

use Drupal\migrate\Attribute\MigrateProcess;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Process Plugin to map paragraph to the flexible page.
 */
#[MigrateProcess('paragraphs_mapping_flexible_page')]
class ParagraphMappingFlexiblePage extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    // Merging the data into paragraph field on flexible page.
    $main_content = [];
    $this->populateMainContentArray($main_content, $value);

    return $main_content;
  }

  /**
   * Processes incoming value array.
   *
   * Internal, recurive function. This function iterates the incoming array
   * of values to transform and searches for valid values to pull-
   * out and return as an array of paragraph ids to associate
   * with a given node.
   *
   * @param array $main_content
   *   An array of target_ids/target_revision_ids to return.
   * @param array $value_array
   *   The incoming array of values to transform and iterate.
   */
  private function populateMainContentArray(array &$main_content, array $value_array) {
    foreach ($value_array as $item) {
      if (
      is_array($item) &&
      count($item) === 2 &&
      is_numeric($item[0]) &&
      is_numeric($item[1])) {
        $main_content[] = [
          'target_id' => $item[0],
          'target_revision_id' => $item[1],
        ];
      }
      elseif (is_array($item)) {
        $this->populateMainContentArray($main_content, $item);
      }
    }
  }

}
