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
class ParagraphMappingFlexiblePage extends ProcessPluginBase
{

    /**
     * {@inheritdoc}
     */
    public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property)
    {
        // Merging the data into paragraph field on flexible page.
        $main_content = [];
        $this->populateMainContentArray($main_content, $value);

        return $main_content;
    }

    private function populateMainContentArray(&$main_content, $value_array)
    {
        foreach ($value_array as $item) {
            if (
              is_array($item) &&
              count($item) == 2 &&
              is_numeric($item[0]) &&
              is_numeric($item[1])) {
                $main_content[] = [
                  'target_id' => $item[0],
                  'target_revision_id' => $item[1],
                ];
            } elseif (is_array($item)) {
                $this->populateMainContentArray($main_content, $item);
            }
        }
    }
}
