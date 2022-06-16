<?php

namespace Drupal\az_migration\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;

/**
 * Process plugin to recognize which media bundle to use.
 *
 * Available configuration keys
 * - prefix: The prefix to use when determining the entity type.
 *
 * Examples:
 *
 * Consider a paragraphs migration, where you want to be able to automatically
 * use a specific destination media type but only use one media migration.
 *
 * @code
 * process:
 *   destination_bundle:
 *     plugin:  az_media_bundle_recognizer
 *     prefix: 'az_'
 *
 * @endcode
 *
 * @MigrateProcessPlugin(
 *   id = "az_media_bundle_recognizer"
 * )
 *
 * @return String that represents a bundle machine name.
 */
class MediaBundleRecognizer extends ProcessPluginBase {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $prefix = '';
    if (!empty($this->configuration['prefix'])) {
      // Set the prefix if it exists.
      $prefix = $this->configuration['prefix'];
    }

    $mimetype = $row->getSourceProperty('filemime');
    if ($mimetype === 'video/oembed') {
      $value = $prefix . 'remote_video';
    }
    else {
      $value = $prefix . $row->getSourceProperty('type');
    }

    return $value;

  }

}
