<?php

namespace Drupal\az_migration\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Process Plugin to handle Full-Width Media Row Paragraphs from QS1.
 *
 * Quickstart 1 to Quickstart 2 Process plugin to extract specific field values
 * from the source, (uaqs_full_width_media_row paragraphs), and transforming
 * those values by wrapping them in specific arizona-bootstrap markup.
 *
 * NOTE: This plugin is only designed to be used with uaqs_full_width_media_row
 * source paragraphs and is not generically reusable for other use cases.
 *
 * Expects the source value to contain the following fields:
 *  - body: A Drupal 7 text area field array.
 *  - link: A Drupal 7 multi-value link field array.
 *
 * Available configuration keys
 * - N/A
 *
 * @code
 * source:
 *   plugin: az_paragraphs_item
 *   bundle: uaqs_full_width_media_row
 * destination:
 *   plugin: 'entity_reference_revisions:paragraph'
 *   default_bundle: az_text_media
 * process:
 * field_az_text_area/value:
 *   plugin: paragraphs_fw_media_row_field_merge
 *   body: field_uaqs_summary
 *   link: field_uaqs_links
 * @endcode
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
