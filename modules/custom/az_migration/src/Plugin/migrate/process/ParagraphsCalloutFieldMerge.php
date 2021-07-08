<?php

namespace Drupal\az_migration\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Process plugin to merge field for callout paragraphs.
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
    // Backgroud Class.
    $bg_light = '';
    if (!empty($this->configuration['background'])) {
      $field_uaqs_callout_background = $row->getSourceProperty($this->configuration['background']);
      if (!empty($field_uaqs_callout_background[0]['value'])) {
        $bg_light = 'bg-light';
      }
    }
    // Color and text Mapping.
    $color_mapping = '';
    $text_mapping = '';
    if (!empty($this->configuration['border'])) {
      $field_uaqs_callout_border_color = $row->getSourceProperty($this->configuration['border']);
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
    }

    // Field value mapping.
    $value['markup'] = '';
    $value['markup'] .= '<div class="callout ' . $color_mapping . ' ' . $bg_light . '">';

    if (!empty($this->configuration['title'])) {
      $title_field = $row->getSourceProperty($this->configuration['title']);
      if (!empty($title_field[0]['value'])) {
        $value['markup'] .= '<h4 class="' . $text_mapping . '">' . $title_field[0]['value'] . '</h4>';
      }
    }

    if (!empty($this->configuration['text'])) {
      $field_uaqs_summary = $row->getSourceProperty($this->configuration['text']);
      if (!empty($field_uaqs_summary[0]['value'])) {
        $value['markup'] .= $field_uaqs_summary[0]['value'];
      }
    }

    $value['markup'] .= '</div>';
    return $value['markup'];
  }

}
