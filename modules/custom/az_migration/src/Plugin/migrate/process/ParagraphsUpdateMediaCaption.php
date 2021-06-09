<?php

namespace Drupal\az_migration\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Drupal\media\Entity\Media;

/**
 * Configure Behavior for paragraphs.
 *
 * @MigrateProcessPlugin(
 *   id = "paragraphs_media_caption"
 * )
 */
class ParagraphsUpdateMediaCaption extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    // Get the field name from the yml.
    $field_name = $this->configuration['field'];
    // Get the field values.
    $field_values = $row->getSourceProperty($field_name);
    // Loading the media to save from the row.
    $media = Media::load($value['target_id']);
    if ($media && $media->get('field_az_caption')->value === NULL) {
      // Setting the caption for the media.
      $media->set('field_az_caption', $field_values[$value['delta']]['value']);
      // Update the media.
      $media->save();
    }
    return $value;
  }

}
