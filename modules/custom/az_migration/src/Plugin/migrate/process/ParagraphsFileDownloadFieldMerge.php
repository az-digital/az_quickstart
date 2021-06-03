<?php

namespace Drupal\az_migration\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

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
    $field_uaqs_download_description = $row->getSourceProperty('field_uaqs_download_description');
    $field_uaqs_download_file = $row->getSourceProperty('field_uaqs_download_file');
    $field_uaqs_download_preview = $row->getSourceProperty('field_uaqs_download_preview');

    $value['uaqs_text'] = '<div class="border-thick border-top border-azurite"><div class="border card-body">
      <h3>' . $field_uaqs_download_name[0]['value'] . '</h3>';
    $value['uaqs_text'] .= '</div></div>';
    return $value['uaqs_text'];
  }

}
