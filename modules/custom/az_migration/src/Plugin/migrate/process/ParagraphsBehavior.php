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

    // Getting the card width checking field.
    if (!empty($this->configuration['card_width_field'])) {
      $card_width_field = $row->getSourceProperty($this->configuration['card_width_field']);
      $card_count_field = $row->getSourceProperty($this->configuration['card_count_field']);

      // If the card width field value is Full Width, adding the conditions.
      if (!empty($card_width_field[0]['value']) &&
        $card_width_field[0]['value'] === 'full_width') {

        $card_width = [
          '1' => 'col-md-12 col-lg-12',
          '2' => 'col-md-6 col-lg-6',
          '3' => 'col-md-4 col-lg-4',
          '4' => 'col-md-3 col-lg-3',
        ];

        $card_width_sm = [
          '1' => 'col-sm-12',
          '2' => 'col-sm-6',
          '3' => 'col-sm-6',
          '4' => 'col-sm-6',
        ];
        $card_count = count($card_count_field);
        $behavior['card_width'] = $card_width[$card_count];
        $behavior['az_display_settings']['card_width_sm'] = $card_width_sm[$card_count];
      }
    }

    if (!empty($row->getSourceProperty('view_mode'))) {
      $card_style_map = [
        'default' => 'card card-borderless',
        'full' => 'card card-borderless',
        'token' => 'card card-borderless',
        'uaqs_landing_grid' => 'card card-borderless',
        'uaqs_borderless_card' => 'card card-borderless',
      ];
      $behavior['card_style'] = $card_style_map[$row->getSourceProperty('view_mode')];
    }

    $value['behavior'] = serialize($behavior);
    return $value['behavior'];
  }

}
