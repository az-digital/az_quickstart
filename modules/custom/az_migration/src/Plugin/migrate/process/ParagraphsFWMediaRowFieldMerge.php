<?php

namespace Drupal\az_migration\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Process plugin to merge the fields for Full Width Media Row Paragraph.
 *
 * @MigrateProcessPlugin(
 *   id = "paragraphs_fw_media_row_field_merge"
 * )
 */
class ParagraphsFWMediaRowFieldMerge extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    // Merging the data into one field.
    $value['markup'] = '';
    if (!empty($this->configuration['body'])) {
      $body = $row->getSourceProperty($this->configuration['body']);
      foreach ($body as $body_item) {
        $value['markup'] .= $body_item['value'];
      }
    }
    if (!empty($this->configuration['link'])) {
      $link = $row->getSourceProperty($this->configuration['link']);
      foreach ($link as $link_item) {
        $attributes = unserialize($link_item['attributes']);
        $value['markup'] .= '<a href="' . $link_item['url'] . '" class="' . $attributes['class'] . '">' . $link_item['title'] . '</a>';
      }
    }
    $value['markup'] .= '</div>';
    return $value['markup'];
  }

}
