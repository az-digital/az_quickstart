<?php

namespace Drupal\az_migration\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Drupal\media\Entity\Media;

/**
 * Process Plugin to merge the fields for file download paragraphs.
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
    $value['markup'] = '';
    $field_uaqs_download_name = $row->getSourceProperty('field_uaqs_download_name');
    if (!empty($field_uaqs_download_name[0]['value'])) {
      $value['markup'] = '<h3>' . $field_uaqs_download_name[0]['value'] . '</h3>';
    }

    // Media embeded for field_uaqs_download_file.
    if (isset($value[0]) && count($value[0])) {
      foreach ($value[0] as $mid) {
        $media = Media::load($mid);
        if (!empty($media)) {
          $value['markup'] .= '<drupal-media data-align="center" data-entity-type="media" data-entity-uuid="' . $media->get('uuid')->value . '" data-view-mode="default"></drupal-media>';
        }
      }
    }

    // Media embeded for field_uaqs_download_preview.
    if (isset($value[1]) && count($value[1])) {
      foreach ($value[1] as $mid) {
        $media = Media::load($mid);
        if (!empty($media)) {
          $value['markup'] .= '<drupal-media data-align="center" data-entity-type="media" data-entity-uuid="' . $media->get('uuid')->value . '" data-view-mode="default"></drupal-media>';
        }
      }
    }
    $field_uaqs_download_description = $row->getSourceProperty('field_uaqs_download_description');
    if (!empty($field_uaqs_download_description[0]['value'])) {
      $value['markup'] .= $field_uaqs_download_description[0]['value'];
    }

    return $value['markup'];
  }

}
