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

    // Text Background Color.
    if (!empty($this->configuration['bg_text_color'])) {
      $bg_text_color = $row->getSourceProperty($this->configuration['bg_text_color']);
      $bg_text_color_mapping = [
        'bg-transparent' => 'transparent',
        'bg-trans-white' => 'light',
        'bg-trans-sky' => 'light',
        'bg-trans-arizona-blue' => 'dark',
        'bg-trans-black' => 'dark',
      ];
      foreach ($bg_text_color as $bg_text_color_item) {
        $behavior['bg_color'] = $bg_text_color_mapping[$bg_text_color_item['value']];
      }
    }

    // Text Background Color.
    if (!empty($this->configuration['bg_attach'])) {
      $bg_attach = $row->getSourceProperty($this->configuration['bg_attach']);
      $bg_attach_mapping = [
        'bg-attachment-fixed' => 'bg-fixed',
        'bg-attachment-scroll' => '',
      ];
      foreach ($bg_attach as $bg_attach_item) {
        $behavior['bg_attachment'] = $bg_attach_mapping[$bg_attach_item['value']];
      }
    }

    if (!empty($this->configuration['position'])) {
      if (!empty($row->getSourceProperty($this->configuration['position']))) {
        $position_mapping = [
          'uaqs_bg_img_content_left' => 'col-md-8 col-lg-6',
          'uaqs_bg_img_content_center' => 'col-md-8 col-lg-6 col-md-offset-2 col-lg-offset-3',
          'uaqs_bg_img_content_right' => 'col-md-8 col-lg-6 col-md-offset-4 col-lg-offset-6',
        ];
        $behavior['position'] = $media_mode_mapping[$row->getSourceProperty($this->configuration['position'])];
      }
    }

    if (!empty($this->configuration['full_width'])) {
      $behavior['full_width'] = $this->configuration['full_width'];
    }

    if (!empty($this->configuration['content_style'])) {
      $behavior['style'] = $this->configuration['content_style'];
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
