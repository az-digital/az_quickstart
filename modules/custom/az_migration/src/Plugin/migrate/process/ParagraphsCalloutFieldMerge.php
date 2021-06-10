<?php

namespace Drupal\az_migration\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Configure Behavior for paragraphs.
 *
 * @MigrateProcessPlugin(
 *   id = "paragraphs_callout_field_merge"
 * )
 */
class ParagraphsCalloutFieldMerge extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    // Merging the data into one field.
    $title_field = $row->getSourceProperty('title_field');
    $field_uaqs_summary = $row->getSourceProperty('field_uaqs_summary');
    $field_uaqs_callout_background = $row->getSourceProperty('field_uaqs_callout_background');

    // Backgroud Class.
    $bg_light = '';
    if (!empty($field_uaqs_callout_background[0]['value'])) {
      $bg_light = 'bg-light';
    }

    // Color and text Mapping.
    $field_uaqs_callout_border_color = $row->getSourceProperty('field_uaqs_callout_border_color');
    $color_mapping = '';
    $text_mapping = '';
    if ($field_uaqs_callout_border_color[0]['value'] === "callout-warning") {
      $color_mapping = 'border-warning';
      $text_mapping = 'text-warning';
    }
    elseif ($field_uaqs_callout_border_color[0]['value'] === "callout-danger") {
      $color_mapping = 'border-danger';
      $text_mapping = 'text-danger';
    }
    elseif ($field_uaqs_callout_border_color[0]['value'] === "callout-info") {
      $color_mapping = 'border-info';
      $text_mapping = 'text-info';
    }
    else {
      $color_mapping = $field_uaqs_callout_border_color[0]['value'];
    }

    // Field value mapping.
    $value['uaqs_text'] = '';
    if (!empty($title_field[0]['value'])) {
      $value['uaqs_text'] .= '<h4 class="' . $text_mapping . '">' . $title_field[0]['value'] . '</h4>';
    }
    if (!empty($field_uaqs_summary[0]['value'])) {
      $value['uaqs_text'] .= '<div class="callout ' . $color_mapping . ' ' . $bg_light . '">' . $field_uaqs_summary[0]['value'] . '</div>';
    }

    return $value['uaqs_text'];
  }

}
