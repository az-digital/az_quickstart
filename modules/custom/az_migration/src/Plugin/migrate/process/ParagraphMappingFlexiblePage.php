<?php

namespace Drupal\az_migration\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Process Plugin to map paragraph to the flexible page.
 *
 * @MigrateProcessPlugin(
 *   id = "paragraphs_mapping_flexible_page"
 * )
 */
class ParagraphMappingFlexiblePage extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    // Merging the data into paragraph field on flexible page.
    $main_content = [];
    foreach ($value as $item) {
      if (isset($item[0]) && isset($item[1])) {
        $main_content[] = [
          'target_id' => $item[0],
          'target_revision_id' => $item[1],
        ];
      }
    }

    return $main_content;
  }

}
