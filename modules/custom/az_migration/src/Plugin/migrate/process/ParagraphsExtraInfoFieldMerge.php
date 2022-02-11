<?php

namespace Drupal\az_migration\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Process plugin for converting uaqs_extra_info paragraphs to az_text.
 *
 * Quickstart 1 to Quickstart2 process plugin to extract specific field values
 * from the source, (uaqs_extra_info paragraphs), and transforming those values
 * by wrapping them in specific arizona-bootstrap markup before outputting
 * before returning the desired markup.
 *
 * Expects the source value to contain the following fields:
 *  - field_uaqs_short_title: An indexed array with the first index of 0 with 1
 *  element for value.
 *  - field_uaqs_body: An indexed array with the first index of 0 with 1
 *  element for value.
 *  - field_uaqs_link: An indexed array the first index of 0 the following keys:
 *    - url: Link URL.
 *    - attributes: An associative array with the following keys:
 *      - class: Link classes.
 *      - title: Link title.
 *
 * Available configuration keys
 * - N/A
 *
 * @code
 * source: plugin: az_paragraphs_item bundle: uaqs_extra_info destination:
 *   plugin: 'entity_reference_revisions:paragraph' default_bundle: az_text
 *   process: field_az_text_area/value: plugin:
 *   paragraphs_extra_info_field_merge field_az_text_area/format: plugin:
 *   default_value default_value: az_standard
 * @endcode
 *
 * @MigrateProcessPlugin(
 *   id = "paragraphs_extra_info_field_merge"
 * )
 */
class ParagraphsExtraInfoFieldMerge extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    // Merging the data into one field.
    $field_uaqs_short_title = $row->getSourceProperty('field_uaqs_short_title');
    $field_uaqs_body = $row->getSourceProperty('field_uaqs_body');
    $field_uaqs_link = $row->getSourceProperty('field_uaqs_link');
    $value['markup'] = '<div class="border-thick border-top border-azurite">
      <div class="border card-body">';
    if (!empty($field_uaqs_link[0]['url'])) {
      $value['markup'] .= '<h3>More information</h3>';
      $value['markup'] .= '<a href="' . $field_uaqs_link[0]['url'] . '" class="' . $field_uaqs_link[0]['attributes']['class'] . '">' . $field_uaqs_link[0]['title'] . '</a>';
    }
    $value['markup'] .= '<h2 class="h3">' . $field_uaqs_short_title[0]['value'] . '</h2>
    ' . $field_uaqs_body[0]['value'] . '
    </div>
    </div>';
    return $value['markup'];
  }

}
