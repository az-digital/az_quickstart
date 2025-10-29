<?php

namespace Drupal\az_migration\Plugin\migrate\process;

use Drupal\migrate\Attribute\MigrateProcess;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Process plugin for converting uaqs_jumbotron paragraphs to az_text.
 *
 * NOTE: This plugin is only designed to be used with uaqs_jumbotron source
 * paragraphs and is not generically reusable for other use cases.
 *
 * Transforms uaqs_jumbotron paragraph field values (Quickstart 1) into
 * HTML markup for use within the field_az_text_area field on az_text paragraph
 * entities (Quickstart 2).
 *
 * Expects uaqs_jumbotron source fields to exist as source properties:
 * - field_uaqs_short_title
 * - field_uaqs_summary
 * - field_uaqs_links
 *
 * Examples:
 * @code
 * source:
 *   plugin: az_paragraphs_item
 *   bundle: uaqs_jumbotron
 * destination:
 *   plugin: 'entity_reference_revisions:paragraph'
 *   default_bundle: az_text
 * process:
 *   field_az_text_area/value:
 *     plugin: paragraphs_jumbotron_field_merge
 *   field_az_text_area/format:
 *     plugin: default_value
 *     default_value: az_standard
 * @endcode
 */
#[MigrateProcess('paragraphs_jumbotron_field_merge')]
class ParagraphsJumbotronFieldMerge extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    // Merging the data into one field.
    $field_uaqs_short_title = $row->getSourceProperty('field_uaqs_short_title');
    $field_uaqs_summary = $row->getSourceProperty('field_uaqs_summary');
    $field_uaqs_links = $row->getSourceProperty('field_uaqs_links');
    $value['markup'] = '<div class="jumbotron">';
    if (!empty($field_uaqs_short_title[0]['value'])) {
      $value['markup'] .= '<h1 class="display-3 mt-0">' . $field_uaqs_short_title[0]['value'] . '</h1>';
    }
    if (!empty($field_uaqs_summary[0]['value'])) {
      $value['markup'] .= '<div class="lead">' . $field_uaqs_summary[0]['value'] . '</div>';
    }
    if (!empty($field_uaqs_links[0]['url'])) {
      $value['markup'] .= '<a href="' . $field_uaqs_links[0]['url'] . '" class="' . $field_uaqs_links[0]['attributes']['class'] . '">' . $field_uaqs_links[0]['title'] . '</a>';
    }
    $value['markup'] .= '</div>';
    return $value['markup'];
  }

}
