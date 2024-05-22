<?php

namespace Drupal\az_migration\Plugin\migrate\process;

use Drupal\media\Entity\Media;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Process PLugin to update media caption for paragraphs.
 *
 * @MigrateProcessPlugin(
 *   id = "az_paragraphs_media_caption"
 * )
 */
class ParagraphsUpdateMediaCaption extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $media_id = $row->getDestinationProperty(ltrim($this->configuration['media_id'], '@'));
    // Loading the media to save from the row.
    $media = Media::load($media_id);
    if ($media && $media->get('field_az_caption')->value === NULL) {
      // Setting the caption for the media.
      $media->set('field_az_caption', $value);
      // Update the media.
      $media->save();
    }
    return $value;
  }

}
