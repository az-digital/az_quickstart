<?php

namespace Drupal\az_migration\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Process plugin to merge the fields for Jumbotron Paragraph.
 *
 * @MigrateProcessPlugin(
 *   id = "paragraphs_jumbotron_field_merge"
 * )
 */
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
