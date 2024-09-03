<?php

namespace Drupal\az_migration\Plugin\migrate\process;

use Drupal\media\Entity\Media;
use Drupal\migrate\Attribute\MigrateProcess;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Process plugin for converting uaqs_file_download paragraphs to az_text.
 *
 * NOTE: This plugin is only designed to be used with uaqs_file_download source
 * paragraphs and is not generically reusable for other use cases.
 *
 * Transforms uaqs_file_download paragraph file field values (Quickstart 1) into
 * embedded media markup (i.e. <drupal-media>) for use within the
 * field_az_text_area field on az_text paragraph entities (Quickstart 2).
 *
 * Expects a source value containing an indexed array with 2 elements:
 * - Destination file ID for field_uaqs_download_file (obtained through
 * migration lookup)
 * - Destination file ID for field_uaqs_download_preview (obtained through
 * migration lookup)
 *
 * Also transforms value of the source field_uaqs_download_name (if not empty)
 * to an <h3> element that is added to the destination field.
 *
 * Examples:
 * @code
 * process:
 *   temp_download_file:
 *     -
 *       plugin: sub_process
 *       source: field_uaqs_download_file
 *       process:
 *         -
 *           plugin: migration_lookup
 *           source: fid
 *           migration:
 *             - az_media
 *
 *   temp_download_preview:
 *     -
 *       plugin: sub_process
 *       source: field_uaqs_download_preview
 *       process:
 *         -
 *           plugin: migration_lookup
 *           source: fid
 *           migration:
 *             - az_media
 *   field_az_text_area/value:
 *     -
 *       plugin: merge
 *       source:
 *         - '@temp_download_file'
 *         - '@temp_download_preview'
 *     -
 *       plugin: paragraphs_file_download_field_merge
 * @endcode
 */
#[MigrateProcess('paragraphs_file_download_field_merge')]
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

    // Media embedded for field_uaqs_download_file.
    if (isset($value[0]) && count($value[0])) {
      foreach ($value[0] as $mid) {
        $media = Media::load($mid);
        if (!empty($media)) {
          $value['markup'] .= '<drupal-media data-align="center" data-entity-type="media" data-entity-uuid="' . $media->get('uuid')->value . '" data-view-mode="default"></drupal-media>';
        }
      }
    }

    // Media embedded for field_uaqs_download_preview.
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
