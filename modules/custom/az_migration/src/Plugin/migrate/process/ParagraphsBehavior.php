<?php

namespace Drupal\az_migration\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Configure Behavior for paragraphs.
 *
 * @MigrateProcessPlugin(
 *   id = "paragraphs_behavior_settings"
 * )
 */
class ParagraphsBehavior extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    // Setting the behavior to the paragraph.
    $behavior = ['az_display_settings' => ['bottom_spacing' => $row->getSourceProperty('bottom_spacing')]];
    if (!empty($this->configuration['gallery_display'])) {
      $behavior['gallery_display'] = $this->configuration['gallery_display'];
    }
   if (!empty($this->configuration['bg_color'])) {
      $bg_color = $row->getSourceProperty($this->configuration['bg_color']);
      $behavior['az_text_background_paragraph_behavior']['text_background_color'] = '';
      foreach ($bg_color as $bg_color_item) {
        $behavior['az_text_background_paragraph_behavior']['text_background_color'] = $bg_color_item['value'];
      }
    }
    // Background Pattern.
    if (!empty($this->configuration['bg_pattern'])) {
      $bg_pattern = $row->getSourceProperty($this->configuration['bg_pattern']);
      $behavior['az_text_background_paragraph_behavior']['text_background_pattern'] = '';
      foreach ($bg_pattern as $bg_pattern_item) {
        $deprecated_patterns = [
          'bg-triangles-mosaic',
          'bg-triangles-fade',
          'bg-catalinas-abstract',
        ];
        if (in_array($bg_pattern_item['value'], $deprecated_patterns)) {
          $behavior['az_text_background_paragraph_behavior']['text_background_pattern'] = '';
        }
        else {
          $behavior['az_text_background_paragraph_behavior']['text_background_pattern'] = $bg_pattern_item['value'];
        }
      }
    }
    if (!empty($this->configuration['card_width'])) {
      $behavior['card_width'] = $this->configuration['card_width'];
    }
    if (!empty($this->configuration['card_style'])) {
      $behavior['card_style'] = $this->configuration['card_style'];
    }
    if (!empty($this->configuration['card_width_sm'])) {
      $behavior['az_display_settings']['card_width_sm'] = $this->configuration['card_width_sm'];
    }
    if (!empty($this->configuration['card_width_xs'])) {
      $behavior['az_display_settings']['card_width_xs'] = $this->configuration['card_width_xs'];
    }

    $value['behavior'] = serialize($behavior);
    return $value['behavior'];
  }

}
