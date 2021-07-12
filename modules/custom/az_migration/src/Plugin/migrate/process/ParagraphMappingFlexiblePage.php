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
    $value['main_content'] = [];
    foreach ($value[0] as $item) {
      if (isset($item['value'])) {
        $value['main_content'][] = [
          'target_id' => $item['value'][0],
          'target_revision_id' => $item['value'][1],
        ];
      }
    }
    if (isset($value[1])) {
      $value['main_content'][] = [
        'target_id' => $value[1][0],
        'target_revision_id' => $value[1][1],
      ];
    }
    return $value['main_content'];
  }

}
