<?php

namespace Drupal\az_paragraphs\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Configure Behavior for paragraphs.
 *
 * @deprecated in az_quickstart:2.1.3 and is removed from az_quickstart:2.3.0.
 *   Use the
 *   \Drupal\az_paragraphs\Plugin\migrate\process\ParagraphsBehaviorSettings
 *   process plugin instead following its migration patterns.
 * // @codingStandardsIgnoreStart
 * @see https://github.com/az-digital/az_quickstart/pull/1345
 * @see https://github.com/az-digital/az_quickstart/issues/1348
 * // @codingStandardsIgnoreEnd
 *
 * @MigrateProcessPlugin( id = "paragraphs_behavior_settings"
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

    if (!empty($this->configuration['text_background_full_width']) && $row->getSourceProperty($this->configuration['text_background_full_width']) !== FALSE) {
      $behavior['az_text_background_paragraph_behavior']['text_background_full_width'] = $row->getSourceProperty($this->configuration['text_background_full_width']);
    }

    // Background Text Color.
    if (!empty($this->configuration['bg_text_color'])) {
      $bg_text_color = $row->getSourceProperty($this->configuration['bg_text_color']);
      $bg_text_color_mapping = [
        'bg-transparent' => 'transparent',
        'bg-trans-white' => 'light',
        'bg-trans-sky' => 'light',
        'bg-trans-arizona-blue' => 'dark',
        'bg-trans-black' => 'dark',
        'dark' => 'dark',
        'light' => 'light',

      ];
      foreach ($bg_text_color as $bg_text_color_item) {
        $behavior['az_text_media_paragraph_behavior']['bg_color'] = $bg_text_color_mapping[$bg_text_color_item['value']];
      }
    }

    // Background Attachment.
    if (!empty($this->configuration['bg_attach'])) {
      if (!empty($row->getSourceProperty($this->configuration['bg_attach']))) {
        $bg_attach_mapping = [
          'bg-attachment-fixed' => 'bg-fixed',
          'bg-fixed' => 'bg-fixed',
          'bg-attachment-scroll' => '',
        ];
        $behavior['az_text_media_paragraph_behavior']['bg_attachment'] = $bg_attach_mapping[$row->getSourceProperty($this->configuration['bg_attach'])];
      }
    }

    if (!empty($this->configuration['position'])) {
      if (!empty($row->getSourceProperty($this->configuration['position']))) {
        $position_mapping = [
          'uaqs_bg_img_content_left' => 'col-md-8 col-lg-6',
          'uaqs_bg_img_content_center' => 'col-md-8 col-lg-6 col-md-offset-2 col-lg-offset-3',
          'uaqs_bg_img_content_right' => 'col-md-8 col-lg-6 col-md-offset-4 col-lg-offset-6',
          'col-md-8 col-lg-6' => 'col-md-8 col-lg-6',
          'col-md-8 col-lg-6 col-md-offset-2 col-lg-offset-3' => 'col-md-8 col-lg-6 col-md-offset-2 col-lg-offset-3',
          'col-md-8 col-lg-6 col-md-offset-4 col-lg-offset-6' => 'col-md-8 col-lg-6 col-md-offset-4 col-lg-offset-6',
        ];
        $behavior['az_text_media_paragraph_behavior']['position'] = $position_mapping[$row->getSourceProperty($this->configuration['position'])];
      }
    }

    if (!empty($this->configuration['text_media_spacing'])) {
      $behavior['az_text_media_paragraph_behavior']['text_media_spacing'] = $row->getSourceProperty($this->configuration['text_media_spacing']);
    }

    if (!empty($this->configuration['full_width'])) {
      $behavior['az_text_media_paragraph_behavior']['full_width'] = $row->getSourceProperty($this->configuration['full_width']);
    }

    if (!empty($this->configuration['content_style'])) {
      $behavior['az_text_media_paragraph_behavior']['style'] = $row->getSourceProperty($this->configuration['content_style']);
    }

    if (!empty($this->configuration['card_width'])) {
      $behavior['card_width'] = $this->configuration['card_width'];
    }
    if (!empty($this->configuration['card_style'])) {
      $behavior['card_style'] = $this->configuration['card_style'];
    }
    if (isset($this->configuration['card_clickable'])) {
      $behavior['card_clickable'] = $this->configuration['card_clickable'];
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
        'default' => 'card',
        'full' => 'card',
        'token' => 'card',
        'uaqs_landing_grid' => 'card card-borderless',
        'uaqs_borderless_card' => 'card card-borderless',
      ];
      $behavior['card_style'] = $card_style_map[$row->getSourceProperty('view_mode')];
    }

    $value['behavior'] = serialize($behavior);
    return $value['behavior'];
  }

}
