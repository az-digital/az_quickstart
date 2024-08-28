<?php

namespace Drupal\az_migration\Plugin\migrate\process;

use Drupal\migrate\Attribute\MigrateProcess;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Process Plugin to handle Extra Info Paragraphs from QS1.
 *
 * Quickstart 1 to Quickstart 2 process plugin to extract specific field values
 * from the source, (uaqs_extra_info paragraphs), and transforming those values
 * by wrapping them in specific arizona-bootstrap markup.
 *
 * NOTE: This plugin is only designed to be used with uaqs_extra_info source
 * paragraphs and is not generically reusable for other use cases.
 *
 * Expects the source value to contain the following fields:
 *  - field_uaqs_short_title: An indexed array of associative arrays with a
 *    `value` key.
 *  - field_uaqs_body: An indexed array of associative arrays with a
 *    `value` key.
 *  - field_uaqs_link: An indexed array of associative arrays with the following
 *    keys:
 *    - url
 *    - attributes: An associative array with the following keys:
 *      - class: Link classes.
 *      - title: Link title.
 *
 * Available configuration keys
 * - N/A
 *
 * @code
 * source:
 *   plugin: az_paragraphs_item
 *   bundle: uaqs_extra_info
 * destination:
 *   plugin: 'entity_reference_revisions:paragraph'
 *   default_bundle: az_text
 * process:
 *   field_az_text_area/value:
 *     plugin: paragraphs_extra_info_field_merge
 *   field_az_text_area/format:
 *     plugin: default_value
 *     default_value: az_standard
 * @endcode
 */
#[MigrateProcess('paragraphs_extra_info_field_merge')]
class ParagraphsExtraInfoFieldMerge extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    // Merging the data into one field.
    $field_uaqs_short_title = $row->getSourceProperty('field_uaqs_short_title');
    $field_uaqs_body = $row->getSourceProperty('field_uaqs_body');
    $field_uaqs_link = $row->getSourceProperty('field_uaqs_link');
    $url = $field_uaqs_link[0]['url'] ?? '';
    $classes = $field_uaqs_link[0]['attributes']['class'] ?? '';
    $title = $field_uaqs_link[0]['title'] ?? $url;
    $short_title = $field_uaqs_short_title[0]['value'] ?? '';
    $body = $field_uaqs_body[0]['value'] ?? '';

    $value['markup'] = '<div class="border-thick border-top border-azurite">
      <div class="border card-body">';
    if (!empty($url)) {
      $value['markup'] .= '<h3>More information</h3>';
      $value['markup'] .= '<a href="' . $url . '" class="' . $classes . '">' . $title . '</a>';
    }
    $value['markup'] .= '<h2 class="h3">' . $short_title . '</h2>' . $body . '</div></div>';
    return $value['markup'];
  }

}
