<?php

namespace Drupal\az_migration\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Drupal\media\Entity\Media;

/**
 * Process Plugin to field merge for Column Image paragraphs.
 *
 * @MigrateProcessPlugin(
 *   id = "paragraphs_column_image_field_merge"
 * )
 */
class ParagraphsColumnImageFieldMerge extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    // Merging the data into one field.
    $value['value'] = '';
    // Getting the media into text.
    if (isset($value[0])) {
      $media = Media::load($value[0]);
      if (!empty($media)) {
        $value['value'] .= '<drupal-media data-align="none" data-entity-type="media" data-entity-uuid="' . $media->get('uuid')->value . '" data-view-mode="az_large"></drupal-media>';
      }
    }
    $hr_flag = 0;
    // Getting Credit into text.
    if (!empty($this->configuration['credit'])) {
      $credit = $row->getSourceProperty($this->configuration['credit']);
      foreach ($credit as $credit_item) {
        if (isset($credit_item['value']) && $credit_item['value'] != "") {
          $value['value'] .= '<p><span class="small">' . $credit_item['value'] . '</span></p>';
          $hr_flag = 1;
        }
      }
    }
    // Getting Caption into text.
    if (!empty($this->configuration['caption'])) {
      $caption = $row->getSourceProperty($this->configuration['caption']);
      foreach ($caption as $caption_item) {
        if (isset($credit_item['value']) && $credit_item['value'] != "") {
          $value['value'] .= '<p>' . $caption_item['value'] . '</p>';
          $hr_flag = 1;
        }
      }
    }
    // Setting HR if the content credit or caption content.
    if ($hr_flag) {
      $value['value'] .= '<hr>';
    }
    if (!empty($this->configuration['credit'])) {
      $value['format'] = $this->configuration['format'];
    }
    return $value;
  }

}
