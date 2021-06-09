<?php

namespace Drupal\az_migration\Plugin\migrate\process;

use Drupal\media\MediaInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Drupal\media\Entity\Media;

/**
 * Configure Behavior for paragraphs.
 *
 * @MigrateProcessPlugin(
 *   id = "paragraphs_file_download_field_merge"
 * )
 */
class ParagraphsFileDownloadFieldMerge extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    // Merging the data into one field.
    $field_uaqs_download_name = $row->getSourceProperty('field_uaqs_download_name');
    $value['uaqs_text'] = '<h3>' . $field_uaqs_download_name[0]['value'] . '</h3>';

    // Media embeded for field_uaqs_download_file.
    if (isset($value[0]) && count($value[0])) {
      foreach ($value[0] as $mid) {
        $media = Media::load($mid);
        if (!empty($media)) {
          $value['uaqs_text'] .= '<drupal-media data-align="center" data-entity-type="media" data-entity-uuid="' . $media->get('uuid')->value . '" data-view-mode="default"></drupal-media>';
        }
      }
    }

    // Media embeded for field_uaqs_download_preview.
    if (isset($value[1]) && count($value[1])) {
      foreach ($value[1] as $mid) {
        $media = Media::load($mid);
        if ($media instanceof MediaInterface) {
          $value['uaqs_text'] .= '<drupal-media data-align="center" data-entity-type="media" data-entity-uuid="' . $media->get('uuid')->value . '" data-view-mode="default"></drupal-media>';
        }
      }
    }
    return $value['uaqs_text'];
  }

}
