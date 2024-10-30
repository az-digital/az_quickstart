<?php

namespace Drupal\az_migration\Plugin\migrate\process;

use Drupal\migrate\Attribute\MigrateProcess;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Process Plugin to handle Callout Paragraphs from QS1.
 *
 * Quickstart 1 to Quickstart2 Process plugin to extract specific field values
 * from the source, (uaqs_callout paragraphs), and transforming those values
 * by wrapping them in specific arizona-bootstrap markup.
 *
 * NOTE: This plugin is only designed to be used with uaqs_callout source
 * paragraphs and is not generically reusable for other use cases.
 *
 * Expects the source value to contain the following fields:
 *  - title: A Drupal 7 plain text field array.
 *  - text: A Drupal 7 text area field array.
 *  - background: A Drupal 7 field array.
 *  - border: A Drupal 7 field array.
 *
 * Available configuration keys
 * - N/A
 *
 * @code
 * source:
 *   plugin: az_paragraphs_item
 *   bundle: uaqs_callout
 *
 * destination:
 *   plugin: 'entity_reference_revisions:paragraph'
 *   default_bundle: az_text
 *
 * process:
 *   field_az_text_area/value:
 *     plugin: paragraphs_callout_field_merge
 *     title: title_field
 *     text: field_uaqs_summary
 *     background: field_uaqs_callout_background
 *     border: field_uaqs_callout_border_color
 * @endcode
 */
#[MigrateProcess('paragraphs_callout_field_merge')]
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
