<?php

namespace Drupal\az_migration\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Process Plugin to field merge for Extra Info paragraphs.
 *
 * @MigrateProcessPlugin(
 *   id = "paragraphs_extra_info_field_merge"
 * )
 */
class ParagraphsExtraInfoFieldMerge extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    // Merging the data into one field.
    $field_uaqs_short_title = $row->getSourceProperty('field_uaqs_short_title');
    $field_uaqs_body = $row->getSourceProperty('field_uaqs_body');
    $field_uaqs_link = $row->getSourceProperty('field_uaqs_link');
    $value['markup'] = '<div class="border-thick border-top border-azurite">
      <div class="border card-body">';
    if (!empty($field_uaqs_link[0]['url'])) {
      $value['markup'] .= '<h3>More information</h3>';
      $value['markup'] .= '<a href="' . $field_uaqs_link[0]['url'] . '" class="' . $field_uaqs_link[0]['attributes']['class'] . '">' . $field_uaqs_link[0]['title'] . '</a>';
    }
    $value['markup'] .= '<h2 class="h3">' . $field_uaqs_short_title[0]['value'] . '</h2>
    ' . $field_uaqs_body[0]['value'] . '
    </div>
    </div>';
    return $value['markup'];
  }

}
