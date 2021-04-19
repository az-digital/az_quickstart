<?php

namespace Drupal\az_migration\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\media\Entity\Media;
use Drupal\file\Entity\File;
use Drupal\migrate\MigrateException;
use Drupal\migrate\ProcessPluginBase;

/**
 * Generates a media entity from a file and returns the media id.
 *
 * @MigrateProcessPlugin(
 *   id = "az_media_generate"
 * )
 *
 * To generate the entity it is best to this in a subprocess:
 *
 * @code
 *  field_name:
 *    -
 *      plugin: sub_process
 *      source: field_name
 *      process:
 *        target_id:
 *          -
 *            plugin: media_generate
 *            source: fid
 *            destination_bundle: media_bundle
 *            destination_field: field_media_name
 *            imageAltSource: alt
 *            imageTitleSource: title
 * @endcode
 */
class MediaGenerate extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    if (!isset($this->configuration['destination_field'])) {
      throw new MigrateException('Destination field must be set.');
    }
    if (!isset($this->configuration['destination_bundle'])) {
      throw new MigrateException('Destination bundle must be set.');
    }

    $field = $this->configuration['destination_field'];
    $bundle = $this->configuration['destination_bundle'];
    $alt = $this->configuration['imageAltSource'];
    $title = $this->configuration['imageTitleSource'];

    /** @var /Drupal/file/entity/File $file */
    $file = File::load($value);
    if ($file === NULL) {
      throw new MigrateException('Referenced file does not exist');
    }

    $media = Media::create([
      'bundle' => $bundle,
      'uid' => $file->getOwner()->id(),
      'status' => '1',
      'name' => $file->label(),
      $field => [
        'target_id' => $file->id(),
        'alt' => $alt,
        'title' => $title,
      ],
    ]);
    $media->save();

    // @todo uncomment this on the final migration: file_delete($file->id());
    return $media->id();
  }

}
